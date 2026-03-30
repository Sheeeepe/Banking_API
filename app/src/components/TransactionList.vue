<script setup>
defineProps({
  transactions: Array,
  currency: String
})

defineEmits(['select', 'delete'])
</script>

<template>
  <div class="card">
    <div class="card-title">Transaction history</div>
    <div v-if="transactions.length === 0" class="empty">No transactions yet</div>
    <div v-else class="tx-list">
      <div
        v-for="(t, index) in transactions"
        :key="t.id"
        class="tx-item"
        @click="$emit('select', t)"
      >
        <div>
          <div :class="`tx-type ${t.type === 'deposit' ? 'd' : 'w'}`">{{ t.type }}</div>
          <div v-if="t.description" class="tx-desc">{{ t.description }}</div>
          <div class="tx-date">{{ new Date(t.created_at).toLocaleString() }}</div>
        </div>
        <div class="tx-right">
          <div :class="`tx-amt ${t.type === 'deposit' ? 'd' : 'w'}`">
            {{ t.type === 'deposit' ? '+' : '−' }}{{ parseFloat(t.amount).toFixed(2) }} {{ currency }}
          </div>
          <div class="tx-bal">bal. {{ parseFloat(t.balance_after).toFixed(2) }}</div>
          <button
            v-if="index === 0"
            class="btn-delete"
            @click.stop="$emit('delete', t.id)"
            title="Delete last transaction"
          >✕</button>
        </div>
      </div>
    </div>
  </div>
</template>
