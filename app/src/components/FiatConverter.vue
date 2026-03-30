<script setup>
import { ref, watch } from 'vue'
import { convertFiat } from '../api'

const props = defineProps({ balance: Number, currency: String })
const emit = defineEmits(['error'])

const CURRENCIES = ['USD', 'GBP', 'JPY', 'CHF', 'CAD', 'AUD', 'CNY']
const to      = ref('USD')
const result  = ref(null)
const loading = ref(false)

const convert = async () => {
  loading.value = true
  result.value = null
  try {
    result.value = await convertFiat(to.value)
  } catch (e) {
    emit('error', e.message)
  }
  loading.value = false
}

watch(to, () => { result.value = null })
</script>

<template>
  <div class="card">
    <div class="card-title">Fiat converter</div>
    <div class="conv-row">
      <div class="field">
        <label>Target currency</label>
        <select v-model="to">
          <option v-for="c in CURRENCIES" :key="c" :value="c">{{ c }}</option>
        </select>
      </div>
      <button class="btn btn-c" @click="convert" :disabled="loading || !balance">
        {{ loading ? '...' : 'Convert' }}
      </button>
    </div>
    <div v-if="result" class="result-box">
      <div class="eq">{{ balance?.toFixed(2) }} {{ currency }} = {{ result.converted_balance }} {{ to }}</div>
      <div class="meta">rate {{ result.rate }} · {{ result.provider }} · {{ result.date }}</div>
    </div>
  </div>
</template>
