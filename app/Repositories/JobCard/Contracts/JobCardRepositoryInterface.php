<?php

namespace App\Repositories\JobCard\Contracts;

use App\Models\JobCard\JobCard;
use App\Enums\JobCard\JobCardStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface JobCardRepositoryInterface
{
    public function findById(int $id): ?JobCard;

    public function findByUuid(string $uuid): ?JobCard;

    public function findByJobNumber(string $jobNumber): ?JobCard;

    public function create(array $data): JobCard;

    public function update(int $id, array $data): JobCard;

    public function delete(int $id): bool;

    public function getWithRelations(int $id): ?JobCard;

    public function getByStatus(JobCardStatus $status): Collection;

    public function getActive(): Collection;

    public function getKanbanBoard(): array; // keyed by status value

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    public function countActiveOverrides(): int;
}
