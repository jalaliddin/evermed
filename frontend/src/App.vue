<template>
  <router-view />
</template>

<script setup>
import { onMounted } from 'vue'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
onMounted(async () => {
  if (auth.isLoggedIn) {
    try { await auth.fetchMe() } catch {}
  }
})
</script>
