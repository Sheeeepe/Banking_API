# Mini Banking API

A school exercise based on [this assignment](https://gist.github.com/benve-meucci/c0f418feb6aebf4ebfbc886b7f2861b1) — a REST API for basic banking operations built with PHP and Slim 4, with a Preact frontend and a fully dockerized setup.

## Assignment

The exercise required building a simplified REST banking backend in groups of 2–3 students, covering:

- deposit and withdrawal registration
- transaction list and detail
- current balance calculation
- balance conversion to fiat currency via [Frankfurter](https://frankfurter.dev)
- balance conversion to cryptocurrency via [Binance](https://binance.com)

Required technologies: **Slim**, **MySQL/MariaDB**, JSON responses.

This implementation extends the base requirements with a Preact frontend and a fully containerized Docker setup.

## Stack

| Layer     | Technology             |
| --------- | ---------------------- |
| API       | PHP 8.3, Slim 4        |
| Frontend  | Preact, Vite           |
| Database  | MariaDB                |
| DB Admin  | phpMyAdmin             |
| Container | Docker, Docker Compose |

## Project Structure

```
Banking_API/
├── api/
│   ├── public/
│   │   └── index.php          # Entry point
│   └── src/
│       ├── controllers/
│       │   └── AccountController.php
│       ├── routes.php
│       └── database.php
├── app/
│   └── src/
│       ├── api.js             # API calls
│       ├── app.jsx            # Root component
│       └── components/        # UI components
├── build/
│   ├── Dockerfile.api
│   ├── Dockerfile.frontend
│   ├── init.sql               # DB schema + seed data
│   ├── start-api.sh
│   └── start-frontend.sh
├── docker-compose.yaml
├── .env
└── .env.example
```

## Getting Started

**1. Clone the repo and create your `.env`:**

```bash
cp .env.example .env
```

Edit `.env` with your values:

```env
DB_PORT=3306
DB_NAME=banking_db
DB_USER=banking_user      # Do not use 'root'
DB_PASSWORD=yourpassword
```

**2. Start all services:**

```bash
docker compose up --build
```

**3. Access the services:**

| Service    | URL                   |
| ---------- | --------------------- |
| Frontend   | http://localhost:5173 |
| API        | http://localhost:8080 |
| phpMyAdmin | http://localhost:8081 |

## API Endpoints

All endpoints are relative to `http://localhost:8080`.

### Accounts

| Method   | Endpoint                                      | Description                    |
| -------- | --------------------------------------------- | ------------------------------ |
| `GET`    | `/accounts/{id}/balance`                      | Get current balance            |
| `GET`    | `/accounts/{id}/transactions`                 | List all transactions          |
| `GET`    | `/accounts/{id}/transactions/{transactionId}` | Get a single transaction       |
| `POST`   | `/accounts/{id}/deposits`                     | Create a deposit               |
| `POST`   | `/accounts/{id}/withdrawals`                  | Create a withdrawal            |
| `PUT`    | `/accounts/{id}/transactions/{transactionId}` | Update transaction description |
| `DELETE` | `/accounts/{id}/transactions/{transactionId}` | Delete last transaction        |

### Currency Conversion

| Method | Endpoint                                       | Description                      |
| ------ | ---------------------------------------------- | -------------------------------- |
| `GET`  | `/accounts/{id}/balance/convert/fiat?to=USD`   | Convert balance to fiat currency |
| `GET`  | `/accounts/{id}/balance/convert/crypto?to=BTC` | Convert balance to crypto        |

Fiat conversion uses [Frankfurter](https://frankfurter.dev). Crypto conversion uses [Binance](https://binance.com).

### Example Requests

```bash
# Get balance
curl http://localhost:8080/accounts/1/balance

# Deposit
curl -X POST http://localhost:8080/accounts/1/deposits \
  -H "Content-Type: application/json" \
  -d '{"amount": 100.00, "description": "Salary"}'

# Withdraw
curl -X POST http://localhost:8080/accounts/1/withdrawals \
  -H "Content-Type: application/json" \
  -d '{"amount": 50.00, "description": "Groceries"}'

# Convert to USD
curl http://localhost:8080/accounts/1/balance/convert/fiat?to=USD

# Convert to BTC
curl http://localhost:8080/accounts/1/balance/convert/crypto?to=BTC
```

## Notes

- The database is seeded with one demo account (`id = 1`) on first startup
- Data is persisted in a named Docker volume (`mysql_data`) and survives container restarts
- To reset the database: `docker compose down -v && docker compose up`
- `composer install` and `npm install` run automatically on first container startup via the entrypoint scripts
