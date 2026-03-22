<?php

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/database.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();

$app->addBodyParsingMiddleware();

$app->get('/', function ($request, $response) {
    $response->getBody()->write(json_encode([
        'message' => 'Mini Banking API',
        'version' => '1.0.0'
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/accounts/{id}/transactions', function ($request, $response, $args) use ($mysqli) {
    $accountId = (int)$args['id'];
    
    $stmt = $mysqli->prepare('SELECT id, currency FROM accounts WHERE id = ?');
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    $account = $result->fetch_assoc();
    
    if (!$account) {
        $response->getBody()->write(json_encode(['error' => 'Account not found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    
    $stmt = $mysqli->prepare('SELECT * FROM transactions WHERE account_id = ? ORDER BY created_at DESC');
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $transactions = [];
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
    
    $response->getBody()->write(json_encode([
        'account_id' => $accountId,
        'currency' => $account['currency'],
        'transactions' => $transactions
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/accounts/{id}/transactions/{transactionId}', function ($request, $response, $args) use ($mysqli) {
    $accountId = (int)$args['id'];
    $transactionId = (int)$args['transactionId'];
    
    $stmt = $mysqli->prepare('SELECT * FROM transactions WHERE id = ? AND account_id = ?');
    $stmt->bind_param('ii', $transactionId, $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    
    if (!$transaction) {
        $response->getBody()->write(json_encode(['error' => 'Transaction not found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    
    $response->getBody()->write(json_encode($transaction));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/accounts/{id}/deposits', function ($request, $response, $args) use ($mysqli) {
    $accountId = (int)$args['id'];
    $data = $request->getParsedBody();
    
    if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
        $response->getBody()->write(json_encode(['error' => 'Amount must be greater than zero']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    
    $amount = (float)$data['amount'];
    $description = $data['description'] ?? '';
    
    $stmt = $mysqli->prepare('SELECT id, currency FROM accounts WHERE id = ?');
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    $account = $result->fetch_assoc();
    
    if (!$account) {
        $response->getBody()->write(json_encode(['error' => 'Account not found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    
    $stmt = $mysqli->prepare("
        SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) -
               COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END), 0) AS balance
        FROM transactions WHERE account_id = ?
    ");
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $currentBalance = (float)($row['balance'] ?? 0);
    $newBalance = $currentBalance + $amount;
    
    $stmt = $mysqli->prepare('INSERT INTO transactions (account_id, type, amount, description, balance_after) VALUES (?, ?, ?, ?, ?)');
    $type = 'deposit';
    $stmt->bind_param('isdsd', $accountId, $type, $amount, $description, $newBalance);
    $stmt->execute();
    
    $transactionId = $mysqli->insert_id;
    
    $response->getBody()->write(json_encode([
        'message' => 'Deposit successful',
        'transaction_id' => $transactionId,
        'account_id' => $accountId,
        'type' => 'deposit',
        'amount' => $amount,
        'description' => $description,
        'balance_after' => $newBalance
    ]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

$app->post('/accounts/{id}/withdrawals', function ($request, $response, $args) use ($mysqli) {
    $accountId = (int)$args['id'];
    $data = $request->getParsedBody();
    
    if (!isset($data['amount']) || !is_numeric($data['amount']) || $data['amount'] <= 0) {
        $response->getBody()->write(json_encode(['error' => 'Amount must be greater than zero']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    
    $amount = (float)$data['amount'];
    $description = $data['description'] ?? '';
    
    $stmt = $mysqli->prepare('SELECT id, currency FROM accounts WHERE id = ?');
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    $account = $result->fetch_assoc();
    
    if (!$account) {
        $response->getBody()->write(json_encode(['error' => 'Account not found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    
    $stmt = $mysqli->prepare("
        SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) -
               COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END), 0) AS balance
        FROM transactions WHERE account_id = ?
    ");
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $currentBalance = (float)($row['balance'] ?? 0);
    
    if ($amount > $currentBalance) {
        $response->getBody()->write(json_encode(['error' => 'Insufficient funds']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
    }
    
    $newBalance = $currentBalance - $amount;
    
    $stmt = $mysqli->prepare('INSERT INTO transactions (account_id, type, amount, description, balance_after) VALUES (?, ?, ?, ?, ?)');
    $type = 'withdrawal';
    $stmt->bind_param('isdsd', $accountId, $type, $amount, $description, $newBalance);
    $stmt->execute();
    
    $transactionId = $mysqli->insert_id;
    
    $response->getBody()->write(json_encode([
        'message' => 'Withdrawal successful',
        'transaction_id' => $transactionId,
        'account_id' => $accountId,
        'type' => 'withdrawal',
        'amount' => $amount,
        'description' => $description,
        'balance_after' => $newBalance
    ]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
});

$app->put('/accounts/{id}/transactions/{transactionId}', function ($request, $response, $args) use ($mysqli) {
    $accountId = (int)$args['id'];
    $transactionId = (int)$args['transactionId'];
    $data = $request->getParsedBody();
    
    if (!isset($data['description'])) {
        $response->getBody()->write(json_encode(['error' => 'Description is required']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    
    $stmt = $mysqli->prepare('SELECT id FROM transactions WHERE id = ? AND account_id = ?');
    $stmt->bind_param('ii', $transactionId, $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response->getBody()->write(json_encode(['error' => 'Transaction not found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    
    $description = $data['description'];
    $stmt = $mysqli->prepare('UPDATE transactions SET description = ? WHERE id = ? AND account_id = ?');
    $stmt->bind_param('sii', $description, $transactionId, $accountId);
    $stmt->execute();
    
    $response->getBody()->write(json_encode([
        'message' => 'Transaction updated successfully',
        'transaction_id' => $transactionId,
        'description' => $description
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->delete('/accounts/{id}/transactions/{transactionId}', function ($request, $response, $args) use ($mysqli) {
    $accountId = (int)$args['id'];
    $transactionId = (int)$args['transactionId'];
    
    $stmt = $mysqli->prepare('SELECT id, balance_after FROM transactions WHERE id = ? AND account_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->bind_param('ii', $transactionId, $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    $transaction = $result->fetch_assoc();
    
    if (!$transaction) {
        $response->getBody()->write(json_encode(['error' => 'Transaction not found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    
    $stmt = $mysqli->prepare('SELECT id FROM transactions WHERE account_id = ? ORDER BY created_at DESC LIMIT 1');
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    $lastTransaction = $result->fetch_assoc();
    
    if ($lastTransaction['id'] != $transactionId) {
        $response->getBody()->write(json_encode(['error' => 'Can only delete the last transaction']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(422);
    }
    
    $stmt = $mysqli->prepare('DELETE FROM transactions WHERE id = ? AND account_id = ?');
    $stmt->bind_param('ii', $transactionId, $accountId);
    $stmt->execute();
    
    $response->getBody()->write(json_encode([
        'message' => 'Transaction deleted successfully',
        'transaction_id' => $transactionId
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/accounts/{id}/balance', function ($request, $response, $args) use ($mysqli) {
    $accountId = (int)$args['id'];
    
    $stmt = $mysqli->prepare('SELECT id, owner_name, currency FROM accounts WHERE id = ?');
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    $account = $result->fetch_assoc();
    
    if (!$account) {
        $response->getBody()->write(json_encode(['error' => 'Account not found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    
    $stmt = $mysqli->prepare("
        SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) -
               COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END), 0) AS balance
        FROM transactions WHERE account_id = ?
    ");
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $balance = (float)($row['balance'] ?? 0);
    
    $response->getBody()->write(json_encode([
        'account_id' => $accountId,
        'owner_name' => $account['owner_name'],
        'currency' => $account['currency'],
        'balance' => $balance
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/accounts/{id}/balance/convert/fiat', function ($request, $response, $args) use ($mysqli) {
    $accountId = (int)$args['id'];
    $params = $request->getQueryParams();
    $to = strtoupper($params['to'] ?? '');
    
    if (!$to) {
        $response->getBody()->write(json_encode(['error' => 'Missing target currency']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    
    $stmt = $mysqli->prepare('SELECT id, currency FROM accounts WHERE id = ?');
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    $account = $result->fetch_assoc();
    
    if (!$account) {
        $response->getBody()->write(json_encode(['error' => 'Account not found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    
    $from = strtoupper($account['currency']);
    
    $stmt = $mysqli->prepare("
        SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) -
               COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END), 0) AS balance
        FROM transactions WHERE account_id = ?
    ");
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $balance = (float)($row['balance'] ?? 0);
    
    $url = "https://api.frankfurter.dev/v1/latest?base={$from}&symbols={$to}";
    $json = @file_get_contents($url);
    
    if ($json === false) {
        $response->getBody()->write(json_encode(['error' => 'External exchange API unavailable']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(502);
    }
    
    $data = json_decode($json, true);
    
    if (!isset($data['rates'][$to])) {
        $response->getBody()->write(json_encode(['error' => 'Target currency not supported']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    
    $rate = (float)$data['rates'][$to];
    $converted = round($balance * $rate, 2);
    
    $response->getBody()->write(json_encode([
        'account_id' => $accountId,
        'provider' => 'Frankfurter',
        'conversion_type' => 'fiat',
        'from_currency' => $from,
        'to_currency' => $to,
        'original_balance' => $balance,
        'converted_balance' => $converted,
        'rate' => $rate,
        'date' => $data['date'] ?? null
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/accounts/{id}/balance/convert/crypto', function ($request, $response, $args) use ($mysqli) {
    $accountId = (int)$args['id'];
    $params = $request->getQueryParams();
    $to = strtoupper($params['to'] ?? '');
    
    if (!$to) {
        $response->getBody()->write(json_encode(['error' => 'Missing target crypto']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    
    $stmt = $mysqli->prepare('SELECT id, currency FROM accounts WHERE id = ?');
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    $account = $result->fetch_assoc();
    
    if (!$account) {
        $response->getBody()->write(json_encode(['error' => 'Account not found']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
    
    $from = strtoupper($account['currency']);
    $marketSymbol = $to . $from;
    
    $exchangeInfoUrl = "https://api.binance.com/api/v3/exchangeInfo";
    $exchangeInfoJson = @file_get_contents($exchangeInfoUrl);
    
    if ($exchangeInfoJson === false) {
        $response->getBody()->write(json_encode(['error' => 'External exchange API unavailable']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(502);
    }
    
    $exchangeInfo = json_decode($exchangeInfoJson, true);
    $symbols = array_column($exchangeInfo['symbols'] ?? [], 'symbol');
    
    if (!in_array($marketSymbol, $symbols)) {
        $response->getBody()->write(json_encode(['error' => 'Crypto symbol not supported with this currency']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
    
    $stmt = $mysqli->prepare("
        SELECT COALESCE(SUM(CASE WHEN type = 'deposit' THEN amount ELSE 0 END), 0) -
               COALESCE(SUM(CASE WHEN type = 'withdrawal' THEN amount ELSE 0 END), 0) AS balance
        FROM transactions WHERE account_id = ?
    ");
    $stmt->bind_param('i', $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $balance = (float)($row['balance'] ?? 0);
    
    $priceUrl = "https://api.binance.com/api/v3/ticker/price?symbol=" . $marketSymbol;
    $priceJson = @file_get_contents($priceUrl);
    
    if ($priceJson === false) {
        $response->getBody()->write(json_encode(['error' => 'Unable to get crypto price']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(502);
    }
    
    $priceData = json_decode($priceJson, true);
    
    if (!isset($priceData['price'])) {
        $response->getBody()->write(json_encode(['error' => 'Price data not available']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(502);
    }
    
    $price = (float)$priceData['price'];
    $convertedAmount = $balance / $price;
    
    $response->getBody()->write(json_encode([
        'account_id' => $accountId,
        'provider' => 'Binance',
        'conversion_type' => 'crypto',
        'from_currency' => $from,
        'to_crypto' => $to,
        'market_symbol' => $marketSymbol,
        'original_balance' => $balance,
        'price' => $price,
        'converted_amount' => round($convertedAmount, 8)
    ]));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
