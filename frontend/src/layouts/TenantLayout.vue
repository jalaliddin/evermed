<template>
  <v-app>
    <!-- Sidebar Navigation Drawer -->
    <v-navigation-drawer
      v-model="drawer"
      :rail="rail"
      permanent
      :width="260"
      theme="dark"
      style="background: #0D1B2A; border-right: 1px solid rgba(255,255,255,0.06);"
    >
      <!-- Logo & Clinic Name -->
      <div class="d-flex align-center pa-4" style="min-height: 64px;">
        <v-avatar color="primary" size="36" class="flex-shrink-0">
          <v-icon color="white" size="20">mdi-hospital-building</v-icon>
        </v-avatar>
        <transition name="fade">
          <div v-if="!rail" class="ml-3 overflow-hidden">
            <div class="text-white font-weight-bold text-body-1 text-truncate">EverMED CRM</div>
            <div class="text-caption" style="color: rgba(255,255,255,0.5);">Salomatlik Klinikasi</div>
          </div>
        </transition>
      </div>

      <v-divider style="border-color: rgba(255,255,255,0.08);" />

      <!-- Navigation Items -->
      <v-list density="compact" nav class="pa-2" style="color: rgba(255,255,255,0.75);">
        <!-- Asosiy -->
        <v-list-subheader v-if="!rail" style="color: rgba(255,255,255,0.35); font-size:10px; letter-spacing:1px;">ASOSIY</v-list-subheader>

        <v-list-item
          v-for="item in mainMenu"
          :key="item.to"
          :to="item.to"
          :prepend-icon="item.icon"
          :title="item.title"
          rounded="lg"
          active-color="white"
          :active-class="'sidebar-active'"
          style="margin: 2px 0;"
        />

        <v-divider class="my-2" style="border-color: rgba(255,255,255,0.08);" />
        <v-list-subheader v-if="!rail" style="color: rgba(255,255,255,0.35); font-size:10px; letter-spacing:1px;">KLINIKA</v-list-subheader>

        <v-list-item
          v-for="item in clinicMenu"
          :key="item.to"
          :to="item.to"
          :prepend-icon="item.icon"
          :title="item.title"
          rounded="lg"
          active-color="white"
          :active-class="'sidebar-active'"
          style="margin: 2px 0;"
        />

        <v-divider class="my-2" style="border-color: rgba(255,255,255,0.08);" />
        <v-list-subheader v-if="!rail" style="color: rgba(255,255,255,0.35); font-size:10px; letter-spacing:1px;">MOLIYA</v-list-subheader>

        <v-list-item
          v-for="item in financeMenu"
          :key="item.to"
          :to="item.to"
          :prepend-icon="item.icon"
          :title="item.title"
          rounded="lg"
          active-color="white"
          :active-class="'sidebar-active'"
          style="margin: 2px 0;"
        />

        <v-divider class="my-2" style="border-color: rgba(255,255,255,0.08);" />
        <v-list-subheader v-if="!rail" style="color: rgba(255,255,255,0.35); font-size:10px; letter-spacing:1px;">TIZIM</v-list-subheader>

        <v-list-item
          v-for="item in systemMenu"
          :key="item.to"
          :to="item.to"
          :prepend-icon="item.icon"
          :title="item.title"
          rounded="lg"
          active-color="white"
          :active-class="'sidebar-active'"
          style="margin: 2px 0;"
        >
          <template v-if="item.badge" #append>
            <v-badge :content="item.badge" color="error" inline v-if="!rail" />
          </template>
        </v-list-item>

        <template v-if="isAdmin">
          <v-divider class="my-2" style="border-color: rgba(255,255,255,0.08);" />
          <v-list-subheader v-if="!rail" style="color: rgba(255,255,255,0.35); font-size:10px; letter-spacing:1px;">SOZLAMALAR</v-list-subheader>

          <v-list-item
            v-for="item in settingsMenu"
            :key="item.to"
            :to="item.to"
            :prepend-icon="item.icon"
            :title="item.title"
            rounded="lg"
            active-color="white"
            :active-class="'sidebar-active'"
            style="margin: 2px 0;"
          />
        </template>
      </v-list>

      <!-- User Profile at Bottom -->
      <template #append>
        <v-divider style="border-color: rgba(255,255,255,0.08);" />
        <div class="pa-3 d-flex align-center">
          <v-avatar color="primary" size="32" class="flex-shrink-0">
            <v-icon size="18" color="white">mdi-account</v-icon>
          </v-avatar>
          <transition name="fade">
            <div v-if="!rail" class="ml-3 flex-grow-1 overflow-hidden">
              <div class="text-white text-body-2 font-weight-medium text-truncate">{{ auth.user?.name }}</div>
              <div class="text-caption text-truncate" style="color: rgba(255,255,255,0.5);">{{ roleLabel }}</div>
            </div>
          </transition>
          <v-btn
            v-if="!rail"
            icon="mdi-logout"
            variant="text"
            size="small"
            style="color: rgba(255,255,255,0.5);"
            @click="doLogout"
          />
        </div>
      </template>
    </v-navigation-drawer>

    <!-- Top App Bar -->
    <v-app-bar elevation="0" border="b" height="64">
      <v-btn
        :icon="rail ? 'mdi-menu' : 'mdi-menu-open'"
        variant="text"
        @click="rail = !rail"
      />

      <!-- Search -->
      <v-text-field
        v-model="search"
        prepend-inner-icon="mdi-magnify"
        placeholder="Qidirish..."
        variant="outlined"
        density="compact"
        hide-details
        rounded="lg"
        style="max-width: 320px;"
        class="mx-4"
      />

      <v-spacer />

      <!-- Notifications Bell -->
      <v-btn icon variant="text" class="mr-1" @click="$router.push('/notifications')">
        <v-badge :content="notifStore.unreadCount || ''" :model-value="notifStore.unreadCount > 0" color="error">
          <v-icon>mdi-bell-outline</v-icon>
        </v-badge>
      </v-btn>

      <!-- Profile menu -->
      <v-menu>
        <template #activator="{ props }">
          <v-btn variant="text" v-bind="props" class="mr-2">
            <v-avatar color="primary" size="32" class="mr-2">
              <span class="text-caption font-weight-bold text-white">{{ initials }}</span>
            </v-avatar>
            <span class="text-body-2">{{ auth.user?.name }}</span>
            <v-icon size="18" class="ml-1">mdi-chevron-down</v-icon>
          </v-btn>
        </template>
        <v-list>
          <v-list-item prepend-icon="mdi-account-circle" title="Profil" />
          <v-divider />
          <v-list-item prepend-icon="mdi-logout" title="Chiqish" @click="doLogout" />
        </v-list>
      </v-menu>
    </v-app-bar>

    <!-- Main Content -->
    <v-main>
      <v-container fluid class="pa-6">
        <router-view />
      </v-container>
    </v-main>

    <!-- Global Snackbar -->
    <v-snackbar v-model="snackbar.show" :color="snackbar.color" :timeout="3000" location="top right">
      {{ snackbar.text }}
    </v-snackbar>
  </v-app>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificationStore } from '@/stores/notifications'

