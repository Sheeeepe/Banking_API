<?php

namespace App\Controllers;

use App\Models\Account;
use App\Models\Transaction;
use App\Services\TransactionService;
use App\Services\ExchangeService;

class AccountController
{
    private TransactionService $transactionService;
    private ExchangeService $exchangeService;

    public function __construct()
    {
        $this->transactionService = new TransactionService();
        $this->exchangeService = new ExchangeService();
    }

    private function jsonResponse($response, $data, int $status = 200)
    {
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function getTransactions($request, $response, $args)
    {
        $accountId = (int) $args['id'];

        $account = Account::find($accountId);
        if (!$account) {
            return $this->jsonResponse($response, ['error' => 'Account not found'], 404);
        }

        $transactions = $account->transactions()->orderBy('created_at', 'desc')->get();

        return $this->jsonResponse($response, [
            'account_id' => $account->id,
            'currency' => $account->currency,
            'transactions' => $transactions
        ]);
    }

    public function getTransaction($request, $response, $args)
    {
        $accountId = (int) $args['id'];
        $transactionId = (int) $args['transactionId'];

        $transaction = Transaction::where('id', $transactionId)
            ->where('account_id', $accountId)
            ->first();

        if (!$transaction) {
            return $this->jsonResponse($response, ['error' => 'Transaction not found'], 404);
        }

        return $this->jsonResponse($response, $transaction);
    }

    public function createDeposit($request, $response, $args)
    {
        $accountId = (int) $args['id'];
        $data = $request->getParsedBody();

        $amount = isset($data['amount']) ? (float) $data['amount'] : 0;
        $description = $data['description'] ?? '';

        $account = Account::find($accountId);
        if (!$account) {
            return $this->jsonResponse($response, ['error' => 'Account not found'], 404);
        }

        try {
            $transaction = $this->transactionService->createDeposit($account, $amount, $description);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 400);
        }

        return $this->jsonResponse($response, [
            'message' => 'Deposit successful',
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'type' => 'deposit',
            'amount' => $transaction->amount,
            'description' => $transaction->description,
            'balance_after' => $transaction->balance_after
        ], 201);
    }

    public function createWithdrawal($request, $response, $args)
    {
        $accountId = (int) $args['id'];
        $data = $request->getParsedBody();

        $amount = isset($data['amount']) ? (float) $data['amount'] : 0;
        $description = $data['description'] ?? '';

        $account = Account::find($accountId);
        if (!$account) {
            return $this->jsonResponse($response, ['error' => 'Account not found'], 404);
        }

        try {
            $transaction = $this->transactionService->createWithdrawal($account, $amount, $description);
        } catch (\InvalidArgumentException $e) {
            $status = $e->getMessage() === 'Insufficient funds' ? 422 : 400;
            return $this->jsonResponse($response, ['error' => $e->getMessage()], $status);
        }

        return $this->jsonResponse($response, [
            'message' => 'Withdrawal successful',
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'type' => 'withdrawal',
            'amount' => $transaction->amount,
            'description' => $transaction->description,
            'balance_after' => $transaction->balance_after
        ], 201);
    }

    public function updateTransaction($request, $response, $args)
    {
        $accountId = (int) $args['id'];
        $transactionId = (int) $args['transactionId'];
        $data = $request->getParsedBody();

        if (!isset($data['description'])) {
            return $this->jsonResponse($response, ['error' => 'Description is required'], 400);
        }

        $transaction = Transaction::where('id', $transactionId)
            ->where('account_id', $accountId)
            ->first();

        if (!$transaction) {
            return $this->jsonResponse($response, ['error' => 'Transaction not found'], 404);
        }

        $transaction->update(['description' => $data['description']]);

        return $this->jsonResponse($response, [
            'message' => 'Transaction updated successfully',
            'transaction_id' => $transaction->id,
            'description' => $transaction->description
        ]);
    }

    public function deleteTransaction($request, $response, $args)
    {
        $accountId = (int) $args['id'];
        $transactionId = (int) $args['transactionId'];

        $transaction = Transaction::where('id', $transactionId)
            ->where('account_id', $accountId)
            ->first();

        if (!$transaction) {
            return $this->jsonResponse($response, ['error' => 'Transaction not found'], 404);
        }

        // Can only delete the last transaction
        $lastTransaction = Transaction::where('account_id', $accountId)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$lastTransaction || $lastTransaction->id != $transactionId) {
            return $this->jsonResponse($response, ['error' => 'Can only delete the last transaction'], 422);
        }

        $transaction->delete();

        return $this->jsonResponse($response, [
            'message' => 'Transaction deleted successfully',
            'transaction_id' => $transactionId
        ]);
    }

    public function getBalance($request, $response, $args)
    {
        $accountId = (int) $args['id'];

        $account = Account::find($accountId);
        if (!$account) {
            return $this->jsonResponse($response, ['error' => 'Account not found'], 404);
        }

        $balance = $this->transactionService->calculateBalance($account);

        return $this->jsonResponse($response, [
            'account_id' => $account->id,
            'owner_name' => $account->owner_name,
            'currency' => $account->currency,
            'balance' => $balance
        ]);
    }

    public function convertFiat($request, $response, $args)
    {
        $accountId = (int) $args['id'];
        $params = $request->getQueryParams();
        $to = strtoupper($params['to'] ?? '');

        if (!$to) {
            return $this->jsonResponse($response, ['error' => 'Missing target currency'], 400);
        }

        $account = Account::find($accountId);
        if (!$account) {
            return $this->jsonResponse($response, ['error' => 'Account not found'], 404);
        }

        $balance = $this->transactionService->calculateBalance($account);

        try {
            $result = $this->exchangeService->convertFiat($account, $balance, $to);
            $result['account_id'] = $account->id;
            return $this->jsonResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 502);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 400);
        }
    }

    public function convertCrypto($request, $response, $args)
    {
        $accountId = (int) $args['id'];
        $params = $request->getQueryParams();
        $to = strtoupper($params['to'] ?? '');

        if (!$to) {
            return $this->jsonResponse($response, ['error' => 'Missing target crypto'], 400);
        }

        $account = Account::find($accountId);
        if (!$account) {
            return $this->jsonResponse($response, ['error' => 'Account not found'], 404);
        }

        $balance = $this->transactionService->calculateBalance($account);

        try {
            $result = $this->exchangeService->convertCrypto($account, $balance, $to);
            $result['account_id'] = $account->id;
            return $this->jsonResponse($response, $result);
        } catch (\RuntimeException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 502);
        } catch (\InvalidArgumentException $e) {
            return $this->jsonResponse($response, ['error' => $e->getMessage()], 400);
        }
    }

    public function createAccount($request, $response, $args)
    {
        $data = json_decode((string) $request->getBody(), true);

        if (!isset($data["owner_name"]) || !isset($data["currency"])) {
            return $this->jsonResponse($response, ['error' => 'Owner name and currency must be set'], 400);
        }

        $account = Account::create([
            'owner_name' => $data['owner_name'],
            'currency' => $data['currency']
        ]);

        return $this->jsonResponse($response, [
            'message' => 'Account created successfully',
            'accountId' => $account->id,
            'owner_name' => $account->owner_name,
            'currency' => $account->currency
        ]);
    }
}
