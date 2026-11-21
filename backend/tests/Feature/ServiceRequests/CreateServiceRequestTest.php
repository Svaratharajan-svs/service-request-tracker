<?php

namespace Tests\Feature\ServiceRequests;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreateServiceRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_create_request(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/requests', [
            'title' => 'Laptop issue',
            'description' => 'My laptop is not starting.',
            'priority' => 'HIGH',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.created_by', $user->id)
            ->assertJsonPath('data.title', 'Laptop issue')
            ->assertJsonPath('data.description', 'My laptop is not starting.')
            ->assertJsonPath('data.priority', 'HIGH')
            ->assertJsonPath('data.status', 'OPEN')
            ->assertJsonPath('data.version', 1);

        $this->assertDatabaseHas('service_requests', [
            'created_by' => $user->id,
            'title' => 'Laptop issue',
            'description' => 'My laptop is not starting.',
            'priority' => 'HIGH',
            'status' => 'OPEN',
            'version' => 1,
        ]);

        $this->assertDatabaseHas('request_status_histories', [
            'changed_by_user_id' => $user->id,
            'from_status' => null,
            'to_status' => 'OPEN',
            'version' => 1,
        ]);
    }

    public function test_unauthenticated_user_cannot_create_request(): void
    {
        $response = $this->postJson('/api/requests', [
            'title' => 'Laptop issue',
            'description' => 'My laptop is not starting.',
            'priority' => 'HIGH',
        ]);

        $response->assertUnauthorized();
    }

    public function test_request_creation_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/requests', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'description',
                'priority',
            ]);
    }

    public function test_user_cannot_assign_request_to_another_user(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/requests', [
            'title' => 'Laptop issue',
            'description' => 'My laptop is not starting.',
            'priority' => 'MEDIUM',

            // These fields must not be controlled by the client.
            'created_by' => $anotherUser->id,
            'status' => 'RESOLVED',
            'version' => 100,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.created_by', $user->id)
            ->assertJsonPath('data.status', 'OPEN')
            ->assertJsonPath('data.version', 1);

        $this->assertDatabaseHas('service_requests', [
            'created_by' => $user->id,
            'priority' => 'MEDIUM',
            'status' => 'OPEN',
            'version' => 1,
        ]);

        $this->assertDatabaseMissing('service_requests', [
            'created_by' => $anotherUser->id,
            'title' => 'Laptop issue',
        ]);
    }

    public function test_invalid_priority_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/requests', [
            'title' => 'Laptop issue',
            'description' => 'My laptop is not starting.',
            'priority' => 'URGENT',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['priority']);
    }

    public function test_title_must_be_between_3_and_100_characters(): void
    {
        $user = User::factory()->create();

        $shortTitleResponse = $this->actingAs($user)->postJson(
            '/api/requests',
            [
                'title' => 'AB',
                'description' => 'My laptop is not starting.',
                'priority' => 'HIGH',
            ]
        );

        $shortTitleResponse->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);

        $longTitleResponse = $this->actingAs($user)->postJson(
            '/api/requests',
            [
                'title' => str_repeat('A', 101),
                'description' => 'My laptop is not starting.',
                'priority' => 'HIGH',
            ]
        );

        $longTitleResponse->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_description_must_be_between_10_and_500_characters(): void
    {
        $user = User::factory()->create();

        $shortDescriptionResponse = $this->actingAs($user)->postJson(
            '/api/requests',
            [
                'title' => 'Laptop issue',
                'description' => 'Too short',
                'priority' => 'HIGH',
            ]
        );

        $shortDescriptionResponse->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);

        $longDescriptionResponse = $this->actingAs($user)->postJson(
            '/api/requests',
            [
                'title' => 'Laptop issue',
                'description' => str_repeat('A', 501),
                'priority' => 'HIGH',
            ]
        );

        $longDescriptionResponse->assertUnprocessable()
            ->assertJsonValidationErrors(['description']);
    }
}
