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
            <router-link to="/marketplace" class="see-more">Mostrar todo &rarr;</router-link>
        </div>

        <div v-if="loading" class="state-message">
            ⏳ Cargando productos frescos...
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
  
  /* Margen de seguridad para evitar solapamientos */
  gap: 15px 30px; 
}

/* Navegación Desktop */
.nav-left { display: flex; gap: 20px; order: 1; }

/* ESTILO GENERAL (Texto limpio, igual en Desktop y Móvil) */
.nav-item { 
  text-decoration: none; 
  color: #64748b; 
  font-weight: 500; 
  transition: color 0.2s; 
  white-space: nowrap; 
  /* Añadimos un poco de padding vertical transparente para facilitar el toque en móvil sin cambiar el diseño visual */
  padding: 5px 0;
}

.nav-item:hover, .nav-item.active { 
  color: #3b82f6; 
  /* Opcional: Si quieres que el activo tenga negrita */
  font-weight: 600; 
}

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