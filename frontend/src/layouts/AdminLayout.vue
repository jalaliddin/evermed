<template>
  <v-app>
    <v-navigation-drawer permanent :width="240" style="background: #0D1B2A;">
      <div class="pa-4">
        <div class="text-h6 font-weight-bold text-white">EverMED</div>
        <div class="text-caption" style="color: rgba(255,255,255,0.5);">Super Admin Panel</div>
      </div>
      <v-divider style="border-color: rgba(255,255,255,0.1);" />
      <v-list density="compact" nav class="pa-2" style="color: rgba(255,255,255,0.75);">
        <v-list-item to="/admin" prepend-icon="mdi-view-dashboard-outline" title="Dashboard" rounded="lg" active-color="white" active-class="sidebar-active" exact />
        <v-list-item to="/admin/tenants" prepend-icon="mdi-hospital-building" title="Klinikalar" rounded="lg" active-color="white" active-class="sidebar-active" />
        <v-list-item to="/admin/subscriptions" prepend-icon="mdi-credit-card-outline" title="Obunalar" rounded="lg" active-color="white" active-class="sidebar-active" />
      </v-list>
      <template #append>
        <div class="pa-3">
          <v-btn block variant="tonal" color="error" prepend-icon="mdi-logout" @click="doLogout">Chiqish</v-btn>
        </div>
      </template>
    </v-navigation-drawer>

    <v-app-bar elevation="0" border="b" height="64">
      <v-app-bar-title>
        <span class="text-body-1 font-weight-medium">Super Admin Panel</span>
      </v-app-bar-title>
      <template #append>
        <v-chip color="primary" class="mr-4">{{ auth.user?.name }}</v-chip>
      </template>
    </v-app-bar>

    <v-main>
      <v-container fluid class="pa-6">
        <router-view />
      </v-container>
    </v-main>
  </v-app>
</template>

<script setup>
import { useAuthStore } from '@/stores/auth'
import { useRouter } from 'vue-router'

const auth = useAuthStore()
const router = useRouter()

async function doLogout() {
  await auth.logout()
  router.push('/login')
}
</script>

<style scoped>
.sidebar-active { background: rgba(21,101,192,0.25) !important; color: #fff !important; }
</style>
