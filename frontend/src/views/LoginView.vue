<template>
  <div class="login-box">
    <h1>Acceso</h1>
    <form @submit.prevent="handleLogin">
      <input v-model="form.email" type="email" placeholder="Email" required />
      <input v-model="form.password" type="password" placeholder="Contraseña" required />
      <button type="submit">Entrar</button>
    </form>
    <p v-if="error" class="error">{{ error }}</p>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import api from '../axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const form = ref({ email: '', password: '' });
const error = ref('');

const handleLogin = async () => {
    try {
        // Petición a tu AuthController
        const response = await api.post('/login', form.value);
        
        // Guardar token
        localStorage.setItem('token', response.data.access_token);
        
        // Ir a usuarios
        router.push('/users');
    } catch (e) {
        error.value = 'Error: Credenciales incorrectas';
    }
};
</script>

<style scoped>
.login-box { max-width: 300px; margin: 50px auto; padding: 2rem; border: 1px solid #ccc; text-align: center; }
input { display: block; width: 90%; margin: 10px auto; padding: 8px; }
button { width: 95%; padding: 10px; background: #2c3e50; color: white; cursor: pointer; }
.error { color: red; margin-top: 10px; }
</style>