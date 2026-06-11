<template>
  <div>
    <div class="text-h5 font-weight-bold mb-6">Klinika sozlamalari</div>

    <v-form @submit.prevent="save">
      <v-row>
        <v-col cols="12" md="8">
          <v-card rounded="xl" class="mb-4">
            <v-card-title class="pa-4 pb-0">Asosiy ma'lumotlar</v-card-title>
            <v-card-text class="pa-4">
              <v-row>
                <v-col cols="12">
                  <v-text-field v-model="form.name" label="Klinika nomi *" variant="outlined" />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field v-model="form.phone" label="Telefon" variant="outlined" />
                </v-col>
                <v-col cols="12">
                  <v-textarea v-model="form.address" label="Manzil" variant="outlined" rows="2" />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field v-model="form.work_start" label="Ish boshlash vaqti" variant="outlined" type="time" />
                </v-col>
                <v-col cols="12" sm="6">
                  <v-text-field v-model="form.work_end" label="Ish tugash vaqti" variant="outlined" type="time" />
                </v-col>
              </v-row>
            </v-card-text>
          </v-card>

          <v-card rounded="xl">
            <v-card-title class="pa-4 pb-0">Chek sozlamalari</v-card-title>
            <v-card-text class="pa-4">
              <v-textarea v-model="form.receipt_tagline" label="Chek pastki yozuv" variant="outlined" rows="2" placeholder="Tez tuzalasiz!" class="mb-3" />
              <v-switch v-model="form.show_logo_on_receipt" label="Chekda logo ko'rsatish" color="primary" hide-details />
            </v-card-text>
          </v-card>
        </v-col>

        <v-col cols="12" md="4">
          <v-card rounded="xl" class="mb-4">
            <v-card-title class="pa-4 pb-0">Logo</v-card-title>
            <v-card-text class="pa-4 text-center">
              <v-img v-if="logoPreview" :src="logoPreview" height="120" contain class="mb-3" />
              <v-icon v-else size="80" color="grey-lighten-2">mdi-image</v-icon>
              <v-file-input
                v-model="logoFile"
                label="Logo yuklash"
                accept="image/*"
                variant="outlined"
                prepend-icon="mdi-camera"
                class="mt-3"
                @update:model-value="previewLogo"
              />
            </v-card-text>
          </v-card>

          <v-btn color="primary" type="submit" block size="large" :loading="saving">
            Saqlash
          </v-btn>
        </v-col>
      </v-row>
    </v-form>

    <v-snackbar v-model="snackbar.show" :color="snackbar.color">{{ snackbar.text }}</v-snackbar>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { tenantApi } from '@/plugins/axios'

const saving = ref(false)
const logoFile = ref(null)
const logoPreview = ref(null)
const snackbar = ref({ show: false, text: '', color: 'success' })

const form = reactive({
  name: '',
  phone: '',
  address: '',
  work_start: '08:00',
  work_end: '18:00',
  receipt_tagline: '',
  show_logo_on_receipt: true,
})

function previewLogo(files) {
  if (files && files[0]) {
    const reader = new FileReader()
    reader.onload = e => { logoPreview.value = e.target.result }
    reader.readAsDataURL(files[0])
  }
}

async function load() {
  const res = await tenantApi.get('/settings/clinic')
  const s = res.data
  Object.keys(form).forEach(k => { if (s[k] !== undefined) form[k] = s[k] })
  if (s.logo) logoPreview.value = s.logo
}

async function save() {
  saving.value = true
  try {
    // Upload logo first if a new file is selected
    const file = Array.isArray(logoFile.value) ? logoFile.value[0] : logoFile.value
    if (file) {
      const fd = new FormData()
      fd.append('logo', file)
      const logoRes = await tenantApi.post('/settings/clinic/logo', fd, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
      logoPreview.value = logoRes.data.logo
      logoFile.value = null
    }

    await tenantApi.put('/settings/clinic', form)
    snackbar.value = { show: true, text: 'Saqlandi', color: 'success' }
  } catch {
    snackbar.value = { show: true, text: 'Xatolik', color: 'error' }
  } finally { saving.value = false }
}

onMounted(load)
</script>
