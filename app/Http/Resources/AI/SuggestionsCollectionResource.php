<?php

namespace App\Http\Resources\AI;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SuggestionsCollectionResource extends ResourceCollection
{
    public $collects = SuggestionResource::class;

    public function toArray(Request $request): array
    {
        return [
            'suggestions' => $this->collection,
            'total_suggestions' => $this->collection->count(),
            'has_bundles' => $this->collection->contains(fn($s) => $s->resource->isBundle()),
        ];
    }
}
