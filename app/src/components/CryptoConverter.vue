<script setup>
import { ref, watch } from 'vue'
import { convertCrypto } from '../api'

const props = defineProps({ balance: Number, currency: String })
const emit = defineEmits(['error'])

const CRYPTOS = ['BTC', 'ETH', 'BNB', 'SOL', 'XRP', 'ADA', 'DOGE']
const to      = ref('BTC')
const result  = ref(null)
const loading = ref(false)

const convert = async () => {
  loading.value = true
  result.value = null
  try {
    result.value = await convertCrypto(to.value)
  } catch (e) {
    emit('error', e.message)
  }
  loading.value = false
}

watch(to, () => { result.value = null })
</script>

<template>
  <div class="card">
    <div class="card-title">Crypto converter</div>
    <div class="conv-row">
      <div class="field">
        <label>Target crypto</label>
        <select v-model="to">
          <option v-for="c in CRYPTOS" :key="c" :value="c">{{ c }}</option>
        </select>
      </div>
      <button class="btn btn-c" @click="convert" :disabled="loading || !balance">
        {{ loading ? '...' : 'Convert' }}
      </button>
    </div>
    <div v-if="result" class="result-box">
      <div class="eq">{{ balance?.toFixed(2) }} {{ currency }} = {{ result.converted_amount }} {{ to }}</div>
      <div class="meta">price {{ result.price }} · {{ result.market_symbol }} · Binance</div>
    </div>
  </div>
</template>
