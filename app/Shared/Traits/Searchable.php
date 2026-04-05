<?php

namespace App\Shared\Traits;

use Illuminate\Database\Eloquent\Builder;

trait Searchable
{
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $searchableFields = $this->getSearchableFields();
        
        return $query->where(function ($q) use ($term, $searchableFields) {
            foreach ($searchableFields as $field) {
                $q->orWhere($field, 'like', "%{$term}%");
            }
        });
    }

    protected function getSearchableFields(): array
    {
        return $this->searchable ?? [];
    }
}
