<template>
  <v-app style="background: #0D1B2A;">
    <v-main>
      <v-container class="d-flex align-center justify-center" style="min-height: 100vh;">
        <v-card width="440" rounded="xl" elevation="24">
          <!-- Header -->
          <div class="text-center pa-8 pb-4">
            <v-avatar color="primary" size="64" class="mb-4">
              <v-icon size="36" color="white">mdi-hospital-building</v-icon>
            </v-avatar>
            <div class="text-h5 font-weight-bold">EverMED CRM</div>
            <div class="text-body-2 text-medium-emphasis mt-1">Tizimga kirish</div>
          </div>

          <v-card-text class="pa-8 pt-4">
            <!-- Login type toggle -->
            <v-btn-toggle v-model="loginType" mandatory rounded="lg" color="primary" class="mb-5 w-100">
              <v-btn value="tenant" class="flex-grow-1">Klinika</v-btn>
              <v-btn value="admin" class="flex-grow-1">Super Admin</v-btn>
            </v-btn-toggle>

            <v-form @submit.prevent="handleLogin">
              <!-- Tenant slug (only for clinic login) -->
              <v-text-field
                v-if="loginType === 'tenant'"
                v-model="form.tenant"
                label="Klinika slugi"
                variant="outlined"
                prepend-inner-icon="mdi-hospital-building"
                :error-messages="errors.tenant"
                hint="Masalan: demo"
                persistent-hint
                class="mb-3"
              />

              <v-text-field
                v-model="form.email"
                label="Email manzil"
                type="email"
                variant="outlined"
                prepend-inner-icon="mdi-email-outline"
                :error-messages="errors.email"
                class="mb-3"
              />

              <v-text-field
                v-model="form.password"
                label="Parol"
                :type="showPassword ? 'text' : 'password'"
                variant="outlined"
                prepend-inner-icon="mdi-lock-outline"
                :append-inner-icon="showPassword ? 'mdi-eye-off' : 'mdi-eye'"
                @click:append-inner="showPassword = !showPassword"
                :error-messages="errors.password"
                class="mb-4"
              />

              <v-btn
                type="submit"
                color="primary"
                block
                size="large"
                :loading="loading"
                rounded="lg"
              >
                Kirish
              </v-btn>
            </v-form>

            <!-- Demo credentials hint -->
            <v-card variant="tonal" color="info" class="mt-4 pa-3" rounded="lg">
              <div class="text-caption font-weight-medium mb-1">Demo kirish:</div>
              <div class="text-caption">Klinika slug: <strong>demo</strong></div>
              <div class="text-caption">Admin: <strong>admin@demo.com</strong> / password123</div>
              <div class="text-caption">Super: <strong>admin@eversoft.uz</strong> / admin123</div>
            </v-card>
          </v-card-text>

          <v-divider />
          <div class="text-center pa-4 text-caption text-medium-emphasis">
            © 2025 Eversoft | med.eversoft.uz
          </div>
        </v-card>
      </v-container>
    </v-main>

    <v-snackbar v-model="snackbar" color="error" :timeout="4000">
      {{ errorMsg }}
    </v-snackbar>
  </v-app>
</template>

<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const router = useRouter()

const loginType = ref('tenant')
const form = reactive({ email: '', password: '', tenant: '' })
const errors = reactive({ email: '', password: '', tenant: '' })
const showPassword = ref(false)
const loading = ref(false)
const snackbar = ref(false)
const errorMsg = ref('')

async function handleLogin() {
  errors.email = ''
  errors.password = ''
  errors.tenant = ''
  loading.value = true

  try {
    const payload = { email: form.email, password: form.password }
    if (loginType.value === 'tenant') {
      if (!form.tenant) {
        errors.tenant = 'Klinika slugini kiriting'
        return
      }
      payload.tenant = form.tenant.trim().toLowerCase()
    }

    const data = await auth.login(payload)
    if (data.type === 'admin') {
      router.push('/admin')
    } else {
      router.push('/dashboard/home')
    }
  } catch (err) {
    const errs = err.response?.data?.errors
    if (errs?.tenant) { errors.tenant = errs.tenant[0]; return }
    if (errs?.email)  { errors.email  = errs.email[0];  return }
    errorMsg.value = err.response?.data?.message || 'Ulanish xatosi. Qayta urinib ko\'ring.'
    snackbar.value = true
  } finally {
    loading.value = false
  }
}
</script>
