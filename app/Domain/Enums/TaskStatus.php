<?php

namespace App\Domain\Enums;

enum TaskStatus: string
{
    case PENDING   = 'PENDING';
    case COMPLETED = 'COMPLETED';
}
