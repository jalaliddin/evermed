<template>
  <div>
    <div class="text-h5 font-weight-bold mb-6">Printer sozlamalari</div>

    <v-row>
      <v-col cols="12" md="7">
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-0">ESC/POS Printer</v-card-title>
          <v-card-text class="pa-4">
            <v-select
              v-model="form.printer_type"
              :items="printerTypes"
              label="Printer turi"
              variant="outlined"
              class="mb-3"
            />

            <template v-if="form.printer_type === 'network'">
              <v-text-field v-model="form.printer_host" label="IP manzil" variant="outlined" placeholder="192.168.1.100" class="mb-3" />
              <v-text-field v-model.number="form.printer_port" label="Port" variant="outlined" type="number" placeholder="9100" class="mb-3" />
            </template>

            <template v-if="form.printer_type === 'usb'">
              <v-text-field v-model="form.printer_path" label="USB yo'l" variant="outlined" placeholder="/dev/usb/lp0" class="mb-3" />
            </template>

            <div class="d-flex gap-2">
              <v-btn variant="outlined" prepend-icon="mdi-printer-check" :loading="testing" @click="testPrint">
                Test chop etish
              </v-btn>
              <v-chip v-if="testResult" :color="testResult === 'ok' ? 'success' : 'error'">
                {{ testResult === 'ok' ? 'Muvaffaqiyatli!' : 'Xatolik' }}
              </v-chip>
            </div>
          </v-card-text>
        </v-card>

        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-0">Chek ko'rinishi</v-card-title>
          <v-card-text class="pa-4">
            <v-switch v-model="form.print_logo" label="Logo chop etish" color="primary" hide-details class="mb-2" />
            <v-switch v-model="form.print_doctor_name" label="Shifokor nomini chop etish" color="primary" hide-details class="mb-2" />
            <v-switch v-model="form.print_diagnosis" label="Diagnozni chop etish" color="primary" hide-details class="mb-2" />
            <v-switch v-model="form.auto_print" label="Avto chop etish (to'lovdan keyin)" color="primary" hide-details />
          </v-card-text>
        </v-card>

        <v-btn color="primary" size="large" :loading="saving" @click="save">Saqlash</v-btn>
      </v-col>

      <v-col cols="12" md="5">
        <v-card rounded="xl">
          <v-card-title class="pa-4 pb-2">Chek ko'rinishi</v-card-title>
          <v-card-text class="pa-4">
            <div class="receipt-preview pa-3 rounded" style="font-family: monospace; background: #f5f5f5; white-space: pre-line; font-size: 12px;">{{ receiptPreview }}</div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { tenantApi } from '@/plugins/axios'

const saving = ref(false)
const testing = ref(false)
const testResult = ref(null)
const snackbar = ref({ show: false, text: '', color: 'success' })

const form = reactive({
  printer_type: 'network',
  printer_host: '192.168.1.100',
  printer_port: 9100,
  printer_path: '/dev/usb/lp0',
  print_logo: true,
  print_doctor_name: true,
  print_diagnosis: false,
  auto_print: false,
})

const printerTypes = [
  { title: 'Tarmoq (Network / Wi-Fi)', value: 'network' },
  { title: 'USB', value: 'usb' },
  { title: "O'chirilgan", value: 'disabled' },
]

const receiptPreview = computed(() => `================================
        EVERMED KLINIKASI
   Tel: +998 90 123 45 67
================================
Bemor: Aliyev Alisher
Shifokor: Dr. Karimov Bobur
Sana: 10.06.2026 14:30
--------------------------------
Konsultatsiya          50,000
UZI tekshiruvi        120,000
--------------------------------
JAMI:                 170,000
To'lov: Naqd
================================
     Tez tuzalasiz!
================================`)

async function testPrint() {
  testing.value = true
  testResult.value = null
  try {
    const res = await tenantApi.post('/settings/test-print', { printer_type: form.printer_type, printer_host: form.printer_host, printer_port: form.printer_port, printer_path: form.printer_path })
    testResult.value = res.data.success ? 'ok' : 'error'
  } catch { testResult.value = 'error' }
  finally { testing.value = false }
}

async function save() {
  saving.value = true
  try {
    await tenantApi.put('/settings/printer', {
      printer_type: form.printer_type,
      printer_ip:   form.printer_host,
      printer_port: form.printer_port,
      printer_path: form.printer_path,
    })
    snackbar.value = { show: true, text: 'Saqlandi', color: 'success' }
  } catch {
    snackbar.value = { show: true, text: 'Xatolik', color: 'error' }
  } finally { saving.value = false }
}

async function load() {
  try {
    const res = await tenantApi.get('/settings/printer')
    const s = res.data
    if (s.printer_type)  form.printer_type = s.printer_type
    if (s.printer_ip)    form.printer_host = s.printer_ip
    if (s.printer_port)  form.printer_port = Number(s.printer_port)
    if (s.printer_path)  form.printer_path = s.printer_path
  } catch { /* not configured yet */ }
}

onMounted(load)
</script>
