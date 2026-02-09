<script setup>
import { ref, onMounted } from 'vue';
import MapView from './MapView.vue'; // Asegúrate de que la ruta sea correcta
import api from '@/api/axios'; 

// Estado de autenticación
const isLoggedIn = ref(!!localStorage.getItem('auth_token'));
const userData = ref(null); 
const BASE_URL = 'http://localhost:8000/storage/'; 

// NUEVO: Variable reactiva para guardar la ubicación (lat, lng)
const userCoords = ref(null); 

// Datos Mock de productos (se mantienen igual para el diseño)
const nearbyAds = ref([
  { 
    id: 1, title: 'Tomates Ensalada', price: '2,50', unit: 'kg',
    image: 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?auto=format&fit=crop&w=500&q=80' 
  },
  { 
    id: 2, title: 'Zanahorias de Valencia', price: '3,10', unit: 'kg',
    image: 'https://images.unsplash.com/photo-1598170845058-32b9d6a5da37?auto=format&fit=crop&w=500&q=80' 
  },
  { 
    id: 3, title: 'Fruta Fresca', price: '15,00', unit: 'pack',
    image: 'https://images.unsplash.com/photo-1610832958506-aa56368176cf?auto=format&fit=crop&w=500&q=80' 
  }
]);

onMounted(async () => {
  // 1. Cargar datos del usuario si está logueado
  if (isLoggedIn.value) {
    try {
      const response = await api.get('/user');
      userData.value = response.data;
    } catch (error) {
      console.error("Error al cargar usuario en header:", error);
      // Si el token es inválido, limpiamos
      if (error.response && error.response.status === 401) {
        localStorage.removeItem('auth_token');
        isLoggedIn.value = false;
      }
    }
  }

  // 2. NUEVO: Pedir ubicación al navegador al cargar la página
  getUserLocation();
});

// Función para obtener geolocalización del navegador
const getUserLocation = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => {
        userCoords.value = [position.coords.latitude, position.coords.longitude];
        console.log("📍 Ubicación obtenida");
      },
      (error) => {
        // CORRECCIÓN: Comentamos el console.warn para que no llene la consola de errores rojos
        // console.warn("⚠️ No se pudo obtener ubicación:", error.message);
        
        // Simplemente dejamos userCoords en null y la app usará coordenadas por defecto
        userCoords.value = null; 
      }
    );
  }
};
</script>

<template>
  <div class="home-view">
    <main class="main-container">
      <section class="section-ads">
        <div class="section-header">
            <h2 class="section-title">Productos Frescos Cerca</h2>
            <router-link to="/marketplace" class="see-more">Mostrar todo</router-link>
        </div>
        <div class="ads-grid">
          <div v-for="ad in nearbyAds" :key="ad.id" class="ad-card">
            <div class="card-image-container">
              <img :src="ad.image" :alt="ad.title" class="ad-image">
              <button class="favorite-btn">♡</button>
            </div>
            <div class="card-details">
              <h3 class="ad-title">{{ ad.title }}</h3>
              <div class="price-row">
                <p class="ad-price">{{ ad.price }} € <span class="unit">/ {{ ad.unit }}</span></p>
                <button class="btn-add">+</button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section class="section-map">
        <h2 class="section-title">Puntos de Recogida Cercanos</h2>
        <div class="map-wrapper">
          <MapView :userLocation="userCoords" />
        </div>
      </section>

      <section class="section-empty">
        <div class="placeholder-content">
          <h3>Espacio para Novedades</h3>
          <p>Esta sección está reservada para futuras promociones.</p>
        </div>
      </section>
    </main>
  </div>
</template>

<style scoped>
/* AÑADE AQUÍ TUS ESTILOS CSS DE LA PÁGINA DE INICIO */
/* He mantenido las clases que usabas en tu ejemplo anterior */

.main-container { max-width: 1200px; margin: 30px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 40px; }

.section-title { font-size: 1.5rem; color: #1e293b; margin-bottom: 20px; }
.section-header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 20px; }
.see-more { color: #3b82f6; text-decoration: none; font-weight: 600; }

.ads-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 25px; }
.ad-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s; }
.ad-card:hover { transform: translateY(-5px); }
.card-image-container { height: 180px; position: relative; }
.ad-image { width: 100%; height: 100%; object-fit: cover; }
.favorite-btn { position: absolute; top: 10px; right: 10px; background: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.card-details { padding: 15px; }
.ad-title { margin: 0 0 10px 0; font-size: 1.1rem; color: #1e293b; }
.price-row { display: flex; justify-content: space-between; align-items: center; }
.ad-price { font-size: 1.2rem; font-weight: bold; color: #10b981; margin: 0; }
.unit { font-size: 0.8rem; color: #64748b; font-weight: normal; }
.btn-add { background: #eff6ff; color: #3b82f6; border: none; width: 30px; height: 30px; border-radius: 8px; font-weight: bold; cursor: pointer; }
.btn-add:hover { background: #3b82f6; color: white; }

.map-wrapper { height: 500px; width: 100%; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }

.section-empty { background: #e2e8f0; border-radius: 16px; padding: 40px; text-align: center; color: #64748b; }
</style>