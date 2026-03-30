<script setup>
defineProps({ ownerName: String })
const ACCOUNT_ID = 1
</script>

<template>
  <div class="header">
    <div>
      <h1>Mini Banking</h1>
      <div class="acc">ACC #{{ String(ACCOUNT_ID).padStart(6, '0') }} · {{ ownerName }}</div>
    </div>
    <div class="dot" title="Connected" />
  </div>
</template>
