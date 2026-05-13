<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;

class TransactionService
{
    public function calculateBalance(Account $account): float
    {
        $deposits = $account->transactions()->where('type', 'deposit')->sum('amount');
        $withdrawals = $account->transactions()->where('type', 'withdrawal')->sum('amount');
        
        return (float)($deposits - $withdrawals);
    }

    public function createDeposit(Account $account, float $amount, string $description = ''): Transaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero');
        }

        $currentBalance = $this->calculateBalance($account);
        $newBalance = $currentBalance + $amount;

        return $account->transactions()->create([
            'type' => 'deposit',
            'amount' => $amount,
            'description' => $description,
            'balance_after' => $newBalance
        ]);
    }

    public function createWithdrawal(Account $account, float $amount, string $description = ''): Transaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero');
        }

        $currentBalance = $this->calculateBalance($account);

        if ($amount > $currentBalance) {
            throw new \InvalidArgumentException('Insufficient funds');
        }

        $newBalance = $currentBalance - $amount;

        return $account->transactions()->create([
            'type' => 'withdrawal',
            'amount' => $amount,
            'description' => $description,
            'balance_after' => $newBalance
        ]);
    }
}
