<?php

namespace App\Enums;

enum ServiceRequestPriority: string
{
    case LOW = 'LOW';
    case MEDIUM = 'MEDIUM';
    case HIGH = 'HIGH';
}