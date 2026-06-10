<template>
  <div>
    <div class="d-flex align-center justify-space-between mb-6">
      <div class="text-h5 font-weight-bold">Qabullar</div>
      <v-btn color="primary" prepend-icon="mdi-plus" @click="openDialog()">Qabul belgilash</v-btn>
    </div>

    <!-- Filters -->
    <v-card rounded="xl" class="mb-4 pa-4">
      <div class="d-flex flex-wrap gap-3 align-center">
        <v-btn icon="mdi-chevron-left" variant="text" @click="prevDay" />
        <v-text-field
          v-model="selectedDate"
          type="date"
          variant="outlined"
          density="compact"
          hide-details
          style="max-width: 180px;"
          @update:model-value="loadAppointments"
        />
        <v-btn icon="mdi-chevron-right" variant="text" @click="nextDay" />
        <v-btn variant="tonal" size="small" @click="selectedDate = today; loadAppointments()">Bugun</v-btn>

        <v-spacer />

        <v-select
          v-model="filterDoctor"
          :items="doctorItems"
          label="Shifokor"
          variant="outlined"
          density="compact"
          hide-details
          clearable
          style="max-width: 220px;"
          @update:model-value="loadAppointments"
        />

        <v-select
          v-model="filterStatus"
          :items="statusItems"
          label="Holat"
          variant="outlined"
          density="compact"
          hide-details
          clearable
          style="max-width: 180px;"
          @update:model-value="loadAppointments"
        />
      </div>
    </v-card>

    <!-- Appointments Table -->
    <v-card rounded="xl">
      <v-data-table
        :headers="headers"
        :items="appointments"
        :loading="loading"
        density="comfortable"
        :items-per-page="20"
      >
        <template #item.scheduled_at="{ item }">
          <span class="font-weight-medium">{{ formatTime(item.scheduled_at) }}</span>
        </template>
        <template #item.patient="{ item }">
          <router-link :to="`/patients/${item.patient_id}`" class="text-decoration-none">
            {{ item.patient?.full_name }}
          </router-link>
        </template>
        <template #item.doctor="{ item }">Dr. {{ item.doctor?.user?.name }}</template>
        <template #item.service="{ item }">{{ item.service?.name || '—' }}</template>
        <template #item.status="{ item }">
          <v-chip :color="statusColor(item.status)" size="small" label>{{ statusLabel(item.status) }}</v-chip>
        </template>
        <template #item.actions="{ item }">
          <v-menu>
            <template #activator="{ props }">
              <v-btn icon="mdi-dots-vertical" size="small" variant="text" v-bind="props" />
            </template>
            <v-list density="compact">
              <v-list-item title="Tasdiqlash" prepend-icon="mdi-check" @click="changeStatus(item, 'confirmed')" v-if="item.status === 'pending'" />
              <v-list-item title="Qabul qilish" prepend-icon="mdi-play" @click="changeStatus(item, 'in_progress')" v-if="['pending','confirmed'].includes(item.status)" />
              <v-list-item title="Yakunlash" prepend-icon="mdi-check-all" @click="changeStatus(item, 'completed')" v-if="item.status === 'in_progress'" />
              <v-list-item title="Bekor qilish" prepend-icon="mdi-close" @click="changeStatus(item, 'cancelled')" v-if="!['completed','cancelled'].includes(item.status)" />
              <v-divider />
              <v-list-item title="Tahrirlash" prepend-icon="mdi-pencil" @click="openDialog(item)" />
            </v-list>
          </v-menu>
        </template>
      </v-data-table>
    </v-card>

    <!-- Appointment Form Dialog -->
    <v-dialog v-model="dialog" max-width="600">
      <v-card rounded="xl">
        <v-card-title class="pa-4">{{ editItem ? 'Qabulni tahrirlash' : 'Qabul belgilash' }}</v-card-title>
        <v-card-text class="pa-4">
          <v-row>
            <v-col cols="12">
              <v-autocomplete
                v-model="form.patient_id"
                :items="patientItems"
                label="Bemor *"
                variant="outlined"
                :loading="patientsLoading"
                @update:search="searchPatients"
                no-data-text="Topilmadi"
              />
            </v-col>
            <v-col cols="12" sm="6">
              <v-select v-model="form.doctor_id" :items="doctorItems" label="Shifokor *" variant="outlined" @update:model-value="loadSlots" />
            </v-col>
            <v-col cols="12" sm="6">
              <v-select v-model="form.service_id" :items="serviceItems" label="Xizmat" variant="outlined" clearable />
            </v-col>
            <v-col cols="12" sm="6">
              <v-text-field v-model="form.date" type="date" label="Sana *" variant="outlined" @update:model-value="loadSlots" />
            </v-col>
            <v-col cols="12" sm="6">
              <v-select
                v-model="form.time"
                :items="availableSlots"
                label="Vaqt *"
                variant="outlined"
                :loading="slotsLoading"
                no-data-text="Bo'sh vaqt yo'q"
              />
            </v-col>
            <v-col cols="12">
              <v-textarea v-model="form.notes" label="Eslatma" variant="outlined" rows="2" />
            </v-col>
          </v-row>
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn @click="dialog = false">Bekor</v-btn>
          <v-btn color="primary" :loading="saving" @click="save">Saqlash</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { tenantApi } from '@/plugins/axios'
import dayjs from 'dayjs'

