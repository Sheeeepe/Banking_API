<script setup>
import { ref, onMounted } from "vue";
import { useRouter } from "vue-router";
import {
  getBalance,
  getTransactions,
  deposit,
  withdraw,
  deleteTransaction,
} from "../api";
import Header from "../components/Header.vue";
import Balance from "../components/Balance.vue";
import TransactionForm from "../components/TransactionForm.vue";
import TransactionList from "../components/TransactionList.vue";
import FiatConverter from "../components/FiatConverter.vue";
import CryptoConverter from "../components/CryptoConverter.vue";
import Toast from "../components/Toast.vue";

const router = useRouter();

const balance = ref(null);
const currency = ref("");
const ownerName = ref("");
const transactions = ref([]);
const loading = ref(true);
const toast = ref(null);

const notify = (msg, type = "success") => (toast.value = { msg, type });
const notifyError = (msg) => notify(msg, "error");

const refreshBalance = async () => {
  const data = await getBalance();
  balance.value = data.balance;
  currency.value = data.currency;
  ownerName.value = data.owner_name;
};

const refreshTransactions = async () => {
  const data = await getTransactions();
  transactions.value = data.transactions || [];
};

const refresh = () => Promise.all([refreshBalance(), refreshTransactions()]);

onMounted(() => refresh().finally(() => (loading.value = false)));

const handleDeposit = async (amount, description) => {
  if (!amount || amount <= 0) return notifyError("Enter a valid amount");
  try {
    await deposit(amount, description);
    notify("Deposit successful");
    await refresh();
  } catch (e) {
    notifyError(e.message);
  }
};

const handleWithdraw = async (amount, description) => {
  if (!amount || amount <= 0) return notifyError("Enter a valid amount");
  try {
    await withdraw(amount, description);
    notify("Withdrawal successful");
    await refresh();
  } catch (e) {
    notifyError(e.message);
  }
};

const handleDelete = async (id) => {
  try {
    await deleteTransaction(id);
    notify("Transaction deleted");
    await refresh();
  } catch (e) {
    notifyError(e.message);
  }
};

const handleSelect = (t) => {
  router.push(`/transactions/${t.id}`);
};
</script>

<template>
  <div v-if="loading" class="loading">LOADING</div>
  <div v-else>
    <Header :ownerName="ownerName" />
    <Balance :balance="balance" :currency="currency" :ownerName="ownerName" />
    <TransactionForm @deposit="handleDeposit" @withdraw="handleWithdraw" />
    <div class="grid2 mb-16">
      <FiatConverter :balance="balance" :currency="currency" @error="notifyError" />
      <CryptoConverter
        :balance="balance"
        :currency="currency"
        @error="notifyError"
      />
    </div>
    <TransactionList
      :transactions="transactions"
      :currency="currency"
      @select="handleSelect"
      @delete="handleDelete"
    />
    <Toast :toast="toast" @done="toast = null" />
  </div>
</template>

