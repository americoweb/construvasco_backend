<?php

namespace App\Repositories\JobCard\Eloquent;

use App\Models\JobCard\JobCard;
use App\Repositories\JobCard\Contracts\JobCardRepositoryInterface;
use App\Enums\JobCard\JobCardStatus;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentJobCardRepository implements JobCardRepositoryInterface
{
    public function __construct(
        protected JobCard $model
    ) {}

    public function findById(int $id): ?JobCard
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?JobCard
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function findByJobNumber(string $jobNumber): ?JobCard
    {
        return $this->model->where('job_number', $jobNumber)->first();
    }

    public function create(array $data): JobCard
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): JobCard
    {
        $jobCard = $this->findById($id);
        $jobCard->update($data);
        return $jobCard->fresh();
    }

    public function delete(int $id): bool
    {
        $jobCard = $this->findById($id);
        return $jobCard->delete();
    }

    public function getWithRelations(int $id): ?JobCard
    {
        return $this->model
            ->with(['client', 'creator', 'designer', 'items', 'files.uploader', 'feedback.author'])
            ->find($id);
    }

    public function getByStatus(JobCardStatus $status): Collection
    {
        return $this->model
            ->byStatus($status)
            ->with(['client', 'designer'])
            ->byPriority()
            ->get();
    }

    public function getActive(): Collection
    {
        return $this->model
            ->active()
            ->with(['client', 'designer'])
            ->byPriority()
            ->get();
    }

    /**
     * Returns job cards grouped by status for Kanban board,
     * each column ordered by priority_score desc.
     */
    public function getKanbanBoard(): array
    {
        $board = [];

        foreach (JobCardStatus::kanbanColumns() as $status) {
            $board[$status->value] = $this->model
                ->byStatus($status)
                ->with(['client', 'designer'])
                ->byPriority()
                ->get();
        }

        // Extras
        $board['draft'] = $this->model
            ->byStatus(JobCardStatus::DRAFT)
            ->with(['client', 'designer'])
            ->byPriority()
            ->get();

        $board['done'] = $this->model
            ->byStatus(JobCardStatus::DONE)
            ->with(['client', 'designer'])
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();

        $board['cancelled'] = $this->model
            ->byStatus(JobCardStatus::CANCELLED)
            ->with(['client', 'designer'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return $board;
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['client', 'designer']);

        $this->applyFilters($query, $filters);

        return $query->orderByDesc('priority_score')
                     ->orderBy('deadline')
                     ->paginate($perPage);
    }

    public function countActiveOverrides(): int
    {
        return $this->model
            ->active()
            ->where('priority_override', true)
            ->count();
    }

    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['client_tier'])) {
            $query->where('client_tier', $filters['client_tier']);
        }

        if (!empty($filters['assigned_designer_id'])) {
            $query->where('assigned_designer_id', $filters['assigned_designer_id']);
        }

        if (!empty($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }

        if (isset($filters['priority_override'])) {
            $query->where('priority_override', (bool) $filters['priority_override']);
        }

        if (!empty($filters['deadline_from'])) {
            $query->whereDate('deadline', '>=', $filters['deadline_from']);
        }

        if (!empty($filters['deadline_to'])) {
            $query->whereDate('deadline', '<=', $filters['deadline_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('title', 'like', "%{$filters['search']}%")
                  ->orWhere('job_number', 'like', "%{$filters['search']}%")
                  ->orWhere('description', 'like', "%{$filters['search']}%");
            });
        }
    }
}
