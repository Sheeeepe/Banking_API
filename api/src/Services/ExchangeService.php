<?php

namespace App\Services;

use App\Models\Account;
use RuntimeException;
use InvalidArgumentException;

class ExchangeService
{
    public function convertFiat(Account $account, float $balance, string $toCurrency): array
    {
        $from = strtoupper($account->currency);
        $to = strtoupper($toCurrency);
        
        $url = "https://api.frankfurter.dev/v1/latest?base={$from}&symbols={$to}";
        $json = @file_get_contents($url);
        
        if ($json === false) {
            throw new RuntimeException('External exchange API unavailable');
        }
        
        $data = json_decode($json, true);
        
        if (!isset($data['rates'][$to])) {
            throw new InvalidArgumentException('Target currency not supported');
        }
        
        $rate = (float)$data['rates'][$to];
        $converted = round($balance * $rate, 2);
        
        return [
            'provider' => 'Frankfurter',
            'conversion_type' => 'fiat',
            'from_currency' => $from,
            'to_currency' => $to,
            'original_balance' => $balance,
            'converted_balance' => $converted,
            'rate' => $rate,
            'date' => $data['date'] ?? null
        ];
    }

    public function convertCrypto(Account $account, float $balance, string $toCrypto): array
    {
        $from = strtoupper($account->currency);
        $to = strtoupper($toCrypto);
        $marketSymbol = $to . $from;
        
        $exchangeInfoUrl = "https://api.binance.com/api/v3/exchangeInfo";
        $exchangeInfoJson = @file_get_contents($exchangeInfoUrl);
        
        if ($exchangeInfoJson === false) {
            throw new RuntimeException('External exchange API unavailable');
        }
        
        $exchangeInfo = json_decode($exchangeInfoJson, true);
        $symbols = array_column($exchangeInfo['symbols'] ?? [], 'symbol');
        
        if (!in_array($marketSymbol, $symbols)) {
            throw new InvalidArgumentException('Crypto symbol not supported with this currency');
        }
        
        $priceUrl = "https://api.binance.com/api/v3/ticker/price?symbol=" . $marketSymbol;
        $priceJson = @file_get_contents($priceUrl);
        
        if ($priceJson === false) {
            throw new RuntimeException('Unable to get crypto price');
        }
        
        $priceData = json_decode($priceJson, true);
        
        if (!isset($priceData['price'])) {
            throw new RuntimeException('Price data not available');
        }
        
        $price = (float)$priceData['price'];
        $convertedAmount = $balance / $price;
        
        return [
            'provider' => 'Binance',
            'conversion_type' => 'crypto',
            'from_currency' => $from,
            'to_crypto' => $to,
            'market_symbol' => $marketSymbol,
            'original_balance' => $balance,
            'price' => $price,
            'converted_amount' => round($convertedAmount, 8)
        ];
    }
}
