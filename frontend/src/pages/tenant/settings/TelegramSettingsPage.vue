<template>
  <div>
    <div class="text-h5 font-weight-bold mb-6">Telegram sozlamalari</div>

    <v-row>
      <v-col cols="12" md="7">
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-0">Bot ulanish</v-card-title>
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
              label="Chat ID"
              variant="outlined"
              placeholder="-100123456789"
              class="mb-4"
            />
            <v-btn variant="outlined" prepend-icon="mdi-wifi" :loading="testing" @click="testConnection">
              Ulanishni tekshirish
            </v-btn>
            <v-chip v-if="testResult" :color="testResult === 'ok' ? 'success' : 'error'" class="ml-3">
              {{ testResult === 'ok' ? 'Ulandi!' : 'Xatolik' }}
            </v-chip>
          </v-card-text>
        </v-card>

        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-0">Bildirishnoma turlari</v-card-title>
          <v-list>
            <v-list-item v-for="n in notifTypes" :key="n.key" :title="n.label" :subtitle="n.desc">
              <template #append>
                <v-switch v-model="form.notifications[n.key]" color="primary" hide-details />
              </template>
            </v-list-item>
          </v-list>
        </v-card>

        <v-btn color="primary" size="large" :loading="saving" @click="save">Saqlash</v-btn>
      </v-col>

      <v-col cols="12" md="5">
        <v-card rounded="xl" color="blue-grey-darken-4">
          <v-card-title class="pa-4 pb-2">
            <v-icon color="primary" class="mr-2">mdi-information-outline</v-icon>
            Qanday sozlanadi?
          </v-card-title>
          <v-card-text class="pa-4">
            <ol class="pl-4">
              <li class="mb-2">Telegram-da <strong>@BotFather</strong> botini oching</li>
              <li class="mb-2"><code>/newbot</code> yuboring va yangi bot yarating</li>
              <li class="mb-2">Bot tokenini nusxa oling va yuqoridagi maydonga kiriting</li>
              <li class="mb-2">Xabar yubormoqchi bo'lgan guruh/kanalga botni qo'shing</li>
              <li class="mb-2">Guruhdan Chat ID oling (bot orqali yoki Telegram API orqali)</li>
              <li>Saqlang va ulanishni tekshiring</li>
            </ol>
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

const saving = ref(false)
const testing = ref(false)
const showToken = ref(false)
const testResult = ref(null)
const snackbar = ref({ show: false, text: '', color: 'success' })

const form = reactive({
  bot_token: '',
  group_chat_id: '',
  is_active: true,
  notifications: { appointment: true, payment: true, low_stock: true },
})

const notifTypes = [
  { key: 'appointment', label: 'Qabul bildirishnomalari', desc: 'Yangi qabul belgilanganda' },
  { key: 'payment', label: "To'lov bildirishnomalari", desc: "To'lov qabul qilinganda" },
  { key: 'low_stock', label: 'Inventar ogohlantirishlari', desc: "Mahsulot miqdori kamayib ketganda" },
]

async function load() {
  try {
    const res = await tenantApi.get('/settings/telegram')
    const s = res.data
    if (s.bot_token) form.bot_token = s.bot_token
    if (s.group_chat_id) form.group_chat_id = s.group_chat_id
    if (s.is_active !== undefined) form.is_active = s.is_active
    if (s.notifications) form.notifications = { ...form.notifications, ...s.notifications }
  } catch { /* not configured yet */ }
}

async function testConnection() {
  testing.value = true
  testResult.value = null
  try {
    await tenantApi.post('/settings/telegram/test')
    testResult.value = 'ok'
  } catch { testResult.value = 'error' }
  finally { testing.value = false }
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