const appointments = ref([])
const loading = ref(false)
const saving = ref(false)
const dialog = ref(false)
const editItem = ref(null)
const selectedDate = ref(dayjs().format('YYYY-MM-DD'))
const today = dayjs().format('YYYY-MM-DD')
const filterDoctor = ref(null)
const filterStatus = ref(null)
const snackbar = ref({ show: false, text: '', color: 'success' })

const doctors = ref([])
const services = ref([])
const availableSlots = ref([])
const slotsLoading = ref(false)
const patientItems = ref([])
const patientsLoading = ref(false)

const form = reactive({ patient_id: null, doctor_id: null, service_id: null, date: selectedDate.value, time: null, notes: '' })

const headers = [
  { title: 'Vaqt', key: 'scheduled_at', width: 80 },
  { title: 'Bemor', key: 'patient' },
  { title: 'Shifokor', key: 'doctor' },
  { title: 'Xizmat', key: 'service' },
  { title: 'Holat', key: 'status', width: 130 },
  { title: '', key: 'actions', sortable: false, width: 60 },
]

const doctorItems = computed(() => doctors.value.map(d => ({ title: `Dr. ${d.user?.name}`, value: d.id })))
const serviceItems = computed(() => services.value.map(s => ({ title: `${s.name} — ${formatMoney(s.price)}`, value: s.id })))
const statusItems = [
  { title: 'Kutilmoqda', value: 'pending' },
  { title: 'Tasdiqlangan', value: 'confirmed' },
  { title: 'Qabulda', value: 'in_progress' },
  { title: 'Tugadi', value: 'completed' },
  { title: 'Bekor', value: 'cancelled' },
]

function formatTime(d) { return d ? dayjs(d).format('HH:mm') : '' }
function formatMoney(v) { return v ? Number(v).toLocaleString('uz-UZ') : '0' }
function statusColor(s) { return {pending:'warning',confirmed:'info',in_progress:'primary',completed:'success',cancelled:'error'}[s]||'grey' }
function statusLabel(s) { return {pending:'Kutilmoqda',confirmed:'Tasdiqlangan',in_progress:'Qabulda',completed:'Tugadi',cancelled:'Bekor'}[s]||s }
function prevDay() { selectedDate.value = dayjs(selectedDate.value).subtract(1,'day').format('YYYY-MM-DD'); loadAppointments() }
function nextDay() { selectedDate.value = dayjs(selectedDate.value).add(1,'day').format('YYYY-MM-DD'); loadAppointments() }

let searchTimer = null
async function searchPatients(q) {
  if (!q || q.length < 2) return
  clearTimeout(searchTimer)
  searchTimer = setTimeout(async () => {
    patientsLoading.value = true
    const res = await tenantApi.get('/patients', { params: { search: q, per_page: 20 } })
    patientItems.value = (res.data.data || []).map(p => ({ title: p.full_name + (p.phone ? ` (${p.phone})` : ''), value: p.id }))
    patientsLoading.value = false
  }, 300)
}

async function loadSlots() {
  if (!form.doctor_id || !form.date) return
  slotsLoading.value = true
  try {
    const res = await tenantApi.get('/appointments/available-slots', { params: { doctor_id: form.doctor_id, date: form.date } })
    availableSlots.value = res.data
  } finally { slotsLoading.value = false }
}

async function loadAppointments() {
  loading.value = true
  try {
    const params = { date: selectedDate.value, per_page: 50 }
    if (filterDoctor.value) params.doctor_id = filterDoctor.value
    if (filterStatus.value) params.status = filterStatus.value
    const res = await tenantApi.get('/appointments', { params })
    appointments.value = res.data.data || []
  } finally { loading.value = false }
}

function openDialog(item = null) {
  editItem.value = item
  if (item) {
    Object.assign(form, {
      patient_id: item.patient_id, doctor_id: item.doctor_id,
      service_id: item.service_id, date: dayjs(item.scheduled_at).format('YYYY-MM-DD'),
      time: dayjs(item.scheduled_at).format('HH:mm'), notes: item.notes,
    })
    patientItems.value = [{ title: item.patient?.full_name, value: item.patient_id }]
  } else {
    Object.assign(form, { patient_id: null, doctor_id: null, service_id: null, date: selectedDate.value, time: null, notes: '' })
    availableSlots.value = []
  }
  dialog.value = true
}

async function save() {
  if (!form.patient_id || !form.doctor_id || !form.date || !form.time) {
    snackbar.value = { show: true, text: 'Barcha majburiy maydonlarni to\'ldiring', color: 'error' }
    return
  }
  saving.value = true
  try {
    const payload = {
      ...form,
      scheduled_at: form.date + 'T' + form.time + ':00',
    }
    if (editItem.value) {
      await tenantApi.put(`/appointments/${editItem.value.id}`, payload)
    } else {
      await tenantApi.post('/appointments', payload)
    }
    snackbar.value = { show: true, text: 'Saqlandi', color: 'success' }
    dialog.value = false
    loadAppointments()
  } catch {
    snackbar.value = { show: true, text: 'Xatolik', color: 'error' }
  } finally { saving.value = false }
}

async function changeStatus(item, status) {
  await tenantApi.patch(`/appointments/${item.id}/status`, { status })
  snackbar.value = { show: true, text: 'Holat yangilandi', color: 'success' }
  loadAppointments()
}

onMounted(async () => {
  const [dRes, sRes] = await Promise.all([
    tenantApi.get('/doctors?per_page=50'),
    tenantApi.get('/services?per_page=100&is_active=true'),
  ])
  doctors.value = dRes.data.data || []
  services.value = sRes.data.data || []
  loadAppointments()
})
</script>
