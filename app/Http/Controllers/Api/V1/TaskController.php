<?php

namespace App\Http\Controllers\Api\V1;

use App\Application\DTO\Tasks\CreateTaskDTO;
use App\Application\DTO\Tasks\UpdateTaskDTO;
use App\Application\UseCases\Tasks\{ListTasks,GetTask,CreateTask,UpdateTask,DeleteTask};
use App\Domain\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tasks\TaskStoreRequest;
use App\Http\Requests\Tasks\TaskUpdateRequest;
use App\Http\Resources\TaskResource;
use Illuminate\Http\Response;

class TaskController extends Controller
{
    public function index(ListTasks $uc)
    {
        return TaskResource::collection(collect(($uc)()));
    }

    public function show(int $id, GetTask $uc)
    {
        return new TaskResource(($uc)($id));
    }

    public function store(TaskStoreRequest $req, CreateTask $uc)
    {
        $dto = new CreateTaskDTO(
            title: $req->string('title'),
            description: $req->input('description'),
            status: TaskStatus::from($req->input('status', 'PENDING'))
        );

        return (new TaskResource(($uc)($dto)))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(TaskUpdateRequest $req, int $id, UpdateTask $uc)
    {
        $dto = new UpdateTaskDTO(
            id: $id,
            title: $req->string('title'),
            description: $req->input('description'),
            status: TaskStatus::from($req->input('status', 'pending'))
        );

        return new TaskResource(($uc)($dto));
    }

    public function destroy(int $id, DeleteTask $uc)
    {
        ($uc)($id);
        return response()->noContent();
    }
}
