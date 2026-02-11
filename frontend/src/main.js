import './assets/main.css'
import 'leaflet/dist/leaflet.css'
import { createApp } from 'vue'
import { createPinia } from 'pinia'

// import App from './App.vue'
import App from './App.vue' // Borrar esta línea al desplegar del todo la app, este import es para pruebas de Vue
import router from './router' // <--- Importamos el router
import Toast from 'vue-toastification'
import 'vue-toastification/dist/index.css'

const app = createApp(App)
app.use(Toast)
app.use(createPinia())

app.use(router) // <--- Le decimos a Vue que lo use
app.mount('#app')