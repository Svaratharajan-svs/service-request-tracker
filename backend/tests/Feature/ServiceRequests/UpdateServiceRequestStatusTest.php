<?php

namespace Tests\Feature\ServiceRequests;

use App\Models\RequestStatusHistory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class UpdateServiceRequestStatusTest extends TestCase
{
    use RefreshDatabase;

    private function createRequest(User $user): ServiceRequest
    {
        return ServiceRequest::factory()->create([
            'created_by' => $user->id,
            'priority' => 'MEDIUM',
            'status' => 'OPEN',
            'version' => 1,
        ]);
    }

    public function test_user_can_move_open_request_to_in_progress(): void
    {
        $user = User::factory()->create();
        $request = $this->createRequest($user);

        $response = $this->actingAs($user)->patchJson(
            "/api/requests/{$request->id}/status",
            [
                'status' => 'IN_PROGRESS',
                'expected_version' => 1,
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.status', 'IN_PROGRESS')
            ->assertJsonPath('data.version', 2)
            ->assertJsonPath('data.priority', 'MEDIUM');

        $this->assertDatabaseHas('service_requests', [
            'id' => $request->id,
            'created_by' => $user->id,
            'status' => 'IN_PROGRESS',
            'version' => 2,
        ]);
    }

    public function test_user_can_resolve_in_progress_request(): void
    {
        $user = User::factory()->create();

        $request = ServiceRequest::factory()->create([
            'created_by' => $user->id,
            'priority' => 'HIGH',
            'status' => 'IN_PROGRESS',
            'version' => 2,
        ]);

        $response = $this->actingAs($user)->patchJson(
            "/api/requests/{$request->id}/status",
            [
                'status' => 'RESOLVED',
                'expected_version' => 2,
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.status', 'RESOLVED')
            ->assertJsonPath('data.version', 3);
    }

    public function test_stale_version_returns_conflict_without_updating(): void
    {
        $user = User::factory()->create();

        $request = ServiceRequest::factory()->create([
            'created_by' => $user->id,
            'priority' => 'MEDIUM',
            'status' => 'IN_PROGRESS',
            'version' => 2,
        ]);

        $historyCountBefore = RequestStatusHistory::count();

        $response = $this->actingAs($user)->patchJson(
            "/api/requests/{$request->id}/status",
            [
                'status' => 'RESOLVED',
                'expected_version' => 1,
            ]
        );

        $response->assertStatus(409)
            ->assertJsonPath('error.code', 'VERSION_CONFLICT');

        $this->assertDatabaseHas('service_requests', [
            'id' => $request->id,
            'status' => 'IN_PROGRESS',
            'version' => 2,
        ]);

        $this->assertSame(
            $historyCountBefore,
            RequestStatusHistory::count()
        );
    }

    public function test_invalid_transition_returns_422(): void
    {
        $user = User::factory()->create();
        $request = $this->createRequest($user);

        $response = $this->actingAs($user)->patchJson(
            "/api/requests/{$request->id}/status",
            [
                'status' => 'RESOLVED',
                'expected_version' => 1,
            ]
        );

        $response->assertUnprocessable();

        $this->assertDatabaseHas('service_requests', [
            'id' => $request->id,
            'status' => 'OPEN',
            'version' => 1,
        ]);
    }

    public function test_resolved_request_cannot_be_changed_again(): void
    {
        $user = User::factory()->create();

        $request = ServiceRequest::factory()->create([
            'created_by' => $user->id,
            'priority' => 'HIGH',
            'status' => 'RESOLVED',
            'version' => 3,
        ]);

        $response = $this->actingAs($user)->patchJson(
            "/api/requests/{$request->id}/status",
            [
                'status' => 'OPEN',
                'expected_version' => 3,
            ]
        );

        $response->assertUnprocessable();

        $this->assertDatabaseHas('service_requests', [
            'id' => $request->id,
            'status' => 'RESOLVED',
            'version' => 3,
        ]);
    }

    public function test_another_user_cannot_update_request_status(): void
    {
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();

        $request = $this->createRequest($owner);

        $response = $this->actingAs($anotherUser)->patchJson(
            "/api/requests/{$request->id}/status",
            [
                'status' => 'IN_PROGRESS',
                'expected_version' => 1,
            ]
        );

        $response->assertNotFound();

        $this->assertDatabaseHas('service_requests', [
            'id' => $request->id,
            'created_by' => $owner->id,
            'status' => 'OPEN',
            'version' => 1,
        ]);
    }

    public function test_history_failure_rolls_back_status_update(): void
    {
        $user = User::factory()->create();
        $request = $this->createRequest($user);

        $failureInjected = false;

        DB::connection()->beforeExecuting(
            function ($query, $bindings, $connection) use (&$failureInjected) {
                if (
                    !$failureInjected &&
                    preg_match(
                        '/insert\s+into\s+["`]?request_status_histories["`]?/i',
                        $query
                    )
                ) {
                    $failureInjected = true;

                    throw new RuntimeException(
                        'Simulated history insertion failure.'
                    );
                }
            }
        );

        $this->withoutExceptionHandling();

        $caughtException = null;

        try {
            $this->actingAs($user)->patchJson(
                "/api/requests/{$request->id}/status",
                [
                    'status' => 'IN_PROGRESS',
                    'expected_version' => 1,
                ]
            );
        } catch (RuntimeException $exception) {
            $caughtException = $exception;
        }

        $this->assertTrue(
            $failureInjected,
            'The history insertion failure was not triggered.'
        );

        $this->assertNotNull(
            $caughtException,
            'Expected history insertion to throw an exception.'
        );

        $this->assertSame(
            'Simulated history insertion failure.',
            $caughtException->getMessage()
        );

        // The status and version update must be rolled back.
        $this->assertDatabaseHas('service_requests', [
            'id' => $request->id,
            'created_by' => $user->id,
            'status' => 'OPEN',
            'version' => 1,
        ]);

        // The failed history entry must not exist.
        $this->assertDatabaseMissing('request_status_histories', [
            'service_request_id' => $request->id,
            'to_status' => 'IN_PROGRESS',
            'version' => 2,
        ]);
    }

    public function test_status_history_versions_are_sequential(): void
    {
        $user = User::factory()->create();
        $request = $this->createRequest($user);

        // Insert initial history entry.
        $request->histories()->create([
            'changed_by_user_id' => $user->id,
            'from_status' => null,
            'to_status' => 'OPEN',
            'version' => 1,
        ]);

        // OPEN -> IN_PROGRESS.
        $this->actingAs($user)->patchJson(
            "/api/requests/{$request->id}/status",
            [
                'status' => 'IN_PROGRESS',
                'expected_version' => 1,
            ]
        )->assertOk();

        // IN_PROGRESS -> RESOLVED.
        $this->actingAs($user)->patchJson(
            "/api/requests/{$request->id}/status",
            [
                'status' => 'RESOLVED',
                'expected_version' => 2,
            ]
        )->assertOk();

        $versions = $request->histories()
            ->orderBy('version')
            ->pluck('version')
            ->all();

        $this->assertSame([1, 2, 3], $versions);
    }
}
