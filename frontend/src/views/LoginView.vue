<script setup>
import { ref } from 'vue';
import api from '@/api/axios';
import { useRouter } from 'vue-router';
import { useToast } from 'vue-toastification';

const toast = useToast();
const router = useRouter();
// Esta es una forma más cómoda de crear las variables reactivas.
// En lugar de crearlas por separado, se engloban dentro de una constante
// para hacer más fácil su envío a Laravel
const form = ref({ email: '', password: '' });
const error = ref('');

const handleLogin = async () => {
    error.value = ''; // Limpiamos errores previos
    try {
        // 1. Petición al backend
        const response = await api.post('/login', form.value);
        
        // 2. Guardar token (IMPORTANTE: usar 'auth_token' para coincidir con axios.js)
        localStorage.setItem('auth_token', response.data.access_token);

        //3 Mensaje de éxito del login
        toast.success('¡Login exitoso!');
        
        // 3. Redirigir a la página de Inicio
        router.push('/');
        
    } catch (e) {
        console.error(e);
        // Si se produce un error de código 422, es debido a credenciales incorrectas
        if (e.response && e.response.status === 422) {
             toast.error = 'Las credenciales no son correctas.';
        // En otro caso, se devuelve un mensaje más genérico
        } else {
             toast.error = 'Error de conexión. Inténtalo de nuevo.';
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