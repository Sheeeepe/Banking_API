import { useState, useEffect } from 'preact/hooks'

const API_URL = 'http://localhost:8080'
const ACCOUNT_ID = 1

export function App() {
  const [balance, setBalance] = useState(null)
  const [transactions, setTransactions] = useState([])
  const [currency, setCurrency] = useState('')
  const [ownerName, setOwnerName] = useState('')
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')
  const [success, setSuccess] = useState('')

  const fetchBalance = async () => {
    try {
      const res = await fetch(`${API_URL}/accounts/${ACCOUNT_ID}/balance`)
      const data = await res.json()
      setBalance(data.balance)
      setCurrency(data.currency)
      setOwnerName(data.owner_name)
    } catch (e) {
      setError('Failed to fetch balance')
    }
  }

  const fetchTransactions = async () => {
    try {
      const res = await fetch(`${API_URL}/accounts/${ACCOUNT_ID}/transactions`)
      const data = await res.json()
      setTransactions(data.transactions || [])
    } catch (e) {
      setError('Failed to fetch transactions')
    }
  }

  useEffect(() => {
    const load = async () => {
      await fetchBalance()
      await fetchTransactions()
      setLoading(false)
    }
    load()
  }, [])

  const handleDeposit = async (e) => {
    e.preventDefault()
    const amount = parseFloat(e.target.amount.value)
    const description = e.target.description.value

    if (!amount || amount <= 0) {
      setError('Please enter a valid amount')
      return
    }

    try {
      const res = await fetch(`${API_URL}/accounts/${ACCOUNT_ID}/deposits`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ amount, description })
      })

      if (res.ok) {
        setSuccess('Deposit successful!')
        e.target.reset()
        await fetchBalance()
        await fetchTransactions()
        setTimeout(() => setSuccess(''), 3000)
      } else {
        const data = await res.json()
        setError(data.error || 'Deposit failed')
      }
    } catch (e) {
      setError('Deposit failed')
    }
  }

  const handleWithdraw = async (e) => {
    e.preventDefault()
    const amount = parseFloat(e.target.amount.value)
    const description = e.target.description.value

    if (!amount || amount <= 0) {
      setError('Please enter a valid amount')
      return
    }

    try {
      const res = await fetch(`${API_URL}/accounts/${ACCOUNT_ID}/withdrawals`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ amount, description })
      })

      if (res.ok) {
        setSuccess('Withdrawal successful!')
        e.target.reset()
        await fetchBalance()
        await fetchTransactions()
        setTimeout(() => setSuccess(''), 3000)
      } else {
        const data = await res.json()
        setError(data.error || 'Withdrawal failed')
      }
    } catch (e) {
      setError('Withdrawal failed')
    }
  }

  if (loading) {
    return <div class="section">Loading...</div>
  }

  return (
    <div>
      <header>
        <h1>Mini Banking API</h1>
        <p>Account: {ownerName} (ID: {ACCOUNT_ID})</p>
      </header>

      <div class="balance-card">
        <div>Current Balance</div>
        <div class="amount">
          {balance?.toFixed(2)} {currency}
        </div>
      </div>

      {error && <div class="error">{error}</div>}
      {success && <div class="success">{success}</div>}

      <div class="section">
        <h2>Deposit</h2>
        <form onSubmit={handleDeposit}>
          <input type="number" name="amount" placeholder="Amount" step="0.01" min="0.01" required />
          <input type="text" name="description" placeholder="Description (optional)" />
          <button type="submit" class="btn-primary">Deposit</button>
        </form>
      </div>

      <div class="section">
        <h2>Withdraw</h2>
        <form onSubmit={handleWithdraw}>
          <input type="number" name="amount" placeholder="Amount" step="0.01" min="0.01" required />
          <input type="text" name="description" placeholder="Description (optional)" />
          <button type="submit" class="btn-danger">Withdraw</button>
        </form>
      </div>

      <FiatConverter accountId={ACCOUNT_ID} balance={balance} currency={currency} />
      <CryptoConverter accountId={ACCOUNT_ID} balance={balance} currency={currency} />

      <div class="section">
        <h2>Transaction History</h2>
        {transactions.length === 0 ? (
          <p>No transactions yet</p>
        ) : (
          <ul class="transaction-list">
            {transactions.map(t => (
              <li key={t.id} class="transaction-item">
                <div class="transaction-info">
                  <span class={`transaction-type ${t.type}`}>{t.type}</span>
                  <span class="transaction-date">
                    {new Date(t.created_at).toLocaleString()}
                  </span>
                  {t.description && <small>{t.description}</small>}
                </div>
                <span class="transaction-amount">
                  {t.type === 'deposit' ? '+' : '-'}{t.amount} {currency}
                </span>
              </li>
            ))}
          </ul>
        )}
      </div>
    </div>
  )
}

