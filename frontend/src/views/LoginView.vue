<script setup>
import { ref } from 'vue';
import api from '@/api/axios'; // Asegúrate de que apunta a tu archivo axios configurado
import { useRouter } from 'vue-router';

const router = useRouter();
const form = ref({ email: '', password: '' });
const error = ref('');

const handleLogin = async () => {
    error.value = ''; // Limpiamos errores previos
    try {
        // 1. Petición al backend
        const response = await api.post('/login', form.value);
        
        // 2. Guardar token (IMPORTANTE: usar 'auth_token' para coincidir con axios.js)
        localStorage.setItem('auth_token', response.data.access_token);
        
        // 3. Redirigir a la Home
        router.push('/');
        
    } catch (e) {
        console.error(e);
        // Mensaje amigable si falla
        if (e.response && e.response.status === 422) {
             error.value = 'Las credenciales no son correctas.';
        } else {
             error.value = 'Error de conexión. Inténtalo de nuevo.';
        }
    }
};
</script>

<template>
  <div class="auth-container">
    <div class="auth-card">
      <h2 class="auth-title">Bienvenido de nuevo!</h2>
      <p class="auth-subtitle">Entra a tu cuenta de ProxiMarkt</p>

      <form @submit.prevent="handleLogin" class="auth-form">
        
        <div class="form-group">
          <label>Email</label>
          <input v-model="form.email" type="email" placeholder="example@mail.com" required />
        </div>

        <div class="form-group">
          <label>Contraseña</label>
          <input v-model="form.password" type="password" placeholder="******" required />
        </div>

        <p v-if="error" style="color: #DC2626; text-align: center; margin-bottom: 1rem;">
            {{ error }}
        </p>

        <button type="submit" class="submit-btn">Entrar</button>
      </form>

      <div class="auth-footer">
        <p>¿No tienes cuenta? <router-link to="/register">Registrate aquí</router-link></p>
      </div>
    </div>
  </div>
</template>