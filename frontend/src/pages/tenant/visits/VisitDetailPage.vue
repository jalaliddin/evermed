<template>
  <div v-if="visit">
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" class="mr-2" @click="$router.back()" />
      <div class="text-h5 font-weight-bold">Tashrif #{{ visit.id }}</div>
      <v-spacer />
      <v-btn prepend-icon="mdi-printer" variant="outlined" class="mr-2" @click="printReceipt">Chek chiqarish</v-btn>
      <v-chip :color="visit.is_paid ? 'success' : 'warning'" size="large">
        {{ visit.is_paid ? "To'langan" : "Qarzdor" }}
      </v-chip>
    </div>

    <v-row>
      <v-col cols="12" md="4">
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-0">Ma'lumotlar</v-card-title>
          <v-list density="compact" class="pa-2">
            <v-list-item prepend-icon="mdi-account" :title="visit.patient?.full_name" subtitle="Bemor" />
            <v-list-item prepend-icon="mdi-doctor" :title="'Dr. ' + visit.doctor?.user?.name" subtitle="Shifokor" />
            <v-list-item prepend-icon="mdi-calendar" :title="formatDate(visit.visited_at)" subtitle="Tashrif sanasi" />
            <v-list-item prepend-icon="mdi-cash" :title="visit.payment_method" subtitle="To'lov usuli" />
          </v-list>
        </v-card>

        <!-- Payment summary -->
        <v-card rounded="xl">
          <v-card-title class="pa-4 pb-0">To'lov</v-card-title>
          <v-list density="compact" class="pa-2">
            <v-list-item title="Jami" :subtitle="formatMoney(visit.total_amount)" />
            <v-list-item v-if="visit.discount > 0" title="Chegirma" :subtitle="'-' + formatMoney(visit.discount)" />
            <v-divider />
            <v-list-item title="To'langan" :subtitle="formatMoney(visit.paid_amount)" class="text-success font-weight-bold" />
          </v-list>
          <v-card-actions v-if="!visit.is_paid" class="pa-4">
            <v-btn color="primary" block @click="payDialog = true">To'lov qabul qilish</v-btn>
          </v-card-actions>
        </v-card>
      </v-col>

      <v-col cols="12" md="8">
        <!-- Services -->
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-0">Xizmatlar</v-card-title>
          <v-data-table
            :headers="svcHeaders"
            :items="visit.services || []"
            density="compact"
            hide-default-footer
          >
            <template #item.service="{ item }">{{ item.service?.name }}</template>
            <template #item.price="{ item }">{{ formatMoney(item.price) }}</template>
            <template #item.total="{ item }">{{ formatMoney(item.total) }}</template>
          </v-data-table>
        </v-card>

        <!-- Inventory -->
        <v-card rounded="xl" class="mb-4" v-if="visit.inventory?.length">
          <v-card-title class="pa-4 pb-0">
            <v-icon class="mr-2" size="20">mdi-package-variant</v-icon>Sarflangan materiallar
          </v-card-title>
          <v-data-table
            :headers="invHeaders"
            :items="visit.inventory || []"
            density="compact"
            hide-default-footer
          >
            <template #item.item="{ item }">{{ item.item?.name }}</template>
            <template #item.quantity_used="{ item }">{{ item.quantity_used }} {{ item.item?.unit }}</template>
            <template #item.unit_price="{ item }">{{ item.unit_price > 0 ? formatMoney(item.unit_price) : '—' }}</template>
            <template #item.total_price="{ item }">{{ item.total_price > 0 ? formatMoney(item.total_price) : '—' }}</template>
          </v-data-table>
        </v-card>

        <!-- Diagnosis -->
        <v-card rounded="xl" v-if="visit.diagnosis || visit.prescription">
          <v-card-title class="pa-4 pb-0">Diagnoz va retsept</v-card-title>
          <v-card-text class="pa-4">
            <div v-if="visit.diagnosis" class="mb-3">
              <div class="text-caption font-weight-bold text-medium-emphasis mb-1">DIAGNOZ</div>
              <p style="white-space: pre-wrap;">{{ visit.diagnosis }}</p>
            </div>
            <div v-if="visit.prescription">
              <div class="text-caption font-weight-bold text-medium-emphasis mb-1">RETSEPT</div>
              <p style="white-space: pre-wrap;">{{ visit.prescription }}</p>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <!-- Pay Dialog -->
    <v-dialog v-model="payDialog" max-width="400">
      <v-card rounded="xl">
        <v-card-title class="pa-4">To'lov qabul qilish</v-card-title>
        <v-card-text class="pa-4">
          <div class="text-h6 text-center mb-4">{{ formatMoney(visit.total_amount - visit.discount) }}</div>
          <v-text-field v-model.number="payAmount" label="To'langan summa" type="number" variant="outlined" class="mb-3" />
          <v-select v-model="payMethod" :items="paymentMethods" label="To'lov usuli" variant="outlined" />
        </v-card-text>
        <v-card-actions class="pa-4">
          <v-spacer />
          <v-btn @click="payDialog = false">Bekor</v-btn>
          <v-btn color="primary" :loading="paying" @click="doPay">To'lov qilish</v-btn>
        </v-card-actions>
      </v-card>
    </v-dialog>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.text }}</v-snackbar>
  </div>
  <v-skeleton-loader v-else type="article" />
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { tenantApi } from '@/plugins/axios'
import dayjs from 'dayjs'

const route = useRoute()
const visit = ref(null)
const payDialog = ref(false)
const payAmount = ref(0)
const payMethod = ref('cash')
const paying = ref(false)
const snackbar = ref({ show: false, text: '', color: 'success' })

const svcHeaders = [
  { title: 'Xizmat', key: 'service' },
  { title: 'Soni', key: 'quantity', width: 80 },
  { title: 'Narx', key: 'price', width: 120 },
  { title: 'Jami', key: 'total', width: 120 },
]

const invHeaders = [
  { title: 'Material', key: 'item' },
  { title: 'Miqdor', key: 'quantity_used', width: 120 },
  { title: 'Narx', key: 'unit_price', width: 110 },
  { title: 'Jami', key: 'total_price', width: 110 },
]

const paymentMethods = [
  { title: 'Naqd', value: 'cash' },
  { title: 'Karta', value: 'card' },
  { title: "Sug'urta", value: 'insurance' },
]

function formatDate(d) { return d ? dayjs(d).format('DD.MM.YYYY HH:mm') : '' }
function formatMoney(v) { return v ? Number(v).toLocaleString('uz-UZ') + " so'm" : '0' }

async function load() {
  const res = await tenantApi.get(`/visits/${route.params.id}`)
  visit.value = res.data
  payAmount.value = visit.value.total_amount - visit.value.discount
}

async function doPay() {
  paying.value = true
  try {
    await tenantApi.post(`/visits/${visit.value.id}/pay`, { paid_amount: payAmount.value, payment_method: payMethod.value })
    snackbar.value = { show: true, text: "To'lov qabul qilindi", color: 'success' }
    payDialog.value = false
    load()
  } catch {
    snackbar.value = { show: true, text: 'Xatolik', color: 'error' }
  } finally { paying.value = false }
}

function printReceipt() {
  const tenantId = localStorage.getItem('tenant_id')
  window.open(`/api/tenant/visits/${visit.value.id}/receipt-preview?tenant=${tenantId}`, '_blank')
}

onMounted(load)
</script>
