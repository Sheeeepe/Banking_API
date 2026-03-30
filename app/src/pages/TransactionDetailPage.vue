<script setup>
import { onMounted, ref, watch } from "vue";
import { useRoute, useRouter } from "vue-router";
import { getBalance, getTransaction, updateTransaction } from "../api";
import TransactionDetail from "../components/TransactionDetail.vue";
import Toast from "../components/Toast.vue";

const route = useRoute();
const router = useRouter();

const loading = ref(true);
const transaction = ref(null);
const currency = ref("");
const toast = ref(null);

const notify = (msg, type = "success") => (toast.value = { msg, type });
const notifyError = (msg) => notify(msg, "error");

const load = async () => {
  loading.value = true;
  try {
    const id = Number(route.params.id);
    if (!Number.isFinite(id)) throw new Error("Invalid transaction id");
    const [bal, tx] = await Promise.all([getBalance(), getTransaction(id)]);
    currency.value = bal.currency;
    transaction.value = tx.transaction ?? tx;
  } catch (e) {
    notifyError(e.message);
  } finally {
    loading.value = false;
  }
};

onMounted(load);
watch(() => route.params.id, load);

const handleClose = () => router.push("/");

const handleUpdate = async (id, description) => {
  try {
    await updateTransaction(id, description);
    notify("Description updated");
    setTimeout(() => router.push("/"), 250);
  } catch (e) {
    notifyError(e.message);
  }
};
</script>

<template>
  <div v-if="loading" class="loading">LOADING</div>
  <div v-else>
    <TransactionDetail
      v-if="transaction"
      :transaction="transaction"
      :currency="currency"
      @close="handleClose"
      @updated="handleUpdate"
    />
    <div v-else class="card">
      <div class="card-title">Transaction</div>
      <div class="empty">Not found</div>
      <button class="btn btn-c" style="margin-top: 12px" @click="handleClose">
        Back
      </button>
    </div>
    <Toast :toast="toast" @done="toast = null" />
  </div>
</template>

