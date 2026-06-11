<template>
  <v-app style="background: #0D1B2A;">
    <v-main>
      <v-container class="d-flex align-center justify-center" style="min-height: 100vh;">
        <v-card width="420" rounded="xl" elevation="24">
          <div class="text-center pa-8 pb-4">
            <v-avatar color="primary" size="64" class="mb-4">
              <v-icon size="36" color="white">mdi-hospital-building</v-icon>
            </v-avatar>
            <div class="text-h5 font-weight-bold">EverMED CRM</div>
            <div class="text-body-2 text-medium-emphasis mt-1">Tizimga kirish</div>
          </div>

          <v-card-text class="pa-8 pt-4">
            <v-form @submit.prevent="handleLogin">
              <v-text-field
                v-model="form.email"
                label="Email manzil"
                type="email"
                variant="outlined"
                prepend-inner-icon="mdi-email-outline"
                :error-messages="errors.email"
                class="mb-3"
                autofocus
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
                class="mb-5"
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
          </v-card-text>

          <v-divider />
          <div class="text-center pa-4 text-caption text-medium-emphasis">
            © {{ new Date().getFullYear() }} Eversoft | med.eversoft.uz
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

const form = reactive({ email: '', password: '' })
const errors = reactive({ email: '', password: '' })
const showPassword = ref(false)
const loading = ref(false)
const snackbar = ref(false)
const errorMsg = ref('')

async function handleLogin() {
  errors.email = ''
  errors.password = ''
  loading.value = true

  try {
    const data = await auth.login({ email: form.email, password: form.password })
    if (data.type === 'admin') {
      router.push('/admin')
    } else {
      router.push('/dashboard/home')
    }
  } catch (err) {
    const errs = err.response?.data?.errors
    if (errs?.email)    { errors.email    = errs.email[0];    return }
    if (errs?.password) { errors.password = errs.password[0]; return }
    errorMsg.value = err.response?.data?.message || 'Ulanish xatosi. Qayta urinib ko\'ring.'
    snackbar.value = true
  } finally {
    loading.value = false
  }
}
</script>
