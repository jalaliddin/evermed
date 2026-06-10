<template>
  <v-app>
  <div class="landing">
    <!-- Navbar -->
    <header style="position: sticky; top: 0; z-index: 100; background: white; border-bottom: 1px solid rgba(0,0,0,0.08);">
      <v-container>
        <div class="d-flex align-center justify-space-between py-3">
          <div class="d-flex align-center gap-2">
            <v-icon color="primary" size="28">mdi-hospital-box</v-icon>
            <span class="font-weight-bold text-h6" style="color: #0D1B2A;">Ever<span class="text-primary">MED</span></span>
          </div>
          <div class="d-flex align-center gap-1">
            <v-btn variant="text" href="#features" class="d-none d-sm-flex">Imkoniyatlar</v-btn>
            <v-btn variant="text" href="#pricing" class="d-none d-sm-flex">Narxlar</v-btn>
            <v-btn color="primary" variant="flat" rounded to="/login" class="ml-2">Kirish</v-btn>
          </div>
        </div>
      </v-container>
    </header>

    <!-- Hero -->
    <section class="hero py-16" style="background: linear-gradient(135deg, #0D1B2A 0%, #1a3a5c 100%); min-height: 90vh; display: flex; align-items: center;">
      <v-container>
        <v-row align="center" justify="center">
          <v-col cols="12" md="6" class="text-center text-md-left">
            <v-chip color="primary" variant="tonal" class="mb-4">
              <v-icon start>mdi-star</v-icon>
              Zamonaviy tibbiy CRM
            </v-chip>
            <h1 class="text-h3 text-sm-h2 font-weight-black text-white mb-4">
              Klinikangizni <span class="text-primary">raqamlashtiring</span>
            </h1>
            <p class="text-body-1 mb-6" style="color: rgba(255,255,255,0.7); max-width: 480px;">
              Bemorlar, qabullar, tashriflar, to'lovlar, hisobotlar — hamma narsa bitta tizimda. Shifokorlar va registratorlar uchun qulay interfeys.
            </p>
            <div class="d-flex gap-3 justify-center justify-md-start flex-wrap">
              <v-btn color="primary" size="x-large" rounded to="/login">
                Bepul sinab ko'ring
                <v-icon end>mdi-arrow-right</v-icon>
              </v-btn>
              <v-btn variant="outlined" size="x-large" rounded color="white" href="#demo">Demo ko'rish</v-btn>
            </div>
          </v-col>
          <v-col cols="12" md="6" class="d-none d-md-flex justify-center">
            <v-card rounded="2xl" elevation="20" style="overflow: hidden; max-width: 480px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);">
              <div class="pa-4">
                <div class="d-flex gap-2 mb-3">
                  <div v-for="c in ['#ff5f57','#ffbd2e','#28c840']" :key="c" :style="`width:12px; height:12px; border-radius:50%; background:${c}`" />
                </div>
                <div v-for="row in mockRows" :key="row.name" class="d-flex align-center justify-space-between pa-2 mb-1 rounded" style="background: rgba(255,255,255,0.07);">
                  <div class="d-flex align-center gap-2">
                    <v-avatar size="28" :color="row.color" variant="tonal"><span class="text-caption">{{ row.name[0] }}</span></v-avatar>
                    <span class="text-white text-body-2">{{ row.name }}</span>
                  </div>
                  <div class="d-flex gap-2 align-center">
                    <span class="text-caption" style="color: rgba(255,255,255,0.5);">{{ row.time }}</span>
                    <v-chip :color="row.status === 'completed' ? 'success' : row.status === 'pending' ? 'warning' : 'primary'" size="x-small" label>{{ row.label }}</v-chip>
                  </div>
                </div>
              </div>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </section>

    <!-- Stats -->
    <section style="background: #f8f9fa;" class="py-10">
      <v-container>
        <v-row justify="center" class="text-center">
          <v-col v-for="s in statsRow" :key="s.label" cols="6" sm="3">
            <div class="text-h4 font-weight-black text-primary">{{ s.value }}</div>
            <div class="text-body-2 text-medium-emphasis">{{ s.label }}</div>
          </v-col>
        </v-row>
      </v-container>
    </section>

    <!-- Features -->
    <section id="features" class="py-16">
      <v-container>
        <div class="text-center mb-10">
          <div class="text-h4 font-weight-bold mb-2">Barcha zarur imkoniyatlar</div>
          <div class="text-body-1 text-medium-emphasis">Klinikangizni boshqarish uchun kerakli hamma narsa</div>
        </div>
        <v-row>
          <v-col v-for="f in features" :key="f.title" cols="12" sm="6" md="4">
            <v-card rounded="xl" height="100%" variant="tonal" :color="f.color">
              <v-card-text class="pa-5">
                <v-icon :icon="f.icon" size="36" :color="f.color" class="mb-3" />
                <div class="text-h6 font-weight-bold mb-2">{{ f.title }}</div>
                <div class="text-body-2">{{ f.desc }}</div>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </section>

    <!-- Pricing -->
    <section id="pricing" style="background: #f8f9fa;" class="py-16">
      <v-container>
        <div class="text-center mb-10">
          <div class="text-h4 font-weight-bold mb-2">Arzon va shaffof narxlar</div>
          <div class="text-body-1 text-medium-emphasis">Foydalanuvchilar soni cheklanmagan, barcha imkoniyatlar kiritilgan</div>
        </div>
        <v-row justify="center">
          <v-col cols="12" sm="8" md="5">
            <v-card rounded="2xl" elevation="8" class="text-center">
              <v-card-text class="pa-8">
                <div class="text-h6 font-weight-medium mb-1">Oylik tarif</div>
                <div class="text-h3 font-weight-black text-primary mb-1">150 000 <span class="text-h6">so'm</span></div>
                <div class="text-body-2 text-medium-emphasis mb-6">/ oyda</div>
                <v-list density="compact" class="text-left mb-6">
                  <v-list-item v-for="f in pricingFeatures" :key="f" :title="f" prepend-icon="mdi-check-circle" color="success" />
                </v-list>
                <v-btn color="primary" size="large" rounded block to="/login">Hoziroq boshlash</v-btn>
              </v-card-text>
            </v-card>
          </v-col>
        </v-row>
      </v-container>
    </section>

    <!-- CTA -->
    <section class="py-16" style="background: linear-gradient(135deg, #0D1B2A 0%, #1565C0 100%);">
      <v-container class="text-center">
        <div class="text-h4 font-weight-black text-white mb-3">Klinikangizni bugun raqamlashtiring</div>
        <div class="text-body-1 mb-6" style="color: rgba(255,255,255,0.7);">Demo hisobingiz tayyor. Hozir kiring va sinab ko'ring.</div>
        <v-btn color="white" variant="flat" size="x-large" rounded to="/login">
          <span style="color: #0D1B2A; font-weight: 700;">Bepul boshlash</span>
          <v-icon end style="color: #0D1B2A;">mdi-arrow-right</v-icon>
        </v-btn>
      </v-container>
    </section>

    <!-- Footer -->
    <footer style="background: #0D1B2A;">
      <v-container class="py-4">
        <div class="d-flex flex-wrap justify-space-between align-center gap-2">
          <div class="d-flex align-center gap-2">
            <v-icon color="primary">mdi-hospital-box</v-icon>
            <span class="font-weight-bold text-white">EverMED CRM</span>
          </div>
          <div class="text-caption" style="color: rgba(255,255,255,0.4);">
            © {{ new Date().getFullYear() }} Eversoft. Barcha huquqlar himoyalangan.
          </div>
        </div>
      </v-container>
    </footer>
  </div>
  </v-app>
