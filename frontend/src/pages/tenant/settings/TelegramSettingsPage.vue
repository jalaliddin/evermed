<template>
  <div>
    <div class="text-h5 font-weight-bold mb-6">Telegram sozlamalari</div>

    <v-row>
      <v-col cols="12" md="7">

        <!-- Bot token & Chat ID -->
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-2">Bot ulanish</v-card-title>
          <v-card-text class="pa-4">
            <v-text-field
              v-model="form.bot_token"
              label="Bot Token"
              variant="outlined"
              placeholder="1234567890:AABBCCdd..."
              class="mb-3"
              :type="showToken ? 'text' : 'password'"
              :append-inner-icon="showToken ? 'mdi-eye-off' : 'mdi-eye'"
              @click:append-inner="showToken = !showToken"
            />
            <v-text-field
              v-model="form.group_chat_id"
              label="Guruh / Kanal Chat ID"
              variant="outlined"
              placeholder="-100123456789"
              class="mb-4"
            />
            <div class="d-flex gap-3 flex-wrap">
              <v-btn variant="outlined" prepend-icon="mdi-wifi" :loading="testing" @click="testConnection">
                Ulanishni tekshirish
              </v-btn>
              <v-chip v-if="testResult" :color="testResult === 'ok' ? 'success' : 'error'" label>
                {{ testResult === 'ok' ? '✅ Ulandi' : '❌ Xatolik' }}
              </v-chip>
            </div>
          </v-card-text>
        </v-card>

        <!-- Webhook -->
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-2">
            <v-icon class="mr-2" color="primary">mdi-webhook</v-icon>
            Webhook
          </v-card-title>
          <v-card-text class="pa-4">
            <div class="text-body-2 text-medium-emphasis mb-3">
              Bot orqali <code>/report</code>, <code>/stats</code> buyruqlarini qabul qilish uchun webhookni sozlang.
            </div>
            <v-text-field
              :model-value="webhookUrl"
              label="Webhook manzili"
              variant="outlined"
              readonly
              class="mb-3"
              :append-inner-icon="copied ? 'mdi-check' : 'mdi-content-copy'"
              @click:append-inner="copyWebhook"
            />
            <v-btn
              color="primary"
              variant="tonal"
              prepend-icon="mdi-link-variant"
              :loading="settingWebhook"
              :disabled="!form.bot_token"
              @click="setWebhook"
            >
              Webhookni Telegram'ga ulash
            </v-btn>
            <v-chip v-if="webhookResult" :color="webhookResult === 'ok' ? 'success' : 'error'" label class="ml-3">
              {{ webhookResult === 'ok' ? '✅ Ulandi' : '❌ Xatolik' }}
            </v-chip>
          </v-card-text>
        </v-card>

        <!-- Notification types -->
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-0">Bildirishnoma turlari</v-card-title>
          <v-list>
            <v-list-item v-for="n in notifTypes" :key="n.key" :title="n.label" :subtitle="n.desc">
              <template #append>
                <v-switch v-model="form.notifications[n.key]" color="primary" hide-details />
              </template>
            </v-list-item>
          </v-list>
          <v-card-text class="pa-4 pt-0">
            <v-switch v-model="form.is_active" label="Telegram bildirishnomalarni yoqish" color="primary" hide-details />
          </v-card-text>
        </v-card>

        <v-btn color="primary" size="large" :loading="saving" @click="save">Saqlash</v-btn>
      </v-col>

      <!-- How-to guide -->
      <v-col cols="12" md="5">
        <v-card rounded="xl" variant="tonal" color="primary" class="mb-4">
          <v-card-title class="pa-4 pb-2">
            <v-icon class="mr-2">mdi-information-outline</v-icon>
            Qanday sozlanadi?
          </v-card-title>
          <v-card-text class="pa-4 pt-0">
            <v-timeline density="compact" side="end">
              <v-timeline-item v-for="step in steps" :key="step.n" :dot-color="step.color" size="small">
                <div class="text-body-2"><strong>{{ step.n }}.</strong> {{ step.text }}</div>
              </v-timeline-item>
            </v-timeline>
          </v-card-text>
        </v-card>

        <v-card rounded="xl" variant="tonal" color="blue-grey">
          <v-card-title class="pa-4 pb-2">Bot buyruqlari</v-card-title>
          <v-card-text class="pa-4 pt-0">
            <div v-for="cmd in commands" :key="cmd.cmd" class="d-flex align-center gap-3 mb-2">
              <v-chip label size="small" color="primary" class="font-weight-bold">{{ cmd.cmd }}</v-chip>
              <span class="text-body-2">{{ cmd.desc }}</span>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { tenantApi } from '@/plugins/axios'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()

