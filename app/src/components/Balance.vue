<script setup>
defineProps({ balance: Number, currency: String, ownerName: String })
</script>

<template>
  <div class="balance-block">
    <div class="bal-label">Current balance</div>
    <div class="bal-amt">
      <span class="cur">{{ currency }}</span>{{ balance?.toFixed(2) }}
    </div>
    <div class="bal-owner">{{ ownerName }}</div>
  </div>
</template>