</template>

<script setup>
const mockRows = [
  { name: 'Aliyev Alisher', time: '09:00', status: 'completed', label: 'Tugadi', color: 'success' },
  { name: 'Karimova Malika', time: '10:30', status: 'in_progress', label: 'Qabulda', color: 'primary' },
  { name: 'Rahimov Bobur', time: '11:00', status: 'pending', label: 'Kutmoqda', color: 'warning' },
  { name: 'Sobirov Jasur', time: '11:30', status: 'pending', label: 'Kutmoqda', color: 'warning' },
  { name: "Nazarova Dilnoza", time: '14:00', status: 'pending', label: 'Belgilangan', color: 'secondary' },
]

const statsRow = [
  { value: '500+', label: 'Tibbiy muassasa' },
  { value: '50K+', label: 'Bemor yozuvi' },
  { value: '99.9%', label: 'Ishlash vaqti' },
  { value: '24/7', label: 'Texnik yordam' },
]

const features = [
  { title: 'Qabul rejalashtirish', icon: 'mdi-calendar-check', color: 'primary', desc: "Onlayn qabul belgilash, eslatmalar va shifokor jadvalini boshqarish" },
  { title: 'Bemor kartasi', icon: 'mdi-account-heart', color: 'success', desc: "To'liq tibbiy tarix, tashriflar, retseptlar va hujjatlar" },
  { title: 'Moliyaviy hisobot', icon: 'mdi-chart-bar', color: 'warning', desc: "Daromad, chiqim, to'lov usullari bo'yicha batafsil hisobotlar" },
  { title: 'Inventar boshqaruvi', icon: 'mdi-package-variant', color: 'info', desc: "Dori va materiallar hisobi, kam qolgan mahsulotlar uchun ogohlantirishlar" },
  { title: 'Telegram bildirishnomalar', icon: 'mdi-send', color: 'secondary', desc: "Qabul, to'lov va tizim xabarlari to'g'ridan-to'g'ri Telegramga" },
  { title: "Ko'p klinikali tizim", icon: 'mdi-hospital-building', color: 'error', desc: "Har bir klinikaning alohida ma'lumotlar bazasi va konfiguratsiyasi" },
]

const pricingFeatures = [
  "Cheksiz foydalanuvchilar",
  "Cheksiz bemorlar",
  "Barcha modullar kiradi",
  "Telegram bildirishnomalar",
  "Excel va PDF eksport",
  "ESC/POS printer qo'llab-quvvatlash",
  "Texnik yordam",
]
</script>

<style scoped>
.landing { overflow-x: hidden; }
</style>
