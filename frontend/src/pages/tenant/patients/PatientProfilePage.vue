<template>
  <div v-if="patient">
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" class="mr-2" to="/patients" />
      <div class="text-h5 font-weight-bold">Bemor profili</div>
      <v-spacer />
      <v-btn variant="outlined" prepend-icon="mdi-pencil" :to="`/patients/${patient.id}/edit`" class="mr-2">Tahrirlash</v-btn>
      <v-btn color="primary" prepend-icon="mdi-calendar-plus" @click="showAppointmentDialog = true">Qabul belgilash</v-btn>
    </div>

    <v-row>
      <!-- Left Panel -->
      <v-col cols="12" md="4" lg="3">
        <v-card rounded="xl" class="mb-4 text-center pa-6">
          <v-avatar size="96" color="primary" class="mb-3">
            <v-img v-if="patient.photo" :src="'/storage/' + patient.photo" cover />
            <span v-else class="text-h5 text-white font-weight-bold">{{ patient.full_name?.[0] }}</span>
          </v-avatar>
          <div class="text-h6 font-weight-bold">{{ patient.full_name }}</div>
          <div class="text-caption text-medium-emphasis mb-3">Bemor #{{ patient.id }}</div>

          <div class="d-flex justify-center gap-2 mb-4">
            <v-chip v-if="patient.gender" :color="patient.gender === 'male' ? 'blue' : 'pink'" size="small" variant="tonal">
              {{ patient.gender === 'male' ? 'Erkak' : 'Ayol' }}
            </v-chip>
            <v-chip v-if="patient.blood_type" color="error" size="small" variant="tonal">{{ patient.blood_type }}</v-chip>
          </div>

          <v-list lines="one" density="compact" class="text-left">
            <v-list-item v-if="patient.phone" prepend-icon="mdi-phone" :subtitle="patient.phone" />
            <v-list-item v-if="patient.birth_date" prepend-icon="mdi-cake" :subtitle="formatDate(patient.birth_date) + ' (' + age(patient.birth_date) + ' yosh)'" />
            <v-list-item v-if="patient.address" prepend-icon="mdi-map-marker" :subtitle="patient.address" />
          </v-list>
        </v-card>

        <!-- Stats -->
        <v-card rounded="xl" class="pa-4">
          <div class="text-body-2 font-weight-bold mb-3">Statistika</div>
          <div class="d-flex justify-space-between py-2 text-body-2">
            <span class="text-medium-emphasis">Jami tashriflar</span>
            <span class="font-weight-bold">{{ patientStats.total_visits || 0 }}</span>
          </div>
          <v-divider />
          <div class="d-flex justify-space-between py-2 text-body-2">
            <span class="text-medium-emphasis">Jami to'lov</span>
            <span class="font-weight-bold">{{ formatMoney(patientStats.total_spent) }}</span>
          </div>
          <v-divider />
          <div class="d-flex justify-space-between py-2 text-body-2">
            <span class="text-medium-emphasis">Oxirgi tashrif</span>
            <span class="font-weight-bold">{{ patientStats.last_visit ? formatDate(patientStats.last_visit) : '—' }}</span>
          </div>
        </v-card>
      </v-col>

      <!-- Right Panel — Tabs -->
      <v-col cols="12" md="8" lg="9">
        <v-card rounded="xl">
          <v-tabs v-model="tab" color="primary">
            <v-tab value="visits">📋 Tashrif tarixi</v-tab>
            <v-tab value="notes">📝 Eslatmalar</v-tab>
          </v-tabs>
          <v-divider />
          <v-window v-model="tab" class="pa-4">
            <!-- Visits Tab -->
            <v-window-item value="visits">
              <div v-if="visitsLoading" class="text-center pa-8">
                <v-progress-circular indeterminate color="primary" />
              </div>
              <div v-else-if="visits.length === 0" class="text-center pa-8 text-medium-emphasis">
                <v-icon size="48" class="mb-2">mdi-calendar-blank</v-icon>
                <div>Tashriflar yo'q</div>
              </div>
              <v-expansion-panels v-else variant="accordion" class="mt-1">
                <v-expansion-panel v-for="visit in visits" :key="visit.id" rounded="lg">
                  <v-expansion-panel-title>
                    <div class="d-flex align-center gap-3 w-100">
                      <div class="text-body-2 font-weight-bold" style="min-width:90px">
                        {{ formatDate(visit.visited_at) }}
                      </div>
                      <div class="text-body-2 text-medium-emphasis flex-grow-1">
                        Dr. {{ visit.doctor?.user?.name }}
                      </div>
                      <div class="text-body-2 font-weight-bold mr-2">
                        {{ formatMoney(visit.paid_amount) }}
                      </div>
                      <v-chip :color="visit.is_paid ? 'success' : 'warning'" size="x-small" label class="mr-2">
                        {{ visit.is_paid ? "To'langan" : 'Qarz' }}
                      </v-chip>
                    </div>
                  </v-expansion-panel-title>
                  <v-expansion-panel-text>
                    <div class="py-1">
                      <!-- Services -->
                      <div class="text-caption font-weight-bold text-medium-emphasis mb-1">XIZMATLAR</div>
                      <div v-for="svc in visit.services" :key="svc.id" class="d-flex justify-space-between text-body-2 py-1">
                        <span>{{ svc.service?.name }} <span v-if="svc.quantity > 1" class="text-medium-emphasis">×{{ svc.quantity }}</span></span>
                        <span class="font-weight-medium">{{ formatMoney(svc.total) }}</span>
                      </div>

                      <!-- Inventory -->
                      <template v-if="visit.inventory?.length">
                        <v-divider class="my-2" />
                        <div class="text-caption font-weight-bold text-medium-emphasis mb-1">SARFLANGAN MATERIAL</div>
                        <div v-for="inv in visit.inventory" :key="inv.id" class="d-flex justify-space-between text-body-2 py-1">
                          <span>{{ inv.item?.name }}</span>
                          <span class="text-medium-emphasis">{{ inv.quantity_used }} {{ inv.item?.unit }}</span>
                        </div>
                      </template>

                      <!-- Diagnosis -->
                      <template v-if="visit.diagnosis">
                        <v-divider class="my-2" />
                        <div class="text-caption font-weight-bold text-medium-emphasis mb-1">DIAGNOZ</div>
                        <div class="text-body-2" style="white-space:pre-wrap">{{ visit.diagnosis }}</div>
                      </template>

                      <div class="d-flex justify-end mt-3">
                        <v-btn size="small" variant="tonal" :to="`/visits/${visit.id}`" prepend-icon="mdi-eye-outline">
                          Batafsil
                        </v-btn>
                      </div>
                    </div>
                  </v-expansion-panel-text>
                </v-expansion-panel>
              </v-expansion-panels>
            </v-window-item>

            <!-- Notes Tab -->
            <v-window-item value="notes">
              <div v-if="patient.notes" class="pa-2">
                <p class="text-body-2" style="white-space: pre-wrap;">{{ patient.notes }}</p>
              </div>
              <div v-else class="text-center pa-8 text-medium-emphasis">
                <v-icon size="48" class="mb-2">mdi-note-off</v-icon>
                <div>Eslatmalar yo'q</div>
              </div>
              <div v-if="patient.allergies" class="mt-4">
                <div class="text-body-2 font-weight-bold mb-2">Allergiyalar:</div>
                <v-chip v-for="al in patient.allergies.split(',')" :key="al" color="error" variant="tonal" size="small" class="mr-1 mb-1">
                  {{ al.trim() }}
                </v-chip>
              </div>
            </v-window-item>
          </v-window>
        </v-card>
      </v-col>
    </v-row>
  </div>
  <v-skeleton-loader v-else type="article" />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { tenantApi } from '@/plugins/axios'
import dayjs from 'dayjs'

const route = useRoute()
const patient = ref(null)
const patientStats = ref({})
const visits = ref([])
const tab = ref('visits')
const visitsLoading = ref(false)
const showAppointmentDialog = ref(false)

function formatDate(d) { return d ? dayjs(d).format('DD.MM.YYYY') : '—' }
function age(d) { return d ? dayjs().diff(dayjs(d), 'year') : '' }
function formatMoney(v) {
  if (!v) return '0'
  return Number(v).toLocaleString('uz-UZ') + " so'm"
}

async function load() {
  const [pRes, sRes, vRes] = await Promise.all([
    tenantApi.get(`/patients/${route.params.id}`),
    tenantApi.get(`/patients/${route.params.id}/stats`),
    tenantApi.get(`/patients/${route.params.id}/visits`),
  ])
  patient.value = pRes.data
  patientStats.value = sRes.data
  visits.value = vRes.data.data || []
}

onMounted(load)
</script>
