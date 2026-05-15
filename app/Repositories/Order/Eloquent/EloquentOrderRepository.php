<?php

namespace App\Repositories\Order\Eloquent;

use App\Enums\Order\OrderStatus;
use App\Models\Order\Order;
use App\Repositories\Order\Contracts\OrderRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function __construct(
        protected Order $model
    ) {}

    public function findById(int $id): ?Order
    {
        return $this->model->find($id);
    }

    public function findByUuid(string $uuid): ?Order
    {
        return $this->model->where('uuid', $uuid)->first();
    }

    public function findByOrderNumber(string $orderNumber): ?Order
    {
        return $this->model->where('order_number', $orderNumber)->first();
    }

    public function create(array $data): Order
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data): Order
    {
        $order = $this->findById($id);
        $order->update($data);
        return $order->fresh();
    }

    public function delete(int $id): bool
    {
        $order = $this->findById($id);
        return $order->delete();
    }

    public function getByUser(int $userId): Collection
    {
        return $this->model->byUser($userId)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function paginateByUser(int $userId, int $perPage = 15, ?string $status = null, ?string $search = null): LengthAwarePaginator
    {
        $allowedStatuses = array_map(
            static fn (OrderStatus $s) => $s->value,
            OrderStatus::cases()
        );

        $query = $this->model->byUser($userId)
            ->with('items')
            ->orderBy('created_at', 'desc');

        if ($status !== null && $status !== '' && in_array($status, $allowedStatuses, true)) {
            $query->where('status', $status);
        }

        if ($search !== null && $search !== '') {
            $term = '%' . addcslashes($search, '%_\\') . '%';
            $query->where(function ($q) use ($term) {
                $q->where('order_number', 'like', $term)
                    ->orWhere('shipping_name', 'like', $term)
                    ->orWhere('billing_email', 'like', $term);
            });
        }

        return $query->paginate($perPage);
    }

    public function getBySession(string $sessionId): Collection
    {
        return $this->model->bySession($sessionId)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getWithItems(int $id): ?Order
    {
        return $this->model->with(['items', 'statusHistory', 'jobCard'])->find($id);
    }

    public function getPending(): Collection
    {
        return $this->model->pending()
            ->with('items')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    public function getActive(): Collection
    {
        return $this->model->active()
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['items', 'jobCard']);
        
        $this->applyFilters($query, $filters);
        
        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('order_number', 'like', "%{$filters['search']}%")
                  ->orWhere('shipping_name', 'like', "%{$filters['search']}%")
                  ->orWhere('billing_email', 'like', "%{$filters['search']}%");
            });
        }
    }
}
