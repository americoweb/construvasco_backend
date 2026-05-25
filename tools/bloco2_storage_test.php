<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$g = \App\Models\AI\AiGeneration::find(12);
$path = $g->image_path ?? '';
$public = public_path('storage/' . ltrim(str_replace('renders/', '', $path), '/'));
$full = storage_path('app/public/' . ltrim($path, '/'));
echo "image_path={$path}\n";
echo "storage_file_exists=" . (is_file($full) ? 'yes' : 'no') . "\n";
echo "symlink_exists=" . (is_link(public_path('storage')) || is_dir(public_path('storage')) ? 'yes' : 'no') . "\n";
echo "url={$g->image_url}\n";
