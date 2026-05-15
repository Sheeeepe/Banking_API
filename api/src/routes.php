<?php

use App\Controllers\AccountController;
use App\Controllers\TransactionController;
use App\Controllers\ExchangeController;
use App\Services\BalanceService;
use App\Services\TransactionService;
use App\Services\ExchangeService;
use App\Middleware\AccountExistsMiddleware;
use Slim\Routing\RouteCollectorProxy;

return function ($app) {
    // Services
    $balanceService = new BalanceService();
    $transactionService = new TransactionService($balanceService);
    $exchangeService = new ExchangeService();

    // Controllers
    $accountController = new AccountController($balanceService);
    $transactionController = new TransactionController($transactionService);
    $exchangeController = new ExchangeController($exchangeService, $balanceService);

    $app->get('/', function ($request, $response) {
        $response->getBody()->write(json_encode([
            'message' => 'Mini Banking API',
            'version' => '1.0.0'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->group('/accounts', function (RouteCollectorProxy $group) use ($accountController, $transactionController, $exchangeController) {
        // Global account routes
        $group->get('', [$accountController, 'getAccounts']);
        $group->post('', [$accountController, 'createAccount']);

        // Account specific routes
        $group->group('/{id}', function (RouteCollectorProxy $accountGroup) use ($accountController, $transactionController, $exchangeController) {
            
            // Account CRUD & Balance
            $accountGroup->get('', [$accountController, 'getAccount']);
            $accountGroup->put('', [$accountController, 'updateAccount']);
            $accountGroup->delete('', [$accountController, 'deleteAccount']);
            $accountGroup->get('/balance', [$accountController, 'getBalance']);

            // Exchange Routes
            $accountGroup->get('/balance/convert/fiat', [$exchangeController, 'convertFiat']);
            $accountGroup->get('/balance/convert/crypto', [$exchangeController, 'convertCrypto']);

            // Transactions
            $accountGroup->get('/transactions', [$transactionController, 'getTransactions']);
            $accountGroup->get('/transactions/{transactionId}', [$transactionController, 'getTransaction']);
            $accountGroup->post('/deposits', [$transactionController, 'createDeposit']);
            $accountGroup->post('/withdrawals', [$transactionController, 'createWithdrawal']);
            $accountGroup->post('/transfers', [$transactionController, 'createTransfer']);
            $accountGroup->put('/transactions/{transactionId}', [$transactionController, 'updateTransaction']);
            $accountGroup->delete('/transactions/{transactionId}', [$transactionController, 'deleteTransaction']);
            
        })->add(new AccountExistsMiddleware());
    });
};
