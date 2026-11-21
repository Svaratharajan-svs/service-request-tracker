<?php

namespace App\Enums;

enum ServiceRequestStatus: string
{
    case OPEN = 'OPEN';
    case IN_PROGRESS = 'IN_PROGRESS';
    case RESOLVED = 'RESOLVED';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::IN_PROGRESS => 'In Progress',
            self::RESOLVED => 'Resolved',
        };
    }

    public function canTransitionTo(
        ServiceRequestStatus $next
    ): bool {
        return match ($this) {
            self::OPEN => $next === self::IN_PROGRESS,

            self::IN_PROGRESS => $next === self::RESOLVED,

            self::RESOLVED => false,
        };
    }
}