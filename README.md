# Mini Banking API

REST API per la gestione di un conto bancario semplificato.

## Requisiti

- PHP 8.3+
- Composer
- MariaDB/MySQL
- Slim Framework 4

## Installazione

1. Installa le dipendenze:

```bash
composer install
```

2. Avvia MariaDB (o MySQL) e crea il database:

```bash
docker run -d --name mariadb -e MYSQL_ROOT_PASSWORD=ciccio -e MYSQL_DATABASE=banking_db -p 3306:3306 mariadb:latest
```

Oppure esegui lo schema:

```bash
mysql -u root -p banking_db < database.sql
```

3. Avvia il server:

```bash
php -S 127.0.0.1:8080 -t public
```

4. Opzionale - collegamento phpmyadmin al db:

```bash
docker run -d --name phpmyadmin --network host -e PMA_HOST=127.0.0.1 -e MYSQL_ROOT_PASSWORD=ciccio -p 8080:80 phpmyadmin/phpmyadmin

```

## Endpoint

### Movimenti

| Metodo | Endpoint                                      | Descrizione              |
| ------ | --------------------------------------------- | ------------------------ |
| GET    | `/accounts/{id}/transactions`                 | Lista movimenti          |
| GET    | `/accounts/{id}/transactions/{transactionId}` | Dettaglio movimento      |
| POST   | `/accounts/{id}/deposits`                     | Registra deposito        |
| POST   | `/accounts/{id}/withdrawals`                  | Registra prelievo        |
| PUT    | `/accounts/{id}/transactions/{transactionId}` | Modifica descrizione     |
| DELETE | `/accounts/{id}/transactions/{transactionId}` | Elimina ultimo movimento |

### Saldo

| Metodo | Endpoint                 | Descrizione   |
| ------ | ------------------------ | ------------- |
| GET    | `/accounts/{id}/balance` | Saldo attuale |

### Conversione

| Metodo | Endpoint                                       | Descrizione              |
| ------ | ---------------------------------------------- | ------------------------ |
| GET    | `/accounts/{id}/balance/convert/fiat?to=USD`   | Converti in valuta fiat  |
| GET    | `/accounts/{id}/balance/convert/crypto?to=BTC` | Converti in criptovaluta |

## Esempi di chiamata

### Creare un deposito

```bash
curl -X POST http://127.0.0.1:8080/accounts/1/deposits \
  -H "Content-Type: application/json" \
  -d '{"amount": 1000, "description": "Initial deposit"}'
```

### Creare un prelievo

```bash
curl -X POST http://127.0.0.1:8080/accounts/1/withdrawals \
  -H "Content-Type: application/json" \
  -d '{"amount": 200, "description": "ATM withdrawal"}'
```

### Visualizzare il saldo

```bash
curl http://127.0.0.1:8080/accounts/1/balance
```

### Convertire in USD

```bash
curl "http://127.0.0.1:8080/accounts/1/balance/convert/fiat?to=USD"
```

### Convertire in BTC

```bash
curl "http://127.0.0.1:8080/accounts/1/balance/convert/crypto?to=BTC"
```

### Modificare la descrizione di un movimento

```bash
curl -X PUT http://127.0.0.1:8080/accounts/1/transactions/1 \
  -H "Content-Type: application/json" \
  -d '{"description": "Updated description"}'
```

### Eliminare un movimento (solo l'ultimo)

```bash
curl -X DELETE http://127.0.0.1:8080/accounts/1/transactions/1
```

## Servizi esterni

- **Frankfurter** (https://api.frankfurter.dev) - Conversione valute fiat
- **Binance** (https://api.binance.com) - Conversione criptovalute
