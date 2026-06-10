<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div class="text-h5 font-weight-bold">Klinikalar</div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog()">Yangi klinika</v-btn>
    </div>

    <v-card rounded="xl">
      <div class="pa-4">
        <v-text-field v-model="search" placeholder="Qidirish..." prepend-inner-icon="mdi-magnify" variant="outlined" density="compact" hide-details clearable style="max-width: 320px;" @update:model-value="debouncedLoad" />
      </div>

      <v-data-table-server
        :headers="headers"
        :items="tenants"
        :items-length="total"
        :loading="loading"
        :items-per-page="15"
        density="comfortable"
        @update:options="onOptions"
      >
        <template #item.name="{ item }">
          <div>
            <div class="font-weight-medium">{{ item.name }}</div>
            <div class="text-caption text-medium-emphasis">{{ item.slug }}</div>
          </div>
        </template>
        <template #item.status="{ item }">
          <v-chip :color="item.status === 'active' ? 'success' : 'error'" size="small" label>
            {{ item.status === 'active' ? 'Aktiv' : 'Bloklangan' }}
          </v-chip>
        </template>
        <template #item.plan="{ item }">
          <v-chip color="primary" size="small" variant="tonal">{{ item.plan || 'basic' }}</v-chip>
        </template>
        <template #item.subscription="{ item }">
          <div v-if="item.current_subscription">
            <div class="text-caption">{{ formatDate(item.current_subscription.expires_at) }} ga qadar</div>
            <v-chip :color="isExpiringSoon(item.current_subscription.expires_at) ? 'warning' : 'success'" size="x-small" label>
              {{ daysLeft(item.current_subscription.expires_at) }} kun</v-chip>
          </div>
          <span v-else class="text-medium-emphasis text-caption">Obuna yo'q</span>
        </template>
        <template #item.created_at="{ item }">{{ formatDate(item.created_at) }}</template>
        <template #item.actions="{ item }">
          <v-menu>
            <template #activator="{ props }">
              <v-btn icon="mdi-dots-vertical" size="small" variant="text" v-bind="props" />
            </template>
            <v-list density="compact">
              <v-list-item title="Ko'rish" prepend-icon="mdi-eye" :to="`/admin/tenants/${item.id}`" />
              <v-list-item title="Tahrirlash" prepend-icon="mdi-pencil" @click="openDialog(item)" />
              <v-list-item title="Obuna uzaytirish" prepend-icon="mdi-calendar-plus" @click="openSubscription(item)" />
              <v-list-item title="Parol o'zgartirish" prepend-icon="mdi-key" @click="openResetPassword(item)" />
              <v-divider />
              <v-list-item
                :title="item.status === 'active' ? 'Bloklash' : 'Aktivlashtirish'"
                :prepend-icon="item.status === 'active' ? 'mdi-lock' : 'mdi-lock-open'"
                @click="toggleStatus(item)"
              />
            </v-list>
          </v-menu>
        </template>
      </v-data-table-server>
    </v-card>

    <!-- Tenant Dialog -->
    <v-dialog v-model="dialog" max-width="540">
      <v-card rounded="xl">
        <v-card-title class="pa-4">{{ editTenant ? 'Tahrirlash' : 'Yangi klinika' }}</v-card-title>
        <v-card-text class="pa-4">
          <v-row>
            <v-col cols="12"><v-text-field v-model="form.name" label="Klinika nomi *" variant="outlined" /></v-col>
            <v-col cols="12" sm="6"><v-text-field v-model="form.slug" label="Slug *" variant="outlined" placeholder="mening-klinikam" /></v-col>
            <v-col cols="12" sm="6"><v-text-field v-model="form.phone" label="Telefon" variant="outlined" /></v-col>
            <v-col cols="12"><v-textarea v-model="form.address" label="Manzil" variant="outlined" rows="2" /></v-col>
            <v-col cols="12" sm="6">
              <v-select v-model="form.plan" :items="planItems" label="Tarif rejasi" variant="outlined" />
            </v-col>
            <v-col cols="12" sm="6">
              <v-select v-model="form.status" :items="statusItems" label="Holat" variant="outlined" />
            </v-col>
            <template v-if="!editTenant">
              <v-col cols="12" class="text-subtitle-2 text-medium-emphasis">Admin foydalanuvchi</v-col>
              <v-col cols="12" sm="6"><v-text-field v-model="form.admin_name" label="Ism *" variant="outlined" /></v-col>
              <v-col cols="12" sm="6"><v-text-field v-model="form.admin_email" label="Email *" variant="outlined" type="email" /></v-col>
              <v-col cols="12"><v-text-field v-model="form.admin_password" label="Parol *" variant="outlined" type="password" /></v-col>
            </template>
          </v-row>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn @click="dialog = false">Bekor</v-btn>
          <v-btn color="primary" :loading="saving" @click="saveTenant">Saqlash</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Subscription Dialog -->
    <v-dialog v-model="subDialog" max-width="400">
      <v-card rounded="xl">
        <v-card-title class="pa-4">Obuna uzaytirish — {{ subTarget?.name }}</v-card-title>
        <v-card-text class="pa-4">
          <v-text-field v-model="subMonths" label="Oylar soni" type="number" variant="outlined" class="mb-3" />
          <div class="text-body-2">
            Narx: <strong>{{ (subMonths * 150000).toLocaleString('uz-UZ') }} so'm</strong>
          </div>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn @click="subDialog = false">Bekor</v-btn>
          <v-btn color="primary" :loading="saving" @click="extendSubscription">Uzaytirish</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <!-- Reset Password Dialog -->
    <v-dialog v-model="pwDialog" max-width="400">
      <v-card rounded="xl">
        <v-card-title class="pa-4">Parol o'zgartirish — {{ pwTarget?.name }}</v-card-title>
        <v-card-text class="pa-4">
          <v-text-field v-model="pwForm.email" label="Admin email *" type="email" variant="outlined" class="mb-3" />
          <v-text-field v-model="pwForm.password" label="Yangi parol *" type="password" variant="outlined" />
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn @click="pwDialog = false">Bekor</v-btn>
          <v-btn color="primary" :loading="saving" @click="resetPassword">Saqlash</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import api from '@/plugins/axios'
