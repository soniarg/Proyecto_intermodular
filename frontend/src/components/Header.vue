<script setup>
import { ref, onMounted } from 'vue';
import api from '@/api/axios'; 

// Variables de estado
const isLoggedIn = ref(false);
const userData = ref(null);
const BASE_URL = 'http://localhost:8000/storage/';

// COMPROBACIÓN DE SESIÓN AL CARGAR EL HEADER
onMounted(async () => {
  // 1. Miramos si hay token guardado
  const token = localStorage.getItem('auth_token');
  
  if (token) {
    isLoggedIn.value = true;
    try {
      // 2. Si hay token, pedimos los datos del usuario para la foto y el nombre
      const response = await api.get('/user');
      userData.value = response.data;
    } catch (error) {
      console.error("Error validando sesión en Header:", error);
      // Si el token no vale, cerramos sesión visualmente
      localStorage.removeItem('auth_token');
      isLoggedIn.value = false;
    }
  }
});
</script>

<template>
  <header class="main-header">
    <div class="header-content">
      
      <nav class="nav-left">
        <router-link to="/" class="nav-item">Inicio</router-link>
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
</template>

<style scoped>
/* --- ESTILOS DEL HEADER --- */
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
  gap: 15px 30px; 
}

.nav-left { display: flex; gap: 20px; order: 1; }
.nav-item { text-decoration: none; color: #64748b; font-weight: 500; transition: color 0.2s; white-space: nowrap; padding: 5px 0; }
.nav-item:hover, .nav-item.active, .router-link-active { color: #3b82f6; font-weight: 600; }

.logo-container { order: 2; flex-shrink: 0; }
.logo-container .site-title { margin: 0; font-size: 1.5rem; color: #1e293b; }
.highlight { color: #10b981; }

.user-zone { display: flex; align-items: center; gap: 15px; order: 3; flex-shrink: 0; }
.auth-buttons { display: flex; gap: 15px; align-items: center; }
.login-link { text-decoration: none; color: #64748b; font-weight: 600; }
.register-btn { background-color: #3b82f6; color: white; padding: 8px 16px; border-radius: 20px; text-decoration: none; font-weight: 600; transition: background 0.2s; }

.profile-pill { display: flex; align-items: center; gap: 10px; background: #f1f5f9; padding: 5px 10px 5px 15px; border-radius: 30px; text-decoration: none; color: #334155; font-weight: 600; }
.avatar-circle, .avatar-circle-img { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; }
.avatar-circle { background: #3b82f6; color: white; display: flex; align-items: center; justify-content: center; }

/* --- RESPONSIVE (TU CÓDIGO) --- */
@media (max-width: 1100px) {
  .header-content { padding: 15px 20px 20px 20px !important; align-items: center; height: auto !important; gap: 15px 0 !important; }
  .logo-container { order: 1 !important; }
  .user-zone { order: 2 !important; margin-left: auto !important; }

  .nav-left {
    order: 3 !important; width: 100% !important; margin-top: 40px !important; padding-top: 15px !important; border-top: 1px solid #f1f5f9; 
    display: flex; justify-content: flex-start; gap: 20px; overflow-x: auto; white-space: nowrap; padding-bottom: 5px;
  }
  .nav-left::-webkit-scrollbar { display: none; }
  .nav-left { -ms-overflow-style: none; scrollbar-width: none; }
  
  .nav-item { font-size: 0.95rem; background-color: transparent; padding: 0; border-radius: 0; }
}
</style>