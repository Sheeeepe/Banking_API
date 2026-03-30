const API_URL = "http://localhost:8080";
const ACCOUNT_ID = 1;

const req = async (path, options = {}) => {
  const res = await fetch(`${API_URL}${path}`, {
    headers: { "Content-Type": "application/json" },
    ...options,
  });
  const data = await res.json();
  if (!res.ok) throw new Error(data.error || "Request failed");
  return data;
};

export const getBalance = () => req(`/accounts/${ACCOUNT_ID}/balance`);
export const getTransactions = () =>
  req(`/accounts/${ACCOUNT_ID}/transactions`);
export const getTransaction = (id) =>
  req(`/accounts/${ACCOUNT_ID}/transactions/${id}`);
export const deposit = (amount, description) =>
  req(`/accounts/${ACCOUNT_ID}/deposits`, {
    method: "POST",
    body: JSON.stringify({ amount, description }),
  });
export const withdraw = (amount, description) =>
  req(`/accounts/${ACCOUNT_ID}/withdrawals`, {
    method: "POST",
    body: JSON.stringify({ amount, description }),
  });
export const updateTransaction = (id, description) =>
  req(`/accounts/${ACCOUNT_ID}/transactions/${id}`, {
    method: "PUT",
    body: JSON.stringify({ description }),
  });
export const deleteTransaction = (id) =>
  req(`/accounts/${ACCOUNT_ID}/transactions/${id}`, { method: "DELETE" });
export const convertFiat = (to) =>
  req(`/accounts/${ACCOUNT_ID}/balance/convert/fiat?to=${to}`);
export const convertCrypto = (to) =>
  req(`/accounts/${ACCOUNT_ID}/balance/convert/crypto?to=${to}`);
