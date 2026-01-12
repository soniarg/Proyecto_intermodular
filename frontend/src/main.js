import './assets/main.css'
import 'leaflet/dist/leaflet.css'

import { createApp } from 'vue'
import App from './App.vue'
import router from './router' // <--- Importamos el router

const app = createApp(App)

app.use(router) // <--- Le decimos a Vue que lo use
app.mount('#app')