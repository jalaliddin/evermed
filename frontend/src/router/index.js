import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const routes = [
  // Public
  { path: '/', component: () => import('@/pages/landing/LandingPage.vue'), meta: { public: true } },
  { path: '/login', component: () => import('@/pages/auth/LoginPage.vue'), meta: { public: true } },

  // Tenant panel
  {
    path: '/dashboard',
    component: () => import('@/layouts/TenantLayout.vue'),
    meta: { requiresAuth: true, type: 'tenant' },
    children: [
      { path: '', redirect: '/dashboard/home' },
      { path: '/dashboard/home', component: () => import('@/pages/tenant/DashboardPage.vue') },

      // Patients
      { path: '/patients', component: () => import('@/pages/tenant/patients/PatientListPage.vue') },
      { path: '/patients/new', component: () => import('@/pages/tenant/patients/PatientFormPage.vue') },
      { path: '/patients/:id', component: () => import('@/pages/tenant/patients/PatientProfilePage.vue') },
      { path: '/patients/:id/edit', component: () => import('@/pages/tenant/patients/PatientFormPage.vue') },

      // Doctors (read-only for receptionist)
      { path: '/doctors', component: () => import('@/pages/tenant/doctors/DoctorListPage.vue') },
      { path: '/doctors/:id', component: () => import('@/pages/tenant/doctors/DoctorProfilePage.vue') },
      // Admin only
      { path: '/doctors/new', component: () => import('@/pages/tenant/doctors/DoctorFormPage.vue'), meta: { requiresAdmin: true } },

      // Services (admin only)
      { path: '/services', component: () => import('@/pages/tenant/services/ServiceListPage.vue'), meta: { requiresAdmin: true } },

      // Appointments
      { path: '/appointments', component: () => import('@/pages/tenant/appointments/AppointmentCalendarPage.vue') },

      // Visits
      { path: '/visits/new', component: () => import('@/pages/tenant/visits/VisitFormPage.vue') },
      { path: '/visits/:id', component: () => import('@/pages/tenant/visits/VisitDetailPage.vue') },

      // Inventory (admin only)
      { path: '/inventory', component: () => import('@/pages/tenant/inventory/InventoryListPage.vue'), meta: { requiresAdmin: true } },

      // Reports (admin only)
      { path: '/reports', component: () => import('@/pages/tenant/reports/ReportsPage.vue'), meta: { requiresAdmin: true } },

      // Notifications
      { path: '/notifications', component: () => import('@/pages/tenant/NotificationsPage.vue') },

      // Settings (admin only)
      { path: '/settings/clinic',   component: () => import('@/pages/tenant/settings/ClinicSettingsPage.vue'),   meta: { requiresAdmin: true } },
      { path: '/settings/telegram', component: () => import('@/pages/tenant/settings/TelegramSettingsPage.vue'), meta: { requiresAdmin: true } },
      { path: '/settings/users',    component: () => import('@/pages/tenant/settings/UsersSettingsPage.vue'),    meta: { requiresAdmin: true } },
      { path: '/settings/printer',  component: () => import('@/pages/tenant/settings/PrinterSettingsPage.vue'),  meta: { requiresAdmin: true } },
    ],
  },

  // Super Admin panel
  {
    path: '/admin',
    component: () => import('@/layouts/AdminLayout.vue'),
    meta: { requiresAuth: true, type: 'admin' },
    children: [
      { path: '', component: () => import('@/pages/admin/AdminDashboardPage.vue') },
      { path: 'tenants', component: () => import('@/pages/admin/TenantsListPage.vue') },
      { path: 'subscriptions', component: () => import('@/pages/admin/SubscriptionsPage.vue') },
    ],
  },

  { path: '/:pathMatch(.*)*', redirect: '/' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (!to.meta.public && !auth.isLoggedIn) return '/login'
  if (to.path === '/login' && auth.isLoggedIn) {
    return auth.isAdmin ? '/admin' : '/dashboard/home'
  }
  if (to.meta.type === 'admin' && !auth.isAdmin) return '/dashboard/home'
  if (to.meta.type === 'tenant' && auth.isAdmin) return '/admin'
  if (to.meta.requiresAdmin && auth.user?.role !== 'admin') return '/dashboard/home'
})

export default router
