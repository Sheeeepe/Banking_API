<?php

use App\Controllers\AccountController;

return function ($app) {
    $app->get('/', function ($request, $response) {
        $response->getBody()->write(json_encode([
            'message' => 'Mini Banking API',
            'version' => '1.0.0'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $account = new AccountController();

    $app->get('/accounts/{id}/transactions', [$account, 'getTransactions']);
    $app->get('/accounts/{id}/transactions/{transactionId}', [$account, 'getTransaction']);
    $app->post('/accounts/{id}/deposits', [$account, 'createDeposit']);
    $app->post('/accounts/{id}/withdrawals', [$account, 'createWithdrawal']);
    $app->post('/accounts/{id}/transfers', [$account, 'createTransfer']);
    $app->put('/accounts/{id}/transactions/{transactionId}', [$account, 'updateTransaction']);
    $app->delete('/accounts/{id}/transactions/{transactionId}', [$account, 'deleteTransaction']);
    $app->get('/accounts/{id}/balance', [$account, 'getBalance']);
    $app->get('/accounts/{id}/balance/convert/fiat', [$account, 'convertFiat']);
    $app->get('/accounts/{id}/balance/convert/crypto', [$account, 'convertCrypto']);
    $app->post('/accounts', [$account, 'createAccount']);
};
