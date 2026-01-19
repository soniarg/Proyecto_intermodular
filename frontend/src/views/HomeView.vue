<script setup>
import { ref, onMounted } from 'vue';
import MapView from './MapView.vue'; 
import api from '@/axios'; 

// Comprobamos si hay token
const isLoggedIn = ref(!!localStorage.getItem('auth_token'));
const userData = ref(null); 

// Aseguramos que el puerto coincida con tu backend (8000)
const BASE_URL = 'http://localhost:8000/storage/'; 

// Datos Mock de productos
const nearbyAds = ref([
  { 
    id: 1, title: 'Tomates Ensalada', price: '2,50', unit: 'kg',
    image: 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?auto=format&fit=crop&w=500&q=80' 
  },
  { 
    id: 2, title: 'Zanahorias de Valencia', price: '3,10', unit: 'kg',
    image: 'https://www.infobae.com/resizer/v2/4EGI4DIZLFF3FJMYCXOSRVIUP4.jpg?auth=68a9f3c00efa1709305190649f723c12e62a04904593cf0083b2cbd0a417f6a4&smart=true&width=577&height=323&quality=85' 
  },
  { 
    id: 3, title: 'Fruta Fresca', price: '15,00', unit: 'pack',
    image: 'https://images.unsplash.com/photo-1610832958506-aa56368176cf?auto=format&fit=crop&w=500&q=80' 
  }
]);

onMounted(async () => {
  if (isLoggedIn.value) {
    try {
      const response = await api.get('/user');
      userData.value = response.data;
    } catch (error) {
      console.error("Error al cargar usuario en header:", error);
      localStorage.removeItem('auth_token');
      isLoggedIn.value = false;
    }
  }
});
</script>

<template>
  <div class="home-view">
    
    <header class="main-header">
      <div class="header-content">
        <nav class="nav-left">
          <router-link to="/" class="nav-item active">Inicio</router-link>
          <router-link to="/explorar" class="nav-item">Explorar</router-link>
          <router-link to="/mapas" class="nav-item">Mapa</router-link>
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
                :src="BASE_URL + userData.avatar_url" 
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
        <h2 class="section-title">Explora tu alrededor</h2>
        <div class="map-wrapper">
          <MapView />
        </div>
      </section>

      <section class="section-empty">
        <div class="placeholder-content">
          <h3>Espacio para Novedades</h3>
          <p>Esta sección esta reservada a una nueva sección.</p>
        </div>
      </section>
    </main>
  </div>
</template>