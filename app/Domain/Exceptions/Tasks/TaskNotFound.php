<?php

namespace App\Domain\Exceptions\Tasks;

use App\Domain\Exceptions\DomainException;

class TaskNotFound extends DomainException
{
    public function __construct(public int $taskId)
    {
        parent::__construct("Task {$taskId} not found");
    }
}
