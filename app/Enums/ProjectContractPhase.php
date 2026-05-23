<?php

namespace App\Enums;

enum ProjectContractPhase: string
{
    case Architecture = 'architecture';
    case ExecutionQuote = 'execution_quote';
    case Construction = 'construction';
    case Completed = 'completed';
    case Closed = 'closed';
}
