<?php

namespace App\Console\Commands;

use App\Enums\CreditTransactionType;
use App\Models\Credits\CreditBalance;
use App\Models\Credits\CreditTransaction;
use App\Models\User;
use App\Services\Credits\CreditService;
use Illuminate\Console\Command;

class GrantRetroactiveCreditsCommand extends Command
{
    protected $signature = 'credits:grant-retroactive
                            {--amount=5 : Credits to grant per eligible user}
                            {--dry-run : List users without applying changes}';

    protected $description = 'Grant initial credits to customers who never received them';

    public function handle(CreditService $credits): int
    {
        $amount = (int) $this->option('amount');
        $dryRun = (bool) $this->option('dry-run');

        $users = User::role('customer', 'api')->get();
        $granted = 0;

        foreach ($users as $user) {
            $hasGrant = CreditTransaction::where('user_id', $user->id)
                ->where('type', CreditTransactionType::InitialGrant)
                ->exists();

            if ($hasGrant) {
                continue;
            }

            if ($dryRun) {
                $this->line("Would grant {$amount} credits to {$user->identifier}");
                $granted++;
                continue;
            }

            $balance = $credits->getOrCreateBalance($user);
            $balance->balance += $amount;
            $balance->save();

            CreditTransaction::create([
                'user_id' => $user->id,
                'type' => CreditTransactionType::InitialGrant,
                'amount' => $amount,
                'balance_after' => $balance->balance,
                'notes' => 'Créditos retroactivos (comando artisan)',
            ]);

            $this->info("Granted {$amount} credits to {$user->identifier}");
            $granted++;
        }

        $this->info($dryRun ? "Dry run: {$granted} user(s) eligible." : "Done. Granted to {$granted} user(s).");

        return self::SUCCESS;
    }
}
