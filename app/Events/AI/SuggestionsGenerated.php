<?php

namespace App\Events\AI;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SuggestionsGenerated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $goal,
        public float $budget,
        public int $suggestionCount,
        public array $metadata = []
    ) {}
}