const auth = useAuthStore()
const notifStore = useNotificationStore()
const router = useRouter()

const drawer = ref(true)
const rail = ref(false)
const search = ref('')
const snackbar = ref({ show: false, text: '', color: 'success' })

const isAdmin = computed(() => auth.user?.role === 'admin')

const initials = computed(() => {
  const name = auth.user?.name || ''
  return name.split(' ').map(n => n[0]).join('').toUpperCase().slice(0, 2)
})

const roleLabel = computed(() => {
  const labels = { admin: 'Admin', receptionist: 'Registrator' }
  return labels[auth.user?.role] || auth.user?.role
})

const mainMenu = [
  { to: '/dashboard/home', icon: 'mdi-view-dashboard-outline', title: 'Dashboard' },
  { to: '/appointments',   icon: 'mdi-calendar-check-outline', title: 'Qabullar' },
  { to: '/patients',       icon: 'mdi-account-group-outline',  title: 'Bemorlar' },
]

const clinicMenu = computed(() => {
  const items = [
    { to: '/doctors', icon: 'mdi-doctor', title: 'Shifokorlar' },
  ]
  if (isAdmin.value) {
    items.push(
      { to: '/services',   icon: 'mdi-medical-bag',            title: 'Xizmatlar' },
      { to: '/inventory',  icon: 'mdi-package-variant-closed', title: 'Inventar' },
    )
  }
  return items
})

const financeMenu = computed(() => {
  const items = [
    { to: '/visits/new', icon: 'mdi-cash-register', title: "To'lovlar & Cheklar" },
  ]
  if (isAdmin.value) {
    items.push({ to: '/reports', icon: 'mdi-chart-bar', title: 'Hisobotlar' })
  }
  return items
})

const systemMenu = [
  { to: '/notifications', icon: 'mdi-bell-outline', title: 'Bildirishnomalar', badge: notifStore.unreadCount || null },
]

const settingsMenu = [
  { to: '/settings/clinic',    icon: 'mdi-hospital-building',  title: 'Klinika' },
  { to: '/settings/telegram',  icon: 'mdi-send',               title: 'Telegram' },
  { to: '/settings/users',     icon: 'mdi-account-group',      title: 'Foydalanuvchilar' },
  { to: '/settings/printer',   icon: 'mdi-printer',            title: 'Printer' },
]

async function doLogout() {
  await auth.logout()
  router.push('/login')
}

onMounted(() => {
  notifStore.fetchUnreadCount()
  setInterval(() => notifStore.fetchUnreadCount(), 30000)
})
</script>

<style>
.sidebar-active {
  background: rgba(21, 101, 192, 0.25) !important;
  border-left: 3px solid #1565C0;
  border-radius: 0 8px 8px 0 !important;
  color: #fff !important;
}
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
