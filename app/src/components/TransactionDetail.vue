<script setup>
import { ref, watch } from 'vue'

const props = defineProps({ transaction: Object, currency: String })
const emit = defineEmits(['close', 'updated'])

const description = ref('')

watch(() => props.transaction, (t) => {
  if (t) description.value = t.description || ''
}, { immediate: true })

const save = () => {
  emit('updated', props.transaction.id, description.value)
}
</script>

<template>
  <div class="overlay" @click.self="$emit('close')">
    <div class="modal">
      <div class="modal-header">
        <div>
          <div :class="`tx-type ${transaction.type === 'deposit' ? 'd' : 'w'}`">
            {{ transaction.type }}
          </div>
          <div class="modal-id">TX #{{ transaction.id }}</div>
        </div>
        <button class="modal-close" @click="$emit('close')">✕</button>
      </div>

      <div class="modal-body">
        <div class="detail-row">
          <span class="detail-label">Amount</span>
          <span :class="`tx-amt ${transaction.type === 'deposit' ? 'd' : 'w'}`">
            {{ transaction.type === 'deposit' ? '+' : '−' }}{{ parseFloat(transaction.amount).toFixed(2) }} {{ currency }}
          </span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Balance after</span>
          <span class="detail-value">{{ parseFloat(transaction.balance_after).toFixed(2) }} {{ currency }}</span>
        </div>
        <div class="detail-row">
          <span class="detail-label">Date</span>
          <span class="detail-value">{{ new Date(transaction.created_at).toLocaleString() }}</span>
        </div>

        <div class="field" style="margin-top: 20px">
          <label>Description</label>
          <input v-model="description" type="text" placeholder="Add a description..." />
        </div>

        <button class="btn btn-c" style="margin-top: 8px" @click="save">Save description</button>
      </div>
    </div>
  </div>
</template>
