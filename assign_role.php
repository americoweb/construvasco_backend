<?php
use App\Models\User;
use App\Models\Settings\Tenant;

$user = User::where('identifier', 'admin@admin.com')->first();
$tenant = Tenant::find(1);

if ($user && $tenant) {
    app(\App\Services\TenantService::class)->addUserToTenant(
        $tenant,
        $user,
        'admin',
        true
    );
    echo "User admin@admin.com is now an admin on Tenant 1.\n";
} else {
    echo "User or Tenant not found.\n";
}
