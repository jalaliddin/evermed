<template>
  <div>
    <div class="d-flex align-center mb-6">
      <v-btn icon="mdi-arrow-left" variant="text" class="mr-2" @click="$router.back()" />
      <div class="text-h5 font-weight-bold">{{ isEdit ? 'Bemorni tahrirlash' : 'Yangi bemor' }}</div>
    </div>

    <v-row>
      <v-col cols="12" md="8">
        <v-card rounded="xl" class="mb-4">
          <v-card-title class="pa-4 pb-0">
            <v-icon class="mr-2" color="primary">mdi-account-details</v-icon>
            Shaxsiy ma'lumotlar
          </v-card-title>
          <v-card-text class="pa-4">
            <v-row>
              <v-col cols="12">
                <v-text-field v-model="form.full_name" label="To'liq ismi *" variant="outlined" :error-messages="errors.full_name" />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.birth_date" label="Tug'ilgan sanasi" type="date" variant="outlined" />
              </v-col>
              <v-col cols="12" sm="6">
                <v-select
                  v-model="form.gender"
                  label="Jinsi"
                  :items="[{title:'Erkak', value:'male'},{title:'Ayol', value:'female'}]"
                  variant="outlined"
                />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.phone" label="Telefon raqami" variant="outlined" prepend-inner-icon="mdi-phone" placeholder="+998901234567" />
              </v-col>
              <v-col cols="12" sm="6">
                <v-text-field v-model="form.address" label="Manzil" variant="outlined" prepend-inner-icon="mdi-map-marker" />
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>

        <v-card rounded="xl">
          <v-card-title class="pa-4 pb-0">
            <v-icon class="mr-2" color="error">mdi-heart-pulse</v-icon>
            Tibbiy ma'lumotlar
          </v-card-title>
          <v-card-text class="pa-4">
            <v-row>
              <v-col cols="12" sm="6">
                <v-select
                  v-model="form.blood_type"
                  label="Qon guruhi"
                  :items="['A+','A-','B+','B-','O+','O-','AB+','AB-']"
                  variant="outlined"
                  clearable
                />
              </v-col>
              <v-col cols="12">
                <v-combobox
                  v-model="allergyChips"
                  label="Allergiyalar"
                  variant="outlined"
                  multiple
                  chips
                  closable-chips
                  hint="Enter bosib qo'shing"
                  persistent-hint
                />
              </v-col>
              <v-col cols="12">
                <v-textarea v-model="form.notes" label="Qo'shimcha eslatmalar" variant="outlined" rows="3" />
              </v-col>
            </v-row>
          </v-card-text>
        </v-card>
      </v-col>

      <v-col cols="12" md="4">
        <v-card rounded="xl" class="text-center pa-6">
          <v-avatar size="120" color="grey-lighten-3" class="mb-4">
            <v-img v-if="photoPreview" :src="photoPreview" cover />
            <v-icon v-else size="56" color="grey">mdi-account</v-icon>
          </v-avatar>
          <div class="mb-4">
            <v-btn variant="outlined" prepend-icon="mdi-camera" @click="$refs.photoInput.click()">
              Foto yuklash
            </v-btn>
            <input ref="photoInput" type="file" accept="image/*" style="display:none" @change="onPhotoChange" />
          </div>
          <div class="text-caption text-medium-emphasis">JPG, PNG, max 2MB</div>
        </v-card>
      </v-col>
    </v-row>

    <div class="d-flex justify-end gap-3 mt-4">
      <v-btn variant="outlined" @click="$router.back()">Bekor qilish</v-btn>
      <v-btn color="primary" :loading="saving" @click="save">
        {{ isEdit ? 'Saqlash' : 'Bemor qo\'shish' }}
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

const isEdit = computed(() => !!route.params.id && route.path.includes('/edit'))
const saving = ref(false)
const photoPreview = ref(null)
const allergyChips = ref([])
const snackbar = ref({ show: false, text: '', color: 'success' })

const form = reactive({
  full_name: '', birth_date: '', gender: null, phone: '',
  address: '', blood_type: null, notes: '',
})
const errors = reactive({ full_name: '' })

function onPhotoChange(e) {
  const file = e.target.files[0]
  if (file) {
    form.photo = file
    photoPreview.value = URL.createObjectURL(file)
  }
}

async function save() {
  errors.full_name = ''
  if (!form.full_name) { errors.full_name = 'Ism majburiy'; return }

  saving.value = true
  try {
    const fd = new FormData()
    Object.entries(form).forEach(([k, v]) => { if (v != null && v !== '') fd.append(k, v) })
    if (allergyChips.value.length) fd.set('allergies', allergyChips.value.join(', '))

    if (isEdit.value) {
      await tenantApi.put(`/patients/${route.params.id}`, fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      snackbar.value = { show: true, text: 'Bemor yangilandi', color: 'success' }
    } else {
      const res = await tenantApi.post('/patients', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
      snackbar.value = { show: true, text: 'Bemor qo\'shildi', color: 'success' }
      setTimeout(() => router.push(`/patients/${res.data.id}`), 500)
    }
  } catch (e) {
    const errs = e.response?.data?.errors
    if (errs) Object.assign(errors, errs)
    snackbar.value = { show: true, text: 'Xatolik yuz berdi', color: 'error' }
  } finally {
    saving.value = false
  }
}

async function loadPatient() {
  if (!isEdit.value) return
  const res = await tenantApi.get(`/patients/${route.params.id}`)
  const p = res.data
  Object.assign(form, {
    full_name: p.full_name, birth_date: p.birth_date, gender: p.gender,
    phone: p.phone, address: p.address, blood_type: p.blood_type, notes: p.notes,
  })
  if (p.allergies) allergyChips.value = p.allergies.split(',').map(s => s.trim()).filter(Boolean)
}

onMounted(loadPatient)
</script>
