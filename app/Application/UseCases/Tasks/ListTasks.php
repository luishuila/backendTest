<?php 
namespace App\Application\UseCases\Tasks;

use App\Domain\Repositories\TaskRepositoryInterface;

final class ListTasks
{
    public function __construct(private TaskRepositoryInterface $repo) {}
    public function __invoke(): array { return $this->repo->all(); }
}
