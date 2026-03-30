<script setup>
import { watch } from 'vue'

const props = defineProps({ toast: Object })
const emit = defineEmits(['done'])

watch(() => props.toast, (val) => {
  if (val) setTimeout(() => emit('done'), 3000)
})
</script>

<template>
  <div v-if="toast" :class="`toast ${toast.type}`">{{ toast.msg }}</div>
</template>
