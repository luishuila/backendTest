<?php

namespace App\Application\Dto\Tasks;

use App\Domain\Enums\TaskStatus;

class UpdateTaskDto
{
    public function __construct(
        public int $id,
        public string $title,
        public ?string $description,
        public TaskStatus $status
    ) {}
}
