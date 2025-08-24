<?php

namespace App\Domain\Entities;

use App\Domain\Enums\TaskStatus;

class Task
{
    public function __construct(
        public ?int $id,
        public string $title,
        public ?string $description,
        public TaskStatus $status
    ) {}
}
