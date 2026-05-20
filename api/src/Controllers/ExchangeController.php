<?php

namespace App\Controllers;

use App\Services\BalanceService;
use App\Services\ExchangeService;

class ExchangeController extends BaseController
{
    private ExchangeService $exchangeService;
    private BalanceService $balanceService;

    public function __construct(ExchangeService $exchangeService, BalanceService $balanceService)
    {
        $this->exchangeService = $exchangeService;
        $this->balanceService = $balanceService;
    }

    public function convertFiat($request, $response, $args)
    {
        $account = $request->getAttribute('account');
        $params = $request->getQueryParams();
        $to = strtoupper($params['to'] ?? '');

        if (!$to) {
            return $this->jsonResponse($response, ['error' => 'Missing target currency'], 400);
        }

        $balance = $this->balanceService->calculateBalance($account);

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
        $account = $request->getAttribute('account');
        $params = $request->getQueryParams();
        $to = strtoupper($params['to'] ?? '');

        if (!$to) {
            return $this->jsonResponse($response, ['error' => 'Missing target crypto'], 400);
        }

        $balance = $this->balanceService->calculateBalance($account);

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
}