import dayjs from 'dayjs'

const tenants = ref([])
const total = ref(0)
const loading = ref(false)
const saving = ref(false)
const search = ref('')
const dialog = ref(false)
const subDialog = ref(false)
const pwDialog = ref(false)
const pwTarget = ref(null)
const pwForm = reactive({ email: '', password: '' })
const editTenant = ref(null)
const subTarget = ref(null)
const subMonths = ref(1)
const snackbar = ref({ show: false, text: '', color: 'success' })
let currentOptions = { page: 1, itemsPerPage: 15 }

const form = reactive({ name: '', slug: '', phone: '', address: '', plan: 'basic', status: 'active', admin_name: '', admin_email: '', admin_password: '' })

const headers = [
  { title: 'Klinika', key: 'name' },
  { title: 'Holat', key: 'status', width: 100 },
  { title: 'Plan', key: 'plan', width: 100 },
  { title: 'Obuna', key: 'subscription' },
  { title: 'Yaratildi', key: 'created_at', width: 110 },
  { title: '', key: 'actions', sortable: false, width: 60 },
]

const planItems = [{ title: 'Asosiy', value: 'basic' }, { title: 'Premium', value: 'premium' }]
const statusItems = [{ title: 'Aktiv', value: 'active' }, { title: 'Bloklangan', value: 'blocked' }]

function formatDate(d) { return d ? dayjs(d).format('DD.MM.YYYY') : '' }
function daysLeft(d) { return d ? dayjs(d).diff(dayjs(), 'day') : 0 }
function isExpiringSoon(d) { return daysLeft(d) <= 7 }

let timer = null
function debouncedLoad() { clearTimeout(timer); timer = setTimeout(() => load(currentOptions), 400) }

function onOptions(opts) { currentOptions = opts; load(opts) }

async function load(opts = { page: 1, itemsPerPage: 15 }) {
  loading.value = true
  try {
    const res = await api.get('/admin/tenants', { params: { page: opts.page, per_page: opts.itemsPerPage, search: search.value } })
    tenants.value = res.data.data || []
    total.value = res.data.total || 0
  } finally { loading.value = false }
}

function openDialog(tenant = null) {
  editTenant.value = tenant
  if (tenant) Object.assign(form, { name: tenant.name, slug: tenant.slug, phone: tenant.phone || '', address: tenant.address || '', plan: tenant.plan || 'basic', status: tenant.status })
  else Object.assign(form, { name: '', slug: '', phone: '', address: '', plan: 'basic', status: 'active', admin_name: '', admin_email: '', admin_password: '' })
  dialog.value = true
}

function openSubscription(tenant) {
  subTarget.value = tenant
  subMonths.value = 1
  subDialog.value = true
}

async function saveTenant() {
  saving.value = true
  try {
    if (editTenant.value) await api.put(`/admin/tenants/${editTenant.value.id}`, { name: form.name, phone: form.phone, address: form.address, plan: form.plan, status: form.status })
    else await api.post('/admin/tenants', form)
    snackbar.value = { show: true, text: 'Saqlandi', color: 'success' }
    dialog.value = false
    load(currentOptions)
  } catch (e) {
    snackbar.value = { show: true, text: e.response?.data?.message || 'Xatolik', color: 'error' }
  } finally { saving.value = false }
}

async function extendSubscription() {
  saving.value = true
  try {
    const startsAt = new Date().toISOString().split('T')[0]
    const endsAt = new Date(Date.now() + subMonths.value * 30 * 24 * 60 * 60 * 1000).toISOString().split('T')[0]
    await api.post('/admin/subscriptions', {
      tenant_id: subTarget.value.id,
      starts_at: startsAt,
      ends_at: endsAt,
      amount: subMonths.value * 150000,
    })
    snackbar.value = { show: true, text: 'Obuna uzaytirildi', color: 'success' }
    subDialog.value = false
    load(currentOptions)
  } catch {
    snackbar.value = { show: true, text: 'Xatolik', color: 'error' }
  } finally { saving.value = false }
}

function openResetPassword(tenant) {
  pwTarget.value = tenant
  pwForm.email = ''
  pwForm.password = ''
  pwDialog.value = true
}

async function resetPassword() {
  if (!pwForm.email || !pwForm.password) return
  saving.value = true
  try {
    await api.post(`/admin/tenants/${pwTarget.value.id}/reset-password`, { email: pwForm.email, password: pwForm.password })
    snackbar.value = { show: true, text: 'Parol yangilandi', color: 'success' }
    pwDialog.value = false
  } catch (e) {
    snackbar.value = { show: true, text: e.response?.data?.message || 'Xatolik', color: 'error' }
  } finally { saving.value = false }
}

async function toggleStatus(tenant) {
  const newStatus = tenant.status === 'active' ? 'suspended' : 'active'
  await api.put(`/admin/tenants/${tenant.id}`, { status: newStatus })
  tenant.status = newStatus
}

onMounted(() => load(currentOptions))
</script>
