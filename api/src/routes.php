<?php

use App\Controllers\AccountController;

return function ($app, $controller) {
    $app->get('/', function ($request, $response) {
        $response->getBody()->write(json_encode([
            'message' => 'Mini Banking API',
            'version' => '1.0.0'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->get('/accounts/{id}/transactions', [$controller, 'getTransactions']);
    $app->get('/accounts/{id}/transactions/{transactionId}', [$controller, 'getTransaction']);
    $app->post('/accounts/{id}/deposits', [$controller, 'createDeposit']);
    $app->post('/accounts/{id}/withdrawals', [$controller, 'createWithdrawal']);
    $app->put('/accounts/{id}/transactions/{transactionId}', [$controller, 'updateTransaction']);
    $app->delete('/accounts/{id}/transactions/{transactionId}', [$controller, 'deleteTransaction']);
    $app->get('/accounts/{id}/balance', [$controller, 'getBalance']);
    $app->get('/accounts/{id}/balance/convert/fiat', [$controller, 'convertFiat']);
    $app->get('/accounts/{id}/balance/convert/crypto', [$controller, 'convertCrypto']);
};
