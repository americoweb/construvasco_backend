<?php

namespace App\Services\Credits;

use App\Enums\CreditTransactionType;
use App\Models\Credits\CreditBalance;
use App\Models\Credits\CreditPackage;
use App\Models\Credits\CreditTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreditService
{
    public function grantInitialCredits(User $user): CreditBalance
    {
        $amount = (int) config('credits.initial_grant_amount', 5);

        return DB::transaction(function () use ($user, $amount) {
            $balance = $this->getOrCreateBalance($user);
            $balance->balance += $amount;
            $balance->save();

            CreditTransaction::create([
                'user_id' => $user->id,
                'type' => CreditTransactionType::InitialGrant,
                'amount' => $amount,
                'balance_after' => $balance->balance,
                'notes' => 'Créditos de boas-vindas',
            ]);

            return $balance->fresh();
        });
    }

    public function consume(User $user, int $amount, string $referenceType, $referenceId): CreditBalance
    {
        if ($amount <= 0) {
            throw new RuntimeException('Invalid credit amount.');
        }

        return DB::transaction(function () use ($user, $amount, $referenceType, $referenceId) {
            $balance = $this->getOrCreateBalance($user);
            if (!$balance->hasEnough($amount)) {
                throw new RuntimeException('Insufficient credits.');
            }

            $balance->balance -= $amount;
            $balance->lifetime_consumed += $amount;
            $balance->save();

            CreditTransaction::create([
                'user_id' => $user->id,
                'type' => CreditTransactionType::Consumption,
                'amount' => -$amount,
                'balance_after' => $balance->balance,
                'reference_type' => $referenceType,
                'reference_id' => (string) $referenceId,
            ]);

            return $balance->fresh();
        });
    }

    public function refund(User $user, int $amount, string $referenceType, $referenceId): CreditBalance
    {
        return DB::transaction(function () use ($user, $amount, $referenceType, $referenceId) {
            $balance = $this->getOrCreateBalance($user);
            $balance->balance += $amount;
            $balance->lifetime_consumed = max(0, $balance->lifetime_consumed - $amount);
            $balance->save();

            CreditTransaction::create([
                'user_id' => $user->id,
                'type' => CreditTransactionType::Refund,
                'amount' => $amount,
                'balance_after' => $balance->balance,
                'reference_type' => $referenceType,
                'reference_id' => (string) $referenceId,
            ]);

            return $balance->fresh();
        });
    }

    public function purchase(User $user, CreditPackage $package, string $paymentRef): CreditBalance
    {
        $existing = CreditTransaction::where('user_id', $user->id)
            ->where('type', CreditTransactionType::Purchase)
            ->where('reference_id', $paymentRef)
            ->exists();

        if ($existing) {
            return $this->getOrCreateBalance($user);
        }

        return DB::transaction(function () use ($user, $package, $paymentRef) {
            $balance = $this->getOrCreateBalance($user);
            $balance->balance += $package->credits_amount;
            $balance->lifetime_purchased += $package->credits_amount;
            $balance->save();

            CreditTransaction::create([
                'user_id' => $user->id,
                'type' => CreditTransactionType::Purchase,
                'amount' => $package->credits_amount,
                'balance_after' => $balance->balance,
                'reference_type' => 'payment',
                'reference_id' => $paymentRef,
                'notes' => "Pacote: {$package->name}",
            ]);

            return $balance->fresh();
        });
    }

    public function getBalance(User $user): int
    {
        return $this->getOrCreateBalance($user)->balance;
    }

    public function getHistory(User $user, int $limit = 50)
    {
        return CreditTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getOrCreateBalance(User $user): CreditBalance
    {
        return CreditBalance::firstOrCreate(
            ['user_id' => $user->id],
            ['balance' => 0, 'lifetime_purchased' => 0, 'lifetime_consumed' => 0]
        );
    }
}
