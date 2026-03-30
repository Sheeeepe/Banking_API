<script setup>
import { ref } from "vue";

const emit = defineEmits(["deposit", "withdraw"]);

const depositAmount = ref("");
const depositDesc = ref("");
const withdrawAmount = ref("");
const withdrawDesc = ref("");

const handleDeposit = () => {
  emit("deposit", parseFloat(depositAmount.value), depositDesc.value);
  depositAmount.value = "";
  depositDesc.value = "";
};

const handleWithdraw = () => {
  emit("withdraw", parseFloat(withdrawAmount.value), withdrawDesc.value);
  withdrawAmount.value = "";
  withdrawDesc.value = "";
};

const disableNumberSpin = (event) => {
  if (event.key === "ArrowUp" || event.key === "ArrowDown") {
    event.preventDefault();
  }
};
</script>

<template>
  <div class="grid2">
    <div class="card">
      <div class="card-title">Deposit</div>
      <div class="field">
        <label>Amount</label>
        <input
          v-model="depositAmount"
          type="number"
          placeholder="0.00"
          step="0.01"
          min="0.01"
          @keydown="disableNumberSpin"
          @wheel.prevent
        />
      </div>
      <div class="field">
        <label>Description</label>
        <input v-model="depositDesc" type="text" placeholder="Optional" />
      </div>
      <button class="btn btn-d" @click="handleDeposit">+ Deposit</button>
    </div>

    <div class="card">
      <div class="card-title">Withdraw</div>
      <div class="field">
        <label>Amount</label>
        <input
          v-model="withdrawAmount"
          type="number"
          placeholder="0.00"
          step="0.01"
          min="0.01"
          @keydown="disableNumberSpin"
          @wheel.prevent
        />
      </div>
      <div class="field">
        <label>Description</label>
        <input v-model="withdrawDesc" type="text" placeholder="Optional" />
      </div>
      <button class="btn btn-w" @click="handleWithdraw">− Withdraw</button>
    </div>
  </div>
</template>

<style scoped>
input[type="number"]::-webkit-outer-spin-button,
input[type="number"]::-webkit-inner-spin-button {
  -webkit-appearance: none;
  appearance: none;
  margin: 0;
}

input[type="number"] {
  appearance: textfield;
  -webkit-appearance: textfield;
  -moz-appearance: textfield;
}
</style>
