<script setup>
import { ref, onMounted } from 'vue';
import MapView from './MapView.vue'; 
import api from '@/api/axios'; 

const isLoggedIn = ref(!!localStorage.getItem('auth_token'));
const userData = ref(null); 
const BASE_URL = 'http://localhost:8000/storage/'; 
const userCoords = ref(null); 

const nearbyAds = ref([]); 
const loading = ref(true); 

const getUserLocation = () => {
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(
      (position) => { userCoords.value = [position.coords.latitude, position.coords.longitude]; },
      (error) => { userCoords.value = null; }
    );
  }
};

const fetchProducts = async () => {
  loading.value = true;
  try {
    const response = await api.get('/products'); 
    const allProducts = response.data.data || response.data;

    if (userData.value && userData.value.id) {
        nearbyAds.value = allProducts.filter(product => product.seller_id !== userData.value.id);
    } else {
        nearbyAds.value = allProducts;
    }
  } catch (error) {
    console.error("Error:", error);
    nearbyAds.value = [];
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  if (isLoggedIn.value) {
    try {
      const response = await api.get('/user');
      userData.value = response.data;
    } catch (error) {
       // manejo error
    }
  }
  getUserLocation();
  await fetchProducts(); 
});
</script>

<template>
  <div class="home-view">
    <main class="main-container">
      
      <section class="section-ads">
        <div class="section-header">
            <h2 class="section-title">Productos Frescos Cerca</h2>
            <router-link to="/marketplace" class="see-more">Mostrar todo</router-link>
        </div>

        <div v-if="loading" style="text-align:center; padding:20px;">Cargando...</div>

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
              </div>
            <div class="card-details">
              <h3 class="ad-title">{{ ad.title }}</h3> 
              <div class="price-row">
                <p class="ad-price">{{ ad.price }} € <span class="unit" v-if="ad.unit">/ {{ ad.unit }}</span></p>
                </div>
            </div>
          </div>
        </div>
      </section>

      <section class="section-map">
        <div class="section-header">
            <h2 class="section-title">Puntos de Recogida Cercanos</h2>
            
            <router-link 
                v-if="userData && ['seller', 'vendedor'].includes(userData.role)" 
                to="/seller/pickup-points" 
                class="btn-add-point"
            >
                 + Añadir Punto
            </router-link>
            </div>

        <div class="map-wrapper">
          <MapView :userLocation="userCoords" />
        </div>
      </section>

    </main>
  </div>
</template>

<style scoped>
.home-view { font-family: 'Segoe UI', sans-serif; background-color: #f8fafc; min-height: 100vh; }

.main-container { max-width: 1200px; margin: 30px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 40px; }
.section-title { font-size: 1.5rem; color: #1e293b; margin-bottom: 0; /* Ajustado para alinear con botón */ }

.section-header { 
    display: flex; 
    justify-content: space-between; 
    align-items: center; 
    margin-bottom: 20px; 
}

.see-more { color: #3b82f6; text-decoration: none; font-weight: 600; }

.btn-add-point {
    background-color: #10b981;
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.9rem;
    transition: background 0.2s, transform 0.2s;
    box-shadow: 0 2px 5px rgba(16, 185, 129, 0.3);
}
.btn-add-point:hover {
    background-color: #059669;
    transform: translateY(-2px);
}

.ads-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 25px; }
.ad-card { background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s; }
.card-image-container { height: 180px; position: relative; }
.ad-image { width: 100%; height: 100%; object-fit: cover; }

.card-details { padding: 15px; }
.ad-title { margin: 0 0 10px 0; font-size: 1.1rem; color: #1e293b; }
.price-row { display: flex; justify-content: space-between; align-items: center; }
.ad-price { font-size: 1.2rem; font-weight: bold; color: #10b981; margin: 0; }
.unit { font-size: 0.8rem; color: #64748b; font-weight: normal; }

.map-wrapper { height: 600px; width: 100%; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
.state-message.empty { text-align: center; padding: 40px; color: #64748b; font-size: 1.1rem; }

@media (max-width: 1100px) {
  .map-wrapper { height: 600px; width:90% ; margin: 0 auto}
  .ads-grid { grid-template-columns: repeat(auto-fill, minmax(100%, 1fr)); }
}
</style>