<?php 
namespace App\Application\UseCases\Tasks;

use App\Application\Dto\Tasks\UpdateTaskDto;
use App\Domain\Entities\Task;
use App\Domain\Repositories\TaskRepositoryInterface;

final class UpdateTask
{
    public function __construct(private TaskRepositoryInterface $repo) {}
    public function __invoke(UpdateTaskDto $dto): Task
    {
        return $this->repo->update(new Task($dto->id,$dto->title,$dto->description,$dto->status));
    }
}
