<?php

namespace App\Infrastructure\Persistence\Eloquent\Repositories;

use App\Domain\Entities\Task;
use App\Domain\Enums\TaskStatus;
use App\Domain\Exceptions\Tasks\TaskNotFound;
use App\Domain\Repositories\TaskRepositoryInterface;
use App\Infrastructure\Persistence\Eloquent\Models\TaskModel;

final class EloquentTaskRepository implements TaskRepositoryInterface
{
    public function all(): array
    {
        return array_map($this->toEntity(...), TaskModel::query()->orderBy('id','desc')->get()->all());
    }

    public function find(int $id): ?Task
    {
        $m = TaskModel::find($id);
        return $m ? $this->toEntity($m) : null;
    }

    public function create(Task $task): Task
    {
        $m = TaskModel::create([
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status->value,
        ]);
        return $this->toEntity($m);
    }

    public function update(Task $task): Task
    {
        $m = TaskModel::find($task->id);
        if (!$m) throw new TaskNotFound('Task not found');
        $m->update([
            'title' => $task->title,
            'description' => $task->description,
            'status' => $task->status->value,
        ]);
        return $this->toEntity($m);
    }

    public function delete(int $id): void
    {
        $m = TaskModel::find($id);
        if (!$m) throw new TaskNotFound('Task not found');
        $m->delete();
    }

    private function toEntity(TaskModel $m): Task
    {
        return new Task(
            id: $m->id,
            title: $m->title,
            description: $m->description,
            status: TaskStatus::from($m->status)
        );
    }
}
