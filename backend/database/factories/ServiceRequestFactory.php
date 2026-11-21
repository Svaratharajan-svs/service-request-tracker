<?php

namespace Database\Factories;

use App\Enums\ServiceRequestPriority;
use App\Enums\ServiceRequestStatus;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceRequest>
 */
class ServiceRequestFactory extends Factory
{
    protected $model = ServiceRequest::class;

    public function definition(): array
    {
        return [
            'created_by' => User::factory(),

            'title' => fake()->sentence(4),

            'description' => fake()->text(200),

            'priority' => fake()->randomElement(
                ServiceRequestPriority::cases()
            ),

            'status' => ServiceRequestStatus::OPEN,

            'version' => 1,
        ];
    }
}
