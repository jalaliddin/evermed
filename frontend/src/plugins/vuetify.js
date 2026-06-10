import { createVuetify } from 'vuetify'
import { aliases, mdi } from 'vuetify/iconsets/mdi'
import '@mdi/font/css/materialdesignicons.css'
import 'vuetify/styles'

export default createVuetify({
  icons: {
    defaultSet: 'mdi',
    aliases,
    sets: { mdi },
  },
  theme: {
    defaultTheme: 'light',
    themes: {
      light: {
        dark: false,
        colors: {
          primary: '#1565C0',
          secondary: '#00BFA5',
          accent: '#FF6F00',
          error: '#D32F2F',
          warning: '#F57C00',
          info: '#0288D1',
          success: '#388E3C',
          sidebar: '#0D1B2A',
          'on-primary': '#FFFFFF',
        },
      },
    },
  },
  defaults: {
    VBtn: { style: 'text-transform: none; font-weight: 600;' },
    VCard: { rounded: 'lg' },
  },
})
