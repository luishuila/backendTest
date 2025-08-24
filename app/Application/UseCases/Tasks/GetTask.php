<?php 
namespace App\Application\UseCases\Tasks;

use App\Domain\Exceptions\Tasks\TaskNotFound;
use App\Domain\Repositories\TaskRepositoryInterface;

final class GetTask
{
    public function __construct(private TaskRepositoryInterface $repo) {}
    public function __invoke(int $id)
    {
        $task = $this->repo->find($id);
        if (!$task) {
            throw new TaskNotFound($id); 
        }
        return $task;
    }
}
