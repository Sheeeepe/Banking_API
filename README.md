# Mini Banking API

REST API per operazioni bancarie di base costruita con PHP e Slim 4, con frontend Vue 3 e setup completamente containerizzato.

Basata su [questa assegnazione](https://gist.github.com/benve-meucci/c0f418feb6aebf4ebfbc886b7f2861b1).

## Stack Tecnologico

| Livello    | Tecnologia              |
| ---------- | ----------------------- |
| API        | PHP 8.3, Slim 4         |
| Frontend   | Vue 3, Vite, Vue Router |
| Database   | MariaDB                 |
| DB Admin   | phpMyAdmin              |
| Container  | Docker, Docker Compose   |

## Struttura del Progetto

```
Banking_API/
├── api/
│   ├── public/
│   │   └── index.php          # Entry point
│   └── src/
│       ├── Database.php       # Singleton per connessione DB
│       ├── Controllers/
│       │   └── AccountController.php
│       └── routes.php
├── app/
│   ├── index.html
│   ├── vite.config.js
│   └── src/
│       ├── main.js            # Entry point frontend
│       ├── App.vue            # Componente radice
│       ├── api.js             # Chiamate API
│       ├── index.css
│       ├── router/
│       │   └── index.js       # Configurazione Vue Router
│       ├── pages/
│       │   ├── Home.vue
│       │   └── TransactionDetailPage.vue
│       └── components/
│           ├── Balance.vue
│           ├── Header.vue
│           ├── TransactionForm.vue
│           ├── TransactionList.vue
│           ├── TransactionDetail.vue
│           ├── FiatConverter.vue
│           ├── CryptoConverter.vue
│           └── Toast.vue
├── build/
│   ├── Dockerfile.api
│   ├── Dockerfile.frontend
│   ├── init.sql               # Schema e dati seed
│   ├── start-api.sh
│   └── start-frontend.sh
├── docker-compose.yaml
├── .env
└── .env.example
```

## Scelte Progettuali

### Modello Dati

**Tabella `accounts`:**
- `id`, `owner_name`, `currency`, `created_at`
- Un account per semplicità (come consigliato nell'assegnazione)

**Tabella `transactions`:**
- `id`, `account_id`, `type` (deposit/withdrawal), `amount`, `description`
- `balance_after`: campo opzionale che memorizza il saldo dopo ogni operazione
- `created_at`

### Regole di Business

**Deposito:**
- L'importo deve essere maggiore di zero (validato con 400)

**Prelievo:**
- L'importo deve essere maggiore di zero
- Non si può prelevare più del saldo disponibile (validato con 422)

**Saldo:**
- Non è memorizzato come campo, viene calcolato dinamicamente:
  ```
  saldo = SUM(depositi) - SUM(prelievi)
  ```

**Eliminazione movimenti:**
- Si può eliminare solo l'ultimo movimento (l'API verifica che l'ID corrisponda al movimento più recente)
- Questa regola mantiene la coerenza del saldo

### Endpoint

**Struttura REST:**
- `POST /accounts/{id}/deposits` - registra un deposito
- `POST /accounts/{id}/withdrawals` - registra un prelievo
- `GET /accounts/{id}/balance` - calcola il saldo attuale
- `GET /accounts/{id}/transactions` - lista movimenti
- `GET /accounts/{id}/transactions/{id}` - dettaglio movimento
- `PUT /accounts/{id}/transactions/{id}` - modifica solo la descrizione
- `DELETE /accounts/{id}/transactions/{id}` - elimina solo l'ultimo movimento

### Conversione Valute

**Fiat (Frankfurter):**
- Usa l'API `https://api.frankfurter.dev/v1/latest`
- Moltiplica il saldo per il tasso di cambio
- Arrotonda a 2 decimali

**Crypto (Binance):**
- Costruisce il simbolo di mercato come `{CRYPTO}{VALUTA}` (es. BTCEUR)
- Verifica l'esistenza della coppia tramite `exchangeInfo`
- Ottiene il prezzo tramite `ticker/price`
- Divide il saldo per il prezzo (quantità di crypto ottenibile)
- Arrotonda a 8 decimali

### Gestione Errori

| Codice | Casi                                      |
| ------ | ----------------------------------------- |
| 400    | Importo non valido, valuta mancante/non supportata |
| 404    | Conto o movimento non trovato             |
| 422    | Prelievo superiore al saldo disponibile   |
| 502    | Errore nelle chiamate a API esterne       |

### JSON Responses

Le risposte seguono una struttura coerente con i campi suggeriti dall'assegnazione:
- Campi minimi per operazioni semplici
- Struttura estesa per conversioni con `provider`, `rate`, `converted_balance`

### Frontend (Vue 3)

- Single Page Application con Vue Router
- Gestione stato locale nei componenti
- Comunicazione con API tramite modulo `api.js`
- Componenti per: saldo, lista transazioni, form operazioni, convertitori fiat/crypto
- Sistema di notifiche toast per feedback utente

## Avvio Rapido

**1. Clona il repo e crea `.env`:**

```bash
cp .env.example .env
```

Modifica `.env`:

```env
DB_PORT=3306
DB_NAME=banking_db
DB_USER=banking_user
DB_PASSWORD=tuapassword
```

**2. Avvia tutti i servizi:**

```bash
docker compose up --build
```

**3. Accedi ai servizi:**

| Servizio    | URL                      |
| ----------- | ------------------------ |
| Frontend    | http://localhost:5173    |
| API         | http://localhost:8080    |
| phpMyAdmin  | http://localhost:8081    |

## Endpoint API

Tutti gli endpoint sono relativi a `http://localhost:8080`.

### Conto

| Metodo   | Endpoint                     | Descrizione                |
| -------- | ---------------------------- | -------------------------- |
| `GET`    | `/accounts/{id}/balance`     | Ottieni saldo attuale     |
| `POST`   | `/accounts`                  | Crea un nuovo conto        |

### Transazioni

| Metodo   | Endpoint                                         | Descrizione                     |
| -------- | ------------------------------------------------ | ------------------------------- |
| `GET`    | `/accounts/{id}/transactions`                    | Lista tutti i movimenti        |
| `GET`    | `/accounts/{id}/transactions/{transactionId}`   | Dettaglio di un movimento      |
| `POST`   | `/accounts/{id}/deposits`                        | Registra un deposito           |
| `POST`   | `/accounts/{id}/withdrawals`                     | Registra un prelievo           |
| `PUT`    | `/accounts/{id}/transactions/{transactionId}`    | Modifica descrizione            |
| `DELETE` | `/accounts/{id}/transactions/{transactionId}`   | Elimina ultimo movimento       |

### Conversione

| Metodo | Endpoint                                        | Descrizione                     |
| ------ | ----------------------------------------------- | ------------------------------- |
| `GET`  | `/accounts/{id}/balance/convert/fiat?to=USD`   | Converti in valuta fiat         |
| `GET`  | `/accounts/{id}/balance/convert/crypto?to=BTC` | Converti in criptovaluta        |

### Esempi di Chiamate

```bash
# Crea account
curl -X POST http://localhost:8080/accounts \
  -H "Content-Type: application/json" \
  -d '{"owner_name": "Mario Rossi", "currency": "EUR"}'

# Ottieni saldo
curl http://localhost:8080/accounts/1/balance

# Deposito
curl -X POST http://localhost:8080/accounts/1/deposits \
  -H "Content-Type: application/json" \
  -d '{"amount": 1000.00, "description": "Stipendio"}'

# Prelievo
curl -X POST http://localhost:8080/accounts/1/withdrawals \
  -H "Content-Type: application/json" \
  -d '{"amount": 50.00, "description": "Spesa"}'

# Lista movimenti
curl http://localhost:8080/accounts/1/transactions

# Dettaglio movimento
curl http://localhost:8080/accounts/1/transactions/1

# Modifica descrizione
curl -X PUT http://localhost:8080/accounts/1/transactions/1 \
  -H "Content-Type: application/json" \
  -d '{"description": "Uscita cena"}'

# Elimina ultimo movimento
curl -X DELETE http://localhost:8080/accounts/1/transactions/2

# Converti in USD
curl "http://localhost:8080/accounts/1/balance/convert/fiat?to=USD"

# Converti in BTC
curl "http://localhost:8080/accounts/1/balance/convert/crypto?to=BTC"
```

## Criteri di Valutazione Rispettati

- Correttezza degli endpoint REST
- Qualità del modello dati
- Correttezza della logica di business
- Uso corretto del database (prepared statements, foreign keys)
- Integrazione con Frankfurter
- Integrazione con Binance
- Gestione degli errori con codici HTTP appropriati
- Chiarezza del JSON nelle risposte

## Note

- Il database viene inizializzato con un account demo (`id = 1`) al primo avvio
- I dati sono persistiti in un volume Docker (`mysql_data`)
- Per resettare: `docker compose down -v && docker compose up`
- `composer install` e `npm install` vengono eseguiti automaticamente al primo avvio
