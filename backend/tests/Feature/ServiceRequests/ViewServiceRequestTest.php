<?php

namespace Tests\Feature\ServiceRequests;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ViewServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_view_request(): void
    {
        $user = User::factory()->create();

        $request = ServiceRequest::factory()->create([
            'created_by' => $user->id,
            'priority' => 'HIGH',
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/requests/{$request->id}");

        $response->assertOk()
            ->assertJsonPath('data.created_by', $user->id)
            ->assertJsonPath('data.priority', 'HIGH');
    }

    public function test_other_user_cannot_view_request(): void
    {
        $owner = User::factory()->create();
        $anotherUser = User::factory()->create();

        $request = ServiceRequest::factory()->create([
            'created_by' => $owner->id,
            'priority' => 'MEDIUM',
        ]);

        $response = $this->actingAs($anotherUser)
            ->getJson("/api/requests/{$request->id}");

        $response->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_view_request(): void
    {
        $user = User::factory()->create();

        $request = ServiceRequest::factory()->create([
            'created_by' => $user->id,
            'priority' => 'LOW',
        ]);

        $this->getJson("/api/requests/{$request->id}")
            ->assertUnauthorized();
    }
}
