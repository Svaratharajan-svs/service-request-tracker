<?php

namespace Tests\Unit;

use App\Enums\ServiceRequestStatus;
use PHPUnit\Framework\TestCase;

class ServiceRequestStatusTest extends TestCase
{
    public function test_open_can_transition_to_in_progress(): void
    {
        $this->assertTrue(
            ServiceRequestStatus::OPEN->canTransitionTo(
                ServiceRequestStatus::IN_PROGRESS
            )
        );
    }

    public function test_open_cannot_transition_directly_to_resolved(): void
    {
        $this->assertFalse(
            ServiceRequestStatus::OPEN->canTransitionTo(
                ServiceRequestStatus::RESOLVED
            )
        );
    }

    public function test_in_progress_can_transition_to_resolved(): void
    {
        $this->assertTrue(
            ServiceRequestStatus::IN_PROGRESS->canTransitionTo(
                ServiceRequestStatus::RESOLVED
            )
        );
    }

    public function test_resolved_is_terminal(): void
    {
        $this->assertFalse(
            ServiceRequestStatus::RESOLVED->canTransitionTo(
                ServiceRequestStatus::OPEN
            )
        );

        $this->assertFalse(
            ServiceRequestStatus::RESOLVED->canTransitionTo(
                ServiceRequestStatus::IN_PROGRESS
            )
        );

        $this->assertFalse(
            ServiceRequestStatus::RESOLVED->canTransitionTo(
                ServiceRequestStatus::RESOLVED
            )
        );
    }
}