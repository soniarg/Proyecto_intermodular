<script setup>
import { ref, onMounted } from 'vue';
import MapView from './MapView.vue'; 
import api from '@/api/axios'; 

const isLoggedIn = ref(!!localStorage.getItem('auth_token'));
const userData = ref(null); 
const BASE_URL = 'http://localhost:8000/storage/'; 
const userCoords = ref(null); 
const navContainer = ref(null);

const nearbyAds = ref([]); 
// NUEVO: Variable para saber si estamos cargando
const loading = ref(true); 

onMounted(async () => {
  if (isLoggedIn.value) {
    try {
      const response = await api.get('/user');
      userData.value = response.data;
    } catch (error) {
       // ... manejo de error token ...
    }
  }
  getUserLocation();
  
  // Cargamos productos
  await fetchProducts(); 
});

const fetchProducts = async () => {
  loading.value = true; // Empezamos a cargar
  try {
    const response = await api.get('/products'); 
    const allProducts = response.data.data || response.data;

    // Filtramos los productos (ocultar los míos)
    if (userData.value && userData.value.id) {
        nearbyAds.value = allProducts.filter(product => product.seller_id !== userData.value.id);
    } else {
        nearbyAds.value = allProducts;
    }

  } catch (error) {
    console.error("Error cargando productos:", error);
    nearbyAds.value = [];
  } finally {
    loading.value = false; // ¡TERMINAMOS! Ya podemos mostrar el resultado
  }
};

// ... resto de funciones (getUserLocation, scrollMenu) igual ...
const getUserLocation = () => { /* ... */ };
const scrollMenu = () => { /* ... */ };
</script>

<template>
  <div class="home-view">
    <main class="main-container">
      <section class="section-ads">
        <div class="section-header">
            <h2 class="section-title">Productos Frescos Cerca</h2>
            <router-link to="/marketplace" class="see-more">Mostrar todo</router-link>
        </div>

        <div v-else-if="nearbyAds.length === 0" class="state-message empty">
            😞 No hay productos disponibles cerca
        </div>

        <div v-else class="ads-grid">
          <div v-for="ad in nearbyAds" :key="ad.id" class="ad-card">
            <div class="card-image-container">
              <img 
                :src="ad.image_url ? (ad.image_url.startsWith('http') ? ad.image_url : BASE_URL + ad.image_url) : 'https://via.placeholder.com/300?text=Sin+Foto'" 
                :alt="ad.title" 
                class="ad-image"
              >
              <button class="favorite-btn">♡</button>
            </div>
            <div class="card-details">
              <h3 class="ad-title">{{ ad.title }}</h3> 
              <div class="price-row">
                <p class="ad-price">{{ ad.price }} € <span class="unit" v-if="ad.unit">/ {{ ad.unit }}</span></p>
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
.card-image-container { height: 180px; position: relative; }
.ad-image { width: 100%; height: 100%; object-fit: cover; }
.favorite-btn { position: absolute; top: 10px; right: 10px; background: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; }
.card-details { padding: 15px; }
.ad-title { margin: 0 0 10px 0; font-size: 1.1rem; color: #1e293b; }
.price-row { display: flex; justify-content: space-between; align-items: center; }
.ad-price { font-size: 1.2rem; font-weight: bold; color: #10b981; margin: 0; }
.unit { font-size: 0.8rem; color: #64748b; font-weight: normal; }
.btn-add { background: #eff6ff; color: #3b82f6; border: none; width: 30px; height: 30px; border-radius: 8px; font-weight: bold; cursor: pointer; }
.map-wrapper { height: 500px; width: 100%; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.section-empty { background: #e2e8f0; border-radius: 16px; padding: 40px; text-align: center; color: #64748b; }

/* --- RESPONSIVE (Versión Móvil/Tablet) --- */
@media (max-width: 1100px) {
  
  .header-content {
    padding: 15px 20px 20px 20px !important;
    align-items: center;
    height: auto !important; 
    gap: 15px 0 !important; 
  }

  /* 1. Logo Izquierda */
  .logo-container { order: 1 !important; }

  /* 2. Usuario Derecha */
  .user-zone { order: 2 !important; margin-left: auto !important; }

  /* 3. Navegación debajo */
  .nav-left {
    order: 3 !important;
    width: 100% !important;
    margin-top: 40px !important; /* Separación vertical */
    padding-top: 15px !important;
    border-top: 1px solid #f1f5f9; 

    display: flex;
    justify-content: flex-start;
    
    /* Mantenemos el scroll si hace falta, pero con estilo limpio */
    gap: 20px; /* Un poco más de espacio entre textos */
    
    overflow-x: auto;
    white-space: nowrap;
    padding-bottom: 5px;
  }
  
  .nav-left::-webkit-scrollbar { display: none; }
  .nav-left { -ms-overflow-style: none; scrollbar-width: none; }
  
  /* AQUÍ ESTÁ EL CAMBIO: Eliminamos los estilos de "botón" específicos de móvil 
     para que usen los estilos generales (texto limpio) definidos arriba. 
     Solo ajustamos el tamaño de fuente si lo ves necesario. */
  .nav-item {
    font-size: 0.95rem; /* Ajuste ligero de tamaño */
    background-color: transparent; /* Sin fondo */
    padding: 0; /* Sin relleno interno */
    border-radius: 0; /* Sin bordes redondos */
    /* Color hereda del estilo base */
  }

  .map-wrapper { height: 300px; }
  .ads-grid { grid-template-columns: repeat(auto-fill, minmax(100%, 1fr)); }
}
</style>