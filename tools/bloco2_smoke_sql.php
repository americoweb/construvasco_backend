<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$user = \App\Models\User::where('identifier', 'cliente@construvasco.co.mz')->first();
$pr = \App\Models\Construction\ProjectRequest::where('user_id', $user->id)->latest('id')->first();

echo "PR#{$pr->id} status={$pr->status->value} approved={$pr->approved_ai_generation_id}\n";

$gens = \App\Models\AI\AiGeneration::where('project_request_id', $pr->id)->orderBy('id')->get();
foreach ($gens as $g) {
    echo "  gen#{$g->id} status={$g->status->value} parent={$g->parent_generation_id}\n";
}

$mail = \Illuminate\Support\Facades\DB::table('email_dispatches')
    ->where('event_type', 'project_request_submitted')
    ->where('related_entity_type', 'project_request')
    ->where('related_entity_id', $pr->id)
    ->orderByDesc('id')
    ->first();

echo $mail ? "email_dispatch: id={$mail->id} sent_at={$mail->sent_at}\n" : "email_dispatch: not found\n";
