<?php 
namespace App\Application\UseCases\Tasks;

use App\Domain\Repositories\TaskRepositoryInterface;

final class DeleteTask
{
    public function __construct(private TaskRepositoryInterface $repo) {}
    public function __invoke(int $id): void { $this->repo->delete($id); }
}
