<template>
  <div>
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" class="mr-2" @click="$router.back()" />
      <div class="text-h5 font-weight-bold">{{ isEdit ? 'Shifokorni tahrirlash' : 'Yangi shifokor' }}</div>
    </div>

    <v-row>
      <v-col cols="12" md="7">
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-0">Shifokor ma'lumotlari</v-card-title>
          <v-card-text class="pa-4">
            <v-row>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.name" label="To'liq ismi *" variant="outlined" :error-messages="errors.name" />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.phone" label="Telefon" variant="outlined" prepend-inner-icon="mdi-phone" />
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <v-card rounded="xl">
          <v-card-title class="pa-4 pb-0">Shifokor ma'lumotlari</v-card-title>
          <v-card-text class="pa-4">
            <v-row>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.specialization" label="Mutaxassislik" variant="outlined" placeholder="Terapevt, Kardiolog..." />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.room_number" label="Xona raqami" variant="outlined" prepend-inner-icon="mdi-door" />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model.number="form.consultation_price" label="Konsultatsiya narxi (so'm)" variant="outlined" type="number" prepend-inner-icon="mdi-cash" />
              </v-col>
              <v-col cols="12" sm="6">
                <v-switch v-model="form.is_active" label="Aktiv" color="success" hide-details />
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="form.bio" label="Bio / Ma'lumot" variant="outlined" rows="3" />
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <!-- Schedule -->
      <v-col cols="12" md="5">
        <v-card rounded="xl">
          <v-card-title class="pa-4 pb-0">Ish jadvali</v-card-title>
          <v-card-text class="pa-4">
            <div v-for="(day, key) in scheduleKeys" :key="key" class="mb-3">
              <div class="d-flex align-center mb-1">
                <v-checkbox v-model="schedule[key].active" :label="day" density="compact" hide-details />
              </div>
              <div v-if="schedule[key].active" class="d-flex gap-2 ml-7">
                <v-text-field v-model="schedule[key].start" type="time" variant="outlined" density="compact" hide-details style="max-width: 120px;" />
                <span class="align-self-center">—</span>
                <v-text-field v-model="schedule[key].end" type="time" variant="outlined" density="compact" hide-details style="max-width: 120px;" />
              </div>
            </div>
          </v-card-text>
        </v-card>
      </v-col>
    </v-row>

    <div class="d-flex justify-end gap-3 mt-4">
      <v-btn variant="outlined" @click="$router.back()">Bekor</v-btn>
      <v-btn color="primary" :loading="saving" @click="save">
        {{ isEdit ? 'Saqlash' : 'Qo\'shish' }}
      </v-btn>
    </div>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { tenantApi } from '@/plugins/axios'

const route = useRoute()
const router = useRouter()
const isEdit = computed(() => !!route.params.id)
const saving = ref(false)
const snackbar = ref({ show: false, text: '', color: 'success' })

const form = reactive({
  name: '', phone: '',
  specialization: '', room_number: '', consultation_price: 0,
  bio: '', is_active: true,
})
const errors = reactive({ name: '' })

const scheduleKeys = {
  monday: 'Dushanba', tuesday: 'Seshanba', wednesday: 'Chorshanba',
  thursday: 'Payshanba', friday: 'Juma', saturday: 'Shanba',
}
const schedule = reactive({
  monday: { active: true, start: '09:00', end: '17:00' },
  tuesday: { active: true, start: '09:00', end: '17:00' },
  wednesday: { active: true, start: '09:00', end: '17:00' },
  thursday: { active: true, start: '09:00', end: '17:00' },
  friday: { active: true, start: '09:00', end: '16:00' },
  saturday: { active: false, start: '09:00', end: '13:00' },
})

function buildSchedule() {
  const result = {}
  Object.entries(schedule).forEach(([k, v]) => {
    if (v.active) result[k] = { start: v.start, end: v.end }
  })
  return result
}

async function save() {
  errors.name = ''
  if (!form.name) { errors.name = 'Ism majburiy'; return }

  saving.value = true
  try {
    const payload = { ...form, schedule: buildSchedule() }
    if (isEdit.value) {
      await tenantApi.put(`/doctors/${route.params.id}`, payload)
      snackbar.value = { show: true, text: 'Yangilandi', color: 'success' }
    } else {
      const res = await tenantApi.post('/doctors', payload)
      snackbar.value = { show: true, text: 'Shifokor qo\'shildi', color: 'success' }
      setTimeout(() => router.push(`/doctors/${res.data.id}`), 500)
    }
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) Object.entries(errs).forEach(([k, v]) => errors[k] = v[0])
    snackbar.value = { show: true, text: 'Xatolik', color: 'error' }
  } finally {
    saving.value = false
  }
}

async function loadDoctor() {
  if (!isEdit.value) return
  const res = await tenantApi.get(`/doctors/${route.params.id}`)
  const d = res.data
  Object.assign(form, {
    name: d.user?.name, phone: d.user?.phone,
    specialization: d.specialization, room_number: d.room_number,
    consultation_price: d.consultation_price, bio: d.bio, is_active: d.is_active,
  })
  if (d.schedule) {
    Object.entries(d.schedule).forEach(([k, v]) => {
      if (schedule[k]) schedule[k] = { active: true, ...v }
    })
  }
}

onMounted(loadDoctor)
</script>
