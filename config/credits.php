<?php

return [
    'initial_grant_amount' => (int) env('CREDITS_INITIAL_GRANT', 5),
    'cost_per_generation' => (int) env('CREDITS_COST_PER_GENERATION', 1),
];
