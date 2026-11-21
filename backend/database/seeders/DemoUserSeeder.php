<?php

namespace Database\Seeders;

use App\Enums\ServiceRequestPriority;
use App\Enums\ServiceRequestStatus;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $employee1 = User::updateOrCreate(
                ['email' => 'employee1@example.com'],
                [
                    'name' => 'Employee One',
                    'password' => Hash::make('Password@123'),
                ]
            );

            $employee2 = User::updateOrCreate(
                ['email' => 'employee2@example.com'],
                [
                    'name' => 'Employee Two',
                    'password' => Hash::make('Password@123'),
                ]
            );

            $this->seedServiceRequests($employee1, [
                [
                    'title' => 'Laptop repair request',
                    'description' => 'The laptop screen is flickering and needs technical inspection.',
                    'priority' => ServiceRequestPriority::HIGH,
                    'status' => ServiceRequestStatus::OPEN,
                ],
                [
                    'title' => 'Office software installation',
                    'description' => 'Please install the required development software on my workstation.',
                    'priority' => ServiceRequestPriority::MEDIUM,
                    'status' => ServiceRequestStatus::IN_PROGRESS,
                ],
                [
                    'title' => 'Email access issue',
                    'description' => 'Unable to access the corporate email account after password reset.',
                    'priority' => ServiceRequestPriority::LOW,
                    'status' => ServiceRequestStatus::RESOLVED,
                ],
            ]);

            $this->seedServiceRequests($employee2, [
                [
                    'title' => 'Network connectivity issue',
                    'description' => 'The workstation frequently disconnects from the office network.',
                    'priority' => ServiceRequestPriority::HIGH,
                    'status' => ServiceRequestStatus::IN_PROGRESS,
                ],
                [
                    'title' => 'New keyboard request',
                    'description' => 'Requesting a replacement keyboard because several keys are not working.',
                    'priority' => ServiceRequestPriority::LOW,
                    'status' => ServiceRequestStatus::OPEN,
                ],
                [
                    'title' => 'Printer configuration',
                    'description' => 'The office printer configuration has been completed successfully.',
                    'priority' => ServiceRequestPriority::MEDIUM,
                    'status' => ServiceRequestStatus::RESOLVED,
                ],
            ]);
        });
    }

    private function seedServiceRequests(User $user, array $requests): void
    {
        foreach ($requests as $data) {
            $status = $data['status'];

            $version = match ($status) {
                ServiceRequestStatus::OPEN => 1,
                ServiceRequestStatus::IN_PROGRESS => 2,
                ServiceRequestStatus::RESOLVED => 3,
            };

            $serviceRequest = ServiceRequest::updateOrCreate(
                [
                    'created_by' => $user->id,
                    'title' => $data['title'],
                ],
                [
                    'description' => $data['description'],
                    'priority' => $data['priority'],
                    'status' => $status,
                    'version' => $version,
                ]
            );

            $history = [
                [
                    'from_status' => null,
                    'to_status' => ServiceRequestStatus::OPEN,
                    'version' => 1,
                ],
            ];

            if ($version >= 2) {
                $history[] = [
                    'from_status' => ServiceRequestStatus::OPEN,
                    'to_status' => ServiceRequestStatus::IN_PROGRESS,
                    'version' => 2,
                ];
            }

            if ($version >= 3) {
                $history[] = [
                    'from_status' => ServiceRequestStatus::IN_PROGRESS,
                    'to_status' => ServiceRequestStatus::RESOLVED,
                    'version' => 3,
                ];
            }

            foreach ($history as $entry) {
                $serviceRequest->histories()->updateOrCreate(
                    ['version' => $entry['version']],
                    [
                        'changed_by_user_id' => $user->id,
                        'from_status' => $entry['from_status'],
                        'to_status' => $entry['to_status'],
                    ]
                );
            }
        }
    }
}
