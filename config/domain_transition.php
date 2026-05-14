<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Transitional Domain Strategy
    |--------------------------------------------------------------------------
    |
    | Construvasco currently keeps the Amazing commercial backbone
    | (Product -> Design -> Cart -> Checkout -> Order -> JobCard) while
    | progressively replacing print semantics with construction semantics.
    |
    */
    'legacy_modules' => [
        'product' => ['status' => 'reused', 'target_semantics' => 'construction_service_catalog'],
        'design' => ['status' => 'reused', 'target_semantics' => 'construction_brief_and_proposal'],
        'cart' => ['status' => 'reused', 'target_semantics' => 'service_quote_cart'],
        'checkout' => ['status' => 'reused', 'target_semantics' => 'project_request_checkout'],
        'order' => ['status' => 'reused', 'target_semantics' => 'project_request_order'],
        'job_card' => ['status' => 'reused', 'target_semantics' => 'execution_assignment_workcard'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Order Workflow Ownership
    |--------------------------------------------------------------------------
    |
    | Role ownership helps keep responsibilities explicit until dedicated
    | project workflow entities fully replace the transition flow.
    |
    */
    'order_role_transitions' => [
        'new' => ['owner' => ['system', 'client'], 'next' => ['triaged', 'cancelled']],
        'triaged' => ['owner' => ['admin', 'reception'], 'next' => ['assigned', 'cancelled']],
        'assigned' => ['owner' => ['admin', 'reception'], 'next' => ['in_design', 'cancelled']],
        'in_design' => ['owner' => ['technician', 'architect'], 'next' => ['awaiting_client', 'cancelled']],
        'awaiting_client' => ['owner' => ['client'], 'next' => ['approved', 'in_design', 'cancelled']],
        'approved' => ['owner' => ['client', 'admin'], 'next' => ['in_execution', 'cancelled']],
        'in_execution' => ['owner' => ['technician', 'architect'], 'next' => ['delivered', 'cancelled']],
        'delivered' => ['owner' => ['admin', 'system'], 'next' => []],
        'cancelled' => ['owner' => ['admin', 'client', 'system'], 'next' => []],
    ],

    /*
    |--------------------------------------------------------------------------
    | Legacy Removal Gates
    |--------------------------------------------------------------------------
    |
    | Legacy modules can only be retired when all required gates are true.
    |
    */
    'retirement_gates' => [
        'construction_flow_live' => false,
        'admin_metrics_on_construction' => false,
        'payments_detached_from_print_assumptions' => false,
        'critical_regression_tests_green' => false,
    ],
];