const saving        = ref(false)
const testing       = ref(false)
const settingWebhook = ref(false)
const showToken     = ref(false)
const testResult    = ref(null)
const webhookResult = ref(null)
const webhookUrl    = ref('')
const copied        = ref(false)
const snackbar      = ref({ show: false, text: '', color: 'success' })

const form = reactive({
  bot_token:     '',
  group_chat_id: '',
  is_active:     true,
  notifications: { appointment: true, payment: true, low_stock: true },
})

const notifTypes = [
  { key: 'appointment', label: 'Qabul bildirishnomalari',  desc: 'Tasdiqlash, bekor qilish, yakunlash' },
  { key: 'payment',     label: "To'lov bildirishnomalari", desc: "To'lov qabul qilinganda" },
  { key: 'low_stock',   label: 'Inventar ogohlantirishlari', desc: 'Mahsulot miqdori minimal chegaradan pasaysa' },
]

const steps = [
  { n: 1, text: "Telegram-da @BotFather botini oching", color: 'primary' },
  { n: 2, text: "/newbot yuboring va yangi bot yarating", color: 'primary' },
  { n: 3, text: "Bot tokenini nusxa olib yuqoridagi maydonga kiriting", color: 'primary' },
  { n: 4, text: "Botni guruh yoki kanalga admin sifatida qo'shing", color: 'primary' },
  { n: 5, text: "Guruh/kanalda @username_bot deb yozing — Chat ID auto aniqlanadi yoki @userinfobot orqali oling", color: 'primary' },
  { n: 6, text: "Saqlang, ulanishni tekshiring", color: 'success' },
  { n: 7, text: "Webhookni ulang (bot buyruqlarini qabul qilish uchun)", color: 'warning' },
]

const commands = [
  { cmd: '/start',  desc: "Botni ishga tushirish va yordam" },
  { cmd: '/report', desc: "Bugungi hisobot (tashriflar, daromad)" },
  { cmd: '/stats',  desc: "Umumiy statistika (bemorlar, shifokorlar)" },
]

async function load() {
  try {
    const [settingsRes, webhookRes] = await Promise.all([
      tenantApi.get('/settings/telegram'),
      tenantApi.get('/settings/telegram/webhook-url'),
    ])
    const s = settingsRes.data
    if (s.bot_token)    form.bot_token     = s.bot_token
    if (s.group_chat_id) form.group_chat_id = s.group_chat_id
    if (s.is_active !== undefined) form.is_active = s.is_active
    if (s.notifications) form.notifications = { ...form.notifications, ...s.notifications }
    webhookUrl.value = webhookRes.data.webhook_url
  } catch { /* not configured yet */ }
}

async function testConnection() {
  testing.value = true
  testResult.value = null
  try {
    await tenantApi.post('/settings/telegram/test')
    testResult.value = 'ok'
  } catch {
    testResult.value = 'error'
  } finally { testing.value = false }
}

async function setWebhook() {
  settingWebhook.value = true
  webhookResult.value = null
  try {
    await save()
    const res = await tenantApi.post('/settings/telegram/set-webhook')
    webhookResult.value = res.data.success ? 'ok' : 'error'
    if (res.data.success) {
      snackbar.value = { show: true, text: 'Webhook muvaffaqiyatli ulandi!', color: 'success' }
    } else {
      snackbar.value = { show: true, text: res.data.message || 'Xatolik', color: 'error' }
    }
  } catch (e) {
    webhookResult.value = 'error'
    snackbar.value = { show: true, text: e.response?.data?.message || 'Xatolik', color: 'error' }
  } finally { settingWebhook.value = false }
}

async function copyWebhook() {
  await navigator.clipboard.writeText(webhookUrl.value)
  copied.value = true
  setTimeout(() => copied.value = false, 2000)
}

async function save() {
  saving.value = true
  try {
    await tenantApi.put('/settings/telegram', form)
    snackbar.value = { show: true, text: 'Saqlandi', color: 'success' }
  } catch {
    snackbar.value = { show: true, text: 'Xatolik', color: 'error' }
  } finally { saving.value = false }
}

onMounted(load)
</script>
