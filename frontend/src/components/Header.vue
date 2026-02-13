<script setup>
import { ref, onMounted, watch } from 'vue'; 
import { useRoute } from 'vue-router'; 
import api from '@/api/axios'; 
import NotificationBell from '@/components/NotificationBell.vue'; 

const isLoggedIn = ref(false);
const userData = ref(null);
const BASE_URL = 'http://localhost:8000/storage/';
const route = useRoute(); 

const fetchUser = async () => {
  const token = localStorage.getItem('auth_token');
  if (token) {
    isLoggedIn.value = true;
    try {
      const response = await api.get('/user');
      userData.value = response.data;
    } catch (error) {
      localStorage.removeItem('auth_token');
      isLoggedIn.value = false;
    }
  }
};

onMounted(fetchUser);
watch(() => route.path, fetchUser);
</script>

<template>
  <header class="main-header">
    <div class="header-content">
      
      <div class="header-left">
        <router-link to="/" class="logo-link">
          <img src="@/assets/logo.png" alt="ProxiMarkt" class="main-logo" />
        </router-link>
      </div>

      <nav class="header-center">
        <router-link to="/" class="nav-item">Inicio</router-link>
        <router-link to="/marketplace" class="nav-item">Marketplace</router-link>
        
        <template v-if="isLoggedIn">
            <router-link to="/my-purchases" class="nav-item">Mis Compras</router-link>
            <router-link to="/my-sales" class="nav-item">Mis Ventas</router-link>
        </template>
      </nav>

      <div class="header-right">
        <template v-if="isLoggedIn && userData"> 
            <div class="bell-wrapper">
                <NotificationBell />
            </div>
            <router-link to="/perfil" class="profile-pill">
                <span class="user-name">{{ userData.name }}</span>
                <img v-if="userData.avatar_url" :src="userData.avatar_url.startsWith('http') ? userData.avatar_url : BASE_URL + userData.avatar_url" class="avatar-img">
                <div v-else class="avatar-placeholder">{{ userData.name.charAt(0).toUpperCase() }}</div>
            </router-link>
        </template>

        <template v-else>
          <div class="auth-btns">
            <router-link to="/login" class="login-link">Entrar</router-link>
            <router-link to="/register" class="register-btn">Crear Cuenta</router-link>
          </div>
        </template>
      </div>

    </div>
  </header>
</template>

<style scoped>
.main-header {
  background: white;
  box-shadow: 0 4px 15px rgba(0,0,0,0.05);
  position: sticky;
  top: 0;
  z-index: 1000;
  width: 100%;
}

.header-content {
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 20px;
  height: 80px;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.header-left {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
}

.main-logo {
  height: 50px;
  width: auto;
  display: block;
}

/* --- CENTRADO EN ESCRITORIO --- */
.header-center {
  flex: 1;
  display: flex;
  justify-content: center;
  gap: 30px;
  margin: 0 auto;
}

.nav-item {
  text-decoration: none;
  color: #64748b;
  font-weight: 600;
  font-size: 0.95rem;
  transition: color 0.2s;
}

.nav-item:hover, .router-link-active {
  color: #10b981;
}

.header-right {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
  gap: 20px;
}

.bell-wrapper { display: flex; align-items: center; }
.auth-btns { display: flex; gap: 15px; align-items: center; }
.login-link { text-decoration: none; color: #64748b; font-weight: 600; }

.register-btn {
  background: #3b82f6;
  color: white;
  padding: 10px 20px;
  border-radius: 12px;
  text-decoration: none;
  font-weight: 600;
}

.profile-pill {
  display: flex;
  align-items: center;
  gap: 12px;
  background: #f1f5f9;
  padding: 5px 5px 5px 15px;
  border-radius: 30px;
  text-decoration: none;
  color: #1e293b;
  font-weight: 600;
}

/* Limitamos el nombre para que no rompa el diseño si es muy largo */
.user-name {
  max-width: 120px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.avatar-img, .avatar-placeholder {
  width: 35px;
  height: 35px;
  border-radius: 50%;
  object-fit: cover;
}

.avatar-placeholder {
  background: #10b981;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
}

/* --- RESPONSIVE AJUSTADO --- */
@media (max-width: 1100px) {
  .header-content {
    height: auto;
    padding: 15px 20px;
    display: flex;
    flex-wrap: wrap; 
    justify-content: space-between;
    align-items: center;
  }

  .header-left { order: 1; }
  .header-right { order: 2; }

  .header-center {
    order: 3;
    width: 100%;
    flex: none; 
    margin: 15px auto 0 auto; /* Centrado solicitado */
    padding-top: 15px;
    border-top: 1px solid #f1f5f9;
    justify-content: center;
    overflow-x: auto;
    gap: 20px;
    scrollbar-width: none;
    display: flex;
  }
  
  .header-center::-webkit-scrollbar { display: none; }
  
  /* Ahora el nombre SI se ve, solo reducimos el ancho máximo un poco más */
  .user-name { 
    display: block; 
    max-width: 80px; 
  }
  
  .main-logo { height: 40px; }
  
  /* Ajuste pequeño para que la píldora no sea gigante en móvil */
  .profile-pill {
    padding: 3px 3px 3px 10px;
    gap: 8px;
  }
}
</style>