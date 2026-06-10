<template>
  <div v-if="doctor">
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" class="mr-2" to="/doctors" />
      <div class="text-h5 font-weight-bold">Shifokor profili</div>
      <v-spacer />
      <v-btn variant="outlined" prepend-icon="mdi-pencil" :to="`/doctors/${doctor.id}/edit`">Tahrirlash</v-btn>
    </div>

    <v-row>
      <v-col cols="12" md="4">
        <v-card rounded="xl" class="text-center pa-6 mb-4">
          <v-avatar color="primary" size="88" class="mb-3">
            <v-icon size="44" color="white">mdi-doctor</v-icon>
          </v-avatar>
          <div class="text-h6 font-weight-bold">Dr. {{ doctor.user?.name }}</div>
          <div class="text-body-2 text-medium-emphasis mb-2">{{ doctor.specialization }}</div>
          <v-chip :color="doctor.is_active ? 'success' : 'error'" variant="tonal" size="small">
            {{ doctor.is_active ? 'Aktiv' : 'Inaktiv' }}
          </v-chip>
          <v-list density="compact" class="text-left mt-3">
            <v-list-item prepend-icon="mdi-phone" :subtitle="doctor.user?.phone || '—'" />
            <v-list-item prepend-icon="mdi-email" :subtitle="doctor.user?.email" />
            <v-list-item prepend-icon="mdi-door" :subtitle="'Xona: ' + (doctor.room_number || '—')" />
            <v-list-item prepend-icon="mdi-cash" :subtitle="formatMoney(doctor.consultation_price)" />
          </v-list>
        </v-card>

        <!-- Stats -->
        <v-card rounded="xl" class="pa-4">
          <div class="font-weight-bold mb-3">Bu oy statistikasi</div>
          <v-row dense>
            <v-col cols="6">
              <div class="text-center pa-3 rounded-lg" style="background: #F3F4F6;">
                <div class="text-h5 font-weight-bold text-primary">{{ report.stats?.totalPatients || 0 }}</div>
                <div class="text-caption">Bemorlar</div>
              </div>
            </v-col>
            <v-col cols="6">
              <div class="text-center pa-3 rounded-lg" style="background: #F3F4F6;">
                <div class="text-h5 font-weight-bold text-success">{{ formatMillions(report.stats?.totalRevenue) }}</div>
                <div class="text-caption">Daromad</div>
              </div>
            </v-col>
          </v-row>
        </v-card>
      </v-col>

      <v-col cols="12" md="8">
        <v-card rounded="xl">
          <v-card-title class="pa-4 pb-0">Bugungi qabullar</v-card-title>
          <v-data-table
            :headers="apptHeaders"
            :items="appointments"
            :loading="loading"
            density="compact"
            :items-per-page="10"
          >
            <template #item.scheduled_at="{ item }">{{ formatDateTime(item.scheduled_at) }}</template>
            <template #item.patient="{ item }">{{ item.patient?.full_name }}</template>
            <template #item.status="{ item }">
              <v-chip :color="statusColor(item.status)" size="small" label>{{ statusLabel(item.status) }}</v-chip>
            </template>
          </v-data-table>
        </v-card>
      </v-col>
    </v-row>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { tenantApi } from '@/plugins/axios'
import dayjs from 'dayjs'

const route = useRoute()
const doctor = ref(null)
const report = ref({ stats: {} })
const appointments = ref([])
const loading = ref(false)

const apptHeaders = [
  { title: 'Vaqt', key: 'scheduled_at' },
  { title: 'Bemor', key: 'patient' },
  { title: 'Xizmat', key: 'service.name' },
  { title: 'Holat', key: 'status' },
]

function formatMoney(v) { return v ? Number(v).toLocaleString('uz-UZ') + " so'm" : '0' }
function formatMillions(v) { return v ? (v >= 1000000 ? (v/1000000).toFixed(1)+'M' : (v/1000).toFixed(0)+'K') : '0' }
function formatDateTime(d) { return d ? dayjs(d).format('DD.MM HH:mm') : '' }
function statusColor(s) { return {pending:'warning',confirmed:'info',in_progress:'primary',completed:'success',cancelled:'error'}[s]||'grey' }
function statusLabel(s) { return {pending:'Kutilmoqda',confirmed:'Tasdiqlangan',in_progress:'Qabulda',completed:'Tugadi',cancelled:'Bekor'}[s]||s }

async function load() {
  loading.value = true
  const id = route.params.id
  const [dRes, rRes, aRes] = await Promise.all([
    tenantApi.get(`/doctors/${id}`),
    tenantApi.get(`/doctors/${id}/report`),
    tenantApi.get(`/doctors/${id}/appointments?date=${dayjs().format('YYYY-MM-DD')}`),
  ])
  doctor.value = dRes.data
  report.value = rRes.data
  appointments.value = aRes.data.data || []
  loading.value = false
}

onMounted(load)
</script>
