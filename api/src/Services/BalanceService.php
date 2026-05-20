<?php

namespace App\Services;

use App\Models\Account;

class BalanceService
{
    public function calculateBalance(Account $account): float
    {
        $deposits = $account->transactions()->where('type', 'deposit')->sum('amount');
        $withdrawals = $account->transactions()->where('type', 'withdrawal')->sum('amount');

        return (float) ($deposits - $withdrawals);
    }
}
