<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Task;

interface TaskRepositoryInterface
{

    public function all(): array;
    public function find(int $id): ?Task;
    public function create(Task $task): Task;
    public function update(Task $task): Task;
    public function delete(int $id): void;
}
