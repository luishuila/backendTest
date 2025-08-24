<?php

namespace App\Application\Dto\Tasks;

use App\Domain\Enums\TaskStatus;

class CreateTaskDTO
{
    public function __construct(
        public string $title,
        public ?string $description,
        public TaskStatus $status = TaskStatus::PENDING
    ) {}
}
