<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Capsule\Manager as Capsule;

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

        return Capsule::transaction(function () use ($account, $amount, $description) {
            $lockedAccount = Account::lockForUpdate()->find($account->id);
            
            $currentBalance = $this->calculateBalance($lockedAccount);
            $newBalance = $currentBalance + $amount;

            return $lockedAccount->transactions()->create([
                'type' => 'deposit',
                'amount' => $amount,
                'description' => $description,
                'balance_after' => $newBalance
            ]);
        });
    }

    public function createWithdrawal(Account $account, float $amount, string $description = ''): Transaction
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero');
        }

        return Capsule::transaction(function () use ($account, $amount, $description) {
            $lockedAccount = Account::lockForUpdate()->find($account->id);

            $currentBalance = $this->calculateBalance($lockedAccount);

            if ($amount > $currentBalance) {
                throw new \InvalidArgumentException('Insufficient funds');
            }

            $newBalance = $currentBalance - $amount;

            return $lockedAccount->transactions()->create([
                'type' => 'withdrawal',
                'amount' => $amount,
                'description' => $description,
                'balance_after' => $newBalance
            ]);
        });
    }

    public function createTransfer(Account $from, Account $to, float $amount, string $description = ''): array
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero');
        }

        if ($from->id === $to->id) {
            throw new \InvalidArgumentException('Cannot transfer to the same account');
        }

        if ($from->currency !== $to->currency) {
            throw new \InvalidArgumentException('Cannot transfer between accounts with different currencies');
        }

        return Capsule::transaction(function () use ($from, $to, $amount, $description) {
            // Lock accounts in a deterministic order to avoid deadlocks
            $first = $from->id < $to->id ? $from : $to;
            $second = $from->id < $to->id ? $to : $from;

            Account::lockForUpdate()->find($first->id);
            Account::lockForUpdate()->find($second->id);

            $withdrawalDesc = $description ? "Transfer to Account {$to->id}: {$description}" : "Transfer to Account {$to->id}";
            $depositDesc = $description ? "Transfer from Account {$from->id}: {$description}" : "Transfer from Account {$from->id}";

            $withdrawal = $this->createWithdrawal($from, $amount, $withdrawalDesc);
            $deposit = $this->createDeposit($to, $amount, $depositDesc);

            return [
                'withdrawal' => $withdrawal,
                'deposit' => $deposit
            ];
        });
    }
}
