<?php

namespace App\Events\Candidate;

use App\Models\Candidate\Candidate;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CandidateCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Candidate $candidate
    ) {}
}
