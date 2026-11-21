<?php

namespace Tests\Feature\ServiceRequests;

use App\Models\RequestStatusHistory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceRequestHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_history_contains_initial_open_entry(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/requests', [
            'title' => 'Network issue',
            'description' => 'Cannot connect to office network.',
            'priority' => 'HIGH',
        ]);

        $response->assertCreated();

        $requestId = $response->json('data.id');

        $historyResponse = $this->actingAs($user)
            ->getJson("/api/requests/{$requestId}/history");

        $historyResponse->assertOk()
            ->assertJsonPath('data.0.from_status', null)
            ->assertJsonPath('data.0.to_status', 'OPEN')
            ->assertJsonPath('data.0.version', 1);
    }

    public function test_history_tracks_each_successful_transition(): void
    {
        $user = User::factory()->create();

        $request = ServiceRequest::factory()->create([
            'created_by' => $user->id,
            'status' => 'OPEN',
            'priority' => 'MEDIUM',
            'version' => 1,
        ]);

        // Factory creation does not create history.
        $request->histories()->create([
            'changed_by_user_id' => $user->id,
            'from_status' => null,
            'to_status' => 'OPEN',
            'version' => 1,
        ]);

        $this->actingAs($user)->patchJson(
            "/api/requests/{$request->id}/status",
            [
                'status' => 'IN_PROGRESS',
                'expected_version' => 1,
            ]
        )->assertOk();

        $this->actingAs($user)->patchJson(
            "/api/requests/{$request->id}/status",
            [
                'status' => 'RESOLVED',
                'expected_version' => 2,
            ]
        )->assertOk();

        $history = $this->actingAs($user)
            ->getJson("/api/requests/{$request->id}/history");

        $history->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.version', 1)
            ->assertJsonPath('data.1.version', 2)
            ->assertJsonPath('data.2.version', 3)
            ->assertJsonPath('data.1.from_status', 'OPEN')
            ->assertJsonPath('data.1.to_status', 'IN_PROGRESS')
            ->assertJsonPath('data.2.from_status', 'IN_PROGRESS')
            ->assertJsonPath('data.2.to_status', 'RESOLVED');
    }

    public function test_other_user_cannot_view_history(): void
    {
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();

        $request = ServiceRequest::factory()->create([
            'created_by' => $owner->id,
            'priority' => 'LOW',
        ]);

        $response = $this->actingAs($anotherUser)
            ->getJson("/api/requests/{$request->id}/history");

        $response->assertNotFound();
    }

    public function test_history_rows_are_created_only_for_successful_transitions(): void
    {
        $user = User::factory()->create();

        $request = ServiceRequest::factory()->create([
            'created_by' => $user->id,
            'status' => 'OPEN',
            'priority' => 'MEDIUM',
            'version' => 1,
        ]);

        $initialCount = RequestStatusHistory::count();

        // Invalid transition.
        $this->actingAs($user)->patchJson(
            "/api/requests/{$request->id}/status",
            [
                'status' => 'RESOLVED',
                'expected_version' => 1,
            ]
        )->assertUnprocessable();

        // Stale version.
        $this->actingAs($user)->patchJson(
            "/api/requests/{$request->id}/status",
            [
                'status' => 'IN_PROGRESS',
                'expected_version' => 9,
            ]
        )->assertStatus(409);

        $this->assertSame(
            $initialCount,
            RequestStatusHistory::count()
        );
    }
}
