<?php

namespace App\Controllers;

use App\Http\TransactionQueryParams;
use App\Models\Transaction;
use App\Services\TransactionService;

class TransactionController extends BaseController
{
    private TransactionService $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function getTransactions($request, $response, $args)
    {
        $account = $request->getAttribute('account');
        $params  = TransactionQueryParams::fromRequest($request);

        if ($params->limit === 0) {
            $all = $this->buildTransactionQuery($account, $params)->get();
            return $this->jsonResponse($response, [
                'account_id'   => $account->id,
                'currency'     => $account->currency,
                'total'        => $all->count(),
                'page'         => 1,
                'limit'        => 0,
                'pages'        => 1,
                'transactions' => $all->values(),
            ]);
        }

        $paginated = $this->buildTransactionQuery($account, $params)
            ->paginate($params->limit, ['*'], 'page', $params->page);

        return $this->jsonResponse($response, [
            'account_id'   => $account->id,
            'currency'     => $account->currency,
            'total'        => $paginated->total(),
            'page'         => $paginated->currentPage(),
            'limit'        => $params->limit,
            'pages'        => $paginated->lastPage(),
            'transactions' => $paginated->items(),
        ]);
    }

    private function buildTransactionQuery($account, TransactionQueryParams $params)
    {
        $query = $account->transactions();

        if ($params->type !== null) {
            $query->where('type', $params->type);
        }

        if ($params->from !== null) {
            $query->where('created_at', '>=', $params->from . ' 00:00:00');
        }

        if ($params->to !== null) {
            $query->where('created_at', '<=', $params->to . ' 23:59:59');
        }

        return $query->orderBy($params->sort, $params->order);
    }

    public function getTransaction($request, $response, $args)
    {
        $account = $request->getAttribute('account');
        $transactionId = (int) $args['transactionId'];

        $transaction = $account->transactions()->where('id', $transactionId)->first();

        if (!$transaction) {
            return $this->jsonResponse($response, ['error' => 'Transaction not found'], 404);
        }

        return $this->jsonResponse($response, $transaction);
    }

    public function createDeposit($request, $response, $args)
    {
        $account = $request->getAttribute('account');
        $data = $request->getParsedBody();

        $amount = isset($data['amount']) ? (float) $data['amount'] : 0;
        $description = $data['description'] ?? '';

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
        $account = $request->getAttribute('account');
        $data = $request->getParsedBody();

        $amount = isset($data['amount']) ? (float) $data['amount'] : 0;
        $description = $data['description'] ?? '';

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

    public function createTransfer($request, $response, $args)
    {
        $fromAccount = $request->getAttribute('account');
        $data = $request->getParsedBody();

        if (!isset($data['target_account_id'])) {
            return $this->jsonResponse($response, ['error' => 'Target account ID is required'], 400);
        }

        $toAccountId = (int) $data['target_account_id'];
        $amount = isset($data['amount']) ? (float) $data['amount'] : 0;
        $description = $data['description'] ?? '';

        $toAccount = \App\Models\Account::find($toAccountId);
        if (!$toAccount) {
            return $this->jsonResponse($response, ['error' => 'Target account not found'], 404);
        }

        try {
            $transfer = $this->transactionService->createTransfer($fromAccount, $toAccount, $amount, $description);
        } catch (\InvalidArgumentException $e) {
            $status = in_array($e->getMessage(), ['Insufficient funds']) ? 422 : 400;
            return $this->jsonResponse($response, ['error' => $e->getMessage()], $status);
        }

        return $this->jsonResponse($response, [
            'message' => 'Transfer successful',
            'withdrawal' => $transfer['withdrawal'],
            'deposit' => $transfer['deposit']
        ], 201);
    }

    public function updateTransaction($request, $response, $args)
    {
        $account = $request->getAttribute('account');
        $transactionId = (int) $args['transactionId'];
        $data = $request->getParsedBody();

        if (!isset($data['description'])) {
            return $this->jsonResponse($response, ['error' => 'Description is required'], 400);
        }

        $transaction = $account->transactions()->where('id', $transactionId)->first();

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
        $account = $request->getAttribute('account');
        $transactionId = (int) $args['transactionId'];

        $transaction = $account->transactions()->where('id', $transactionId)->first();

        if (!$transaction) {
            return $this->jsonResponse($response, ['error' => 'Transaction not found'], 404);
        }

        // Can only delete the last transaction
        $lastTransaction = $account->transactions()->orderBy('created_at', 'desc')->first();

        if (!$lastTransaction || $lastTransaction->id != $transactionId) {
            return $this->jsonResponse($response, ['error' => 'Can only delete the last transaction'], 422);
        }

        $transaction->delete();

        return $this->jsonResponse($response, [
            'message' => 'Transaction deleted successfully',
            'transaction_id' => $transactionId
        ]);
    }
}
