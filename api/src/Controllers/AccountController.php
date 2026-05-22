<?php

namespace App\Controllers;

use App\Models\Account;
use App\Services\BalanceService;

class AccountController extends BaseController
{
    private const ALLOWED_CURRENCIES = [
        'AUD', 'BGN', 'BRL', 'CAD', 'CHF', 'CNY', 'CZK', 'DKK',
        'EUR', 'GBP', 'HKD', 'HUF', 'IDR', 'ILS', 'INR', 'ISK',
        'JPY', 'KRW', 'MXN', 'MYR', 'NOK', 'NZD', 'PHP', 'PLN',
        'RON', 'SEK', 'SGD', 'THB', 'TRY', 'USD', 'ZAR',
    ];

    private BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function getAccounts($request, $response, $args)
    {
        $accounts = Account::all();
        return $this->jsonResponse($response, $accounts);
    }

    public function createAccount($request, $response, $args)
    {
        $data = json_decode((string) $request->getBody(), true);

        if (!isset($data["owner_name"]) || !isset($data["currency"])) {
            return $this->jsonResponse($response, ['error' => 'Owner name and currency must be set'], 400);
        }

        $currency = strtoupper(trim($data['currency']));
        if (!in_array($currency, self::ALLOWED_CURRENCIES, true)) {
            return $this->jsonResponse($response, ['error' => 'Invalid currency code'], 422);
        }

        $account = Account::create([
            'owner_name' => $data['owner_name'],
            'currency'   => $currency,
        ]);

        return $this->jsonResponse($response, [
            'message' => 'Account created successfully',
            'accountId' => $account->id,
            'owner_name' => $account->owner_name,
            'currency' => $account->currency
        ], 201);
    }

    public function getAccount($request, $response, $args)
    {
        $account = $request->getAttribute('account');
        return $this->jsonResponse($response, $account);
    }

    public function updateAccount($request, $response, $args)
    {
        $account = $request->getAttribute('account');
        $data = json_decode((string) $request->getBody(), true);

        if (isset($data['owner_name'])) {
            $account->owner_name = $data['owner_name'];
        }

        $account->save();

        return $this->jsonResponse($response, [
            'message' => 'Account updated successfully',
            'account' => $account
        ]);
    }

    public function deleteAccount($request, $response, $args)
    {
        $account = $request->getAttribute('account');

        $balance = $this->balanceService->calculateBalance($account);
        if ($balance != 0) {
            return $this->jsonResponse($response, ['error' => 'Cannot delete account with non-zero balance'], 422);
        }

        $account->transactions()->delete();
        $account->delete();

        return $this->jsonResponse($response, [
            'message' => 'Account deleted successfully',
            'account_id' => $account->id
        ]);
    }

    public function getBalance($request, $response, $args)
    {
        $account = $request->getAttribute('account');
        $balance = $this->balanceService->calculateBalance($account);

        return $this->jsonResponse($response, [
            'account_id' => $account->id,
            'owner_name' => $account->owner_name,
            'currency' => $account->currency,
            'balance' => $balance
        ]);
    }
}
