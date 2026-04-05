<?php

namespace App\Events\Candidate;

use App\Models\Candidate\Resume;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResumeUploaded
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Resume $resume
    ) {}
}