function FiatConverter({ accountId, balance }) {
  const [toCurrency, setToCurrency] = useState('USD')
  const [result, setResult] = useState(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const currencies = ['USD', 'EUR', 'GBP', 'JPY', 'CHF', 'CAD', 'AUD']

  const convert = async () => {
    if (!balance) return
    setLoading(true)
    setError('')
    setResult(null)

    try {
      const res = await fetch(`${API_URL}/accounts/${accountId}/balance/convert/fiat?to=${toCurrency}`)
      const data = await res.json()
      if (res.ok) {
        setResult(data)
      } else {
        setError(data.error || 'Conversion failed')
      }
    } catch (e) {
      setError('Conversion failed')
    }
    setLoading(false)
  }

  return (
    <div class="section">
      <h2>Currency Converter (Fiat)</h2>
      <div class="converter">
        <select value={toCurrency} onChange={e => setToCurrency(e.target.value)}>
          {currencies.filter(c => c !== 'EUR').map(c => (
            <option key={c} value={c}>{c}</option>
          ))}
        </select>
        <button onClick={convert} disabled={loading} class="btn-secondary">
          {loading ? 'Converting...' : 'Convert'}
        </button>
        {result && (
          <div class="result">
            <p><strong>{balance.toFixed(2)} {result.from_currency}</strong> = <strong>{result.converted_balance} {toCurrency}</strong></p>
            <small>Rate: {result.rate} | Provider: {result.provider}</small>
          </div>
        )}
        {error && <div class="error">{error}</div>}
      </div>
    </div>
  )
}

function CryptoConverter({ accountId, balance }) {
  const [toCrypto, setToCrypto] = useState('BTC')
  const [result, setResult] = useState(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState('')

  const cryptos = ['BTC', 'ETH', 'USDT', 'BNB', 'XRP', 'ADA', 'DOGE', 'SOL']

  const convert = async () => {
    if (!balance) return
    setLoading(true)
    setError('')
    setResult(null)

    try {
      const res = await fetch(`${API_URL}/accounts/${accountId}/balance/convert/crypto?to=${toCrypto}`)
      const data = await res.json()
      if (res.ok) {
        setResult(data)
      } else {
        setError(data.error || 'Conversion failed')
      }
    } catch (e) {
      setError('Conversion failed')
    }
    setLoading(false)
  }

  return (
    <div class="section">
      <h2>Crypto Converter</h2>
      <div class="converter">
        <select value={toCrypto} onChange={e => setToCrypto(e.target.value)}>
          {cryptos.map(c => (
            <option key={c} value={c}>{c}</option>
          ))}
        </select>
        <button onClick={convert} disabled={loading} class="btn-secondary">
          {loading ? 'Converting...' : 'Convert'}
        </button>
        {result && (
          <div class="result">
            <p><strong>{balance.toFixed(2)} {result.from_currency}</strong> = <strong>{result.converted_amount} {toCrypto}</strong></p>
            <small>Price: {result.price} | Market: {result.market_symbol}</small>
          </div>
        )}
        {error && <div class="error">{error}</div>}
      </div>
    </div>
  )
}
