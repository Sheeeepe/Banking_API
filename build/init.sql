-- Accounts table
CREATE TABLE IF NOT EXISTS accounts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_name VARCHAR(255) NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'EUR',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    account_id INT NOT NULL,
    type ENUM('deposit', 'withdrawal') NOT NULL,
    amount DECIMAL(15, 2) NOT NULL,
    description TEXT,
    balance_after DECIMAL(15, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE
);

-- Sample accounts
INSERT INTO accounts (id, owner_name, currency) VALUES 
(1, 'Demo Account', 'EUR'),
(2, 'Alice Smith', 'USD'),
(3, 'Bob Johnson', 'GBP')
ON DUPLICATE KEY UPDATE owner_name=VALUES(owner_name), currency=VALUES(currency);

-- Sample transactions
INSERT INTO transactions (account_id, type, amount, description, balance_after) VALUES
(1, 'deposit', 1000.00, 'Initial deposit', 1000.00),
(1, 'withdrawal', 150.50, 'Groceries', 849.50),
(1, 'deposit', 500.00, 'Salary part', 1349.50),
(2, 'deposit', 5000.00, 'Initial deposit', 5000.00),
(2, 'withdrawal', 1200.00, 'Rent payment', 3800.00),
(2, 'withdrawal', 60.00, 'Internet bill', 3740.00),
(2, 'deposit', 200.00, 'Refund', 3940.00),
(3, 'deposit', 2500.00, 'Initial deposit', 2500.00),
(3, 'withdrawal', 300.00, 'Car insurance', 2200.00);