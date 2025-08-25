<?php 
namespace App\Application\UseCases\Tasks;

use App\Application\Dto\Tasks\CreateTaskDto;
use App\Domain\Entities\Task;
use App\Domain\Repositories\TaskRepositoryInterface;

final class CreateTask
{
    public function __construct(private TaskRepositoryInterface $repo) {}
    public function __invoke(CreateTaskDto $dto): Task
    {
        
        return $this->repo->create(new Task(null,$dto->title,$dto->description,$dto->status));
    }
}
