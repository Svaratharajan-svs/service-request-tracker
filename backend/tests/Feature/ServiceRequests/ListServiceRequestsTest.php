<?php

namespace Tests\Feature\ServiceRequests;

use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListServiceRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_only_sees_their_own_requests(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        ServiceRequest::factory()->create([
            'created_by' => $user->id,
            'title' => 'My request',
            'priority' => 'HIGH',
        ]);

        ServiceRequest::factory()->create([
            'created_by' => $anotherUser->id,
            'title' => 'Other employee request',
            'priority' => 'LOW',
        ]);

        $response = $this->actingAs($user)->getJson('/api/requests');

        $response->assertOk()
            ->assertJsonFragment(['title' => 'My request'])
            ->assertJsonMissing([
                'title' => 'Other employee request',
            ]);
    }

    public function test_user_can_filter_requests_by_status(): void
    {
        $user = User::factory()->create();

        ServiceRequest::factory()->create([
            'created_by' => $user->id,
            'status' => 'OPEN',
            'priority' => 'MEDIUM',
        ]);

        ServiceRequest::factory()->create([
            'created_by' => $user->id,
            'status' => 'RESOLVED',
            'priority' => 'HIGH',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/requests?status=OPEN');

        $response->assertOk()
            ->assertJsonFragment(['status' => 'OPEN'])
            ->assertJsonMissing(['status' => 'RESOLVED']);
    }

    public function test_user_can_search_their_requests(): void
    {
        $user = User::factory()->create();

        ServiceRequest::factory()->create([
            'created_by' => $user->id,
            'title' => 'Laptop repair',
            'priority' => 'HIGH',
        ]);

        ServiceRequest::factory()->create([
            'created_by' => $user->id,
            'title' => 'Office chair',
            'priority' => 'LOW',
        ]);

        $response = $this->actingAs($user)
            ->getJson('/api/requests?search=Laptop');

        $response->assertOk()
            ->assertJsonFragment(['title' => 'Laptop repair'])
            ->assertJsonMissing(['title' => 'Office chair']);
    }
}
