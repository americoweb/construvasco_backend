<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
if (! Schema::hasColumn('project_milestones', 'sort_order')) {
    Schema::table('project_milestones', function (Blueprint $table) {
        $table->unsignedInteger('sort_order')->default(0)->after('status');
    });
    echo "added sort_order\n";
} else {
    echo "sort_order exists\n";
}
