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
    
    <header class="main-header">
      <div class="header-content">
        <nav class="nav-left">
          <router-link to="/" class="nav-item active">Inicio</router-link>
          <router-link to="/marketplace" class="nav-item">Marketplace</router-link>
          <router-link to="/my-purchases" class="nav-item">Mis Compras</router-link>
          <router-link to="/my-sales" class="nav-item">Mis Ventas</router-link>
        </nav>

        <div class="logo-container">
          <h1 class="site-title">Proxi<span class="highlight">Markt</span></h1>
        </div>

        <div class="user-zone">
          <template v-if="isLoggedIn">
            <router-link to="/perfil" class="profile-pill">
              <span class="user-name">{{ userData ? userData.name : 'Mi Perfil' }}</span>
              
              <img 
                v-if="userData && userData.avatar_url" 
                :src="userData.avatar_url.startsWith('http') ? userData.avatar_url : BASE_URL + userData.avatar_url" 
                class="avatar-circle-img" 
                alt="Avatar"
              >
              <div v-else class="avatar-circle">
                {{ userData ? userData.name.charAt(0).toUpperCase() : 'U' }}
              </div>
            </router-link>
          </template>
          <template v-else>
            <div class="auth-buttons">
                <router-link to="/login" class="login-link">Entrar</router-link>
                <router-link to="/register" class="register-btn">Crear Cuenta</router-link>
            </div>
          </template>
        </div>
      </div>
    </header>

    <main class="main-container">
      <section class="section-ads">
        <div class="section-header">
            <h2 class="section-title">Productos Frescos Cerca</h2>
            <a href="#" class="see-more">Mostrar todo &rarr;</a>
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
/* --- ESTILOS BASE (Pantallas Grandes) --- */
.home-view { font-family: 'Segoe UI', sans-serif; background-color: #f8fafc; min-height: 100vh; }

.main-header { 
  background: white; 
  box-shadow: 0 4px 20px rgba(0,0,0,0.08); 
  position: sticky; 
  top: 0; 
  z-index: 100;
  width: 100%;
}

.header-content { 
  max-width: 1200px; 
  margin: 0 auto; 
  padding: 0 20px; 
  min-height: 70px;
  
  display: flex; 
  align-items: center; 
  justify-content: space-between; 
  flex-wrap: wrap; 
  
  /* NUEVO: Esto evita el solapamiento. Crea un margen obligatorio entre elementos.
     Si no cabe el hueco de 20px, salta de línea automáticamente. */
  gap: 15px 30px; 
}

/* Navegación Desktop */
.nav-left { display: flex; gap: 20px; order: 1; }
.nav-item { text-decoration: none; color: #64748b; font-weight: 500; transition: color 0.2s; white-space: nowrap; }
.nav-item:hover, .nav-item.active { color: #3b82f6; }

/* Logo Desktop */
.logo-container { order: 2; flex-shrink: 0; }
.logo-container .site-title { margin: 0; font-size: 1.5rem; color: #1e293b; }
.highlight { color: #10b981; }

/* Usuario Desktop */
.user-zone { display: flex; align-items: center; gap: 15px; order: 3; flex-shrink: 0; }

/* Estilos Generales */
.auth-buttons { display: flex; gap: 15px; align-items: center; }
.login-link { text-decoration: none; color: #64748b; font-weight: 600; }
.register-btn { background-color: #3b82f6; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: 600; transition: background 0.2s; }
.profile-pill { display: flex; align-items: center; gap: 10px; background: #f1f5f9; padding: 5px 10px 5px 15px; border-radius: 30px; text-decoration: none; color: #334155; font-weight: 600; }
.avatar-circle, .avatar-circle-img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
.avatar-circle { background: #3b82f6; color: white; display: flex; align-items: center; justify-content: center; }

/* Main Container */
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
/* Subido a 1100px para que salte ANTES de que se toquen */
@media (max-width: 1100px) {
  
  .header-content {
    padding: 15px 20px 20px 20px !important;
    align-items: center;
    height: auto !important; 
    /* En móvil reducimos el hueco horizontal, pero mantenemos el vertical */
    gap: 15px 0 !important; 
  }

  /* 1. Logo Izquierda */
  .logo-container {
    order: 1 !important;
  }

  /* 2. Usuario Derecha */
  .user-zone {
    order: 2 !important;
    margin-left: auto !important; 
  }

  /* 3. Navegación debajo */
  .nav-left {
    order: 3 !important;
    width: 100% !important;
    /* Eliminamos margin-top extra porque ya usamos 'gap' en el padre */
    margin-top: 40px !important; 
    padding-top: 15px !important;
    border-top: 1px solid #f1f5f9; 

    display: flex;
    justify-content: flex-start;
    gap: 8px;
    
    overflow-x: auto;
    white-space: nowrap;
    padding-bottom: 5px;
  }
  
  .nav-left::-webkit-scrollbar { display: none; }
  .nav-left { -ms-overflow-style: none; scrollbar-width: none; }
  
  .nav-item {
    font-size: 0.8rem;
    padding: 8px 10px;
    background-color: #f1f5f9;
    border-radius: 20px;
    color: #475569;
  }
  
  .nav-item.active {
    background-color: #dbeafe; 
    color: #2563eb;
    font-weight: 700;
  }

  .map-wrapper { height: 300px; }
  .ads-grid { grid-template-columns: repeat(auto-fill, minmax(100%, 1fr)); }
}
</style>