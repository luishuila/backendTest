<?php

namespace App\Application\Dto\Tasks;

use App\Domain\Enums\TaskStatus;

class CreateTaskDto
{
    public function __construct(
        public string $title,
        public ?string $description,
        public TaskStatus $status = TaskStatus::PENDING
    ) {}
}
