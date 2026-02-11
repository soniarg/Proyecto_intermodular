<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api/axios';
import { useToast } from 'vue-toastification';

const toast = useToast();
const router = useRouter();

const form = ref({
  name: '',
  surname: '',
  email: '',
  password: '',
  password_confirmation: ''
});

const error = ref('');

const handleRegister = async () => {
  error.value = '';

  if (form.value.password !== form.value.password_confirmation) {
    toast.error = "Las contraseñas no coinciden";
    return;
  }

  try {
    // 2. CAMBIAMOS EL CONSOLE.LOG POR LA LLAMADA REAL
    const response = await api.post('/register', form.value);

    localStorage.setItem('auth_token', response.data.access_token);

    toast.success("Login exitoso");
    
    router.push('/'); 
    
  } catch (e) {
    console.error("Error al registrar:", e);
    if(e.response && e.response.data.message){
      error.value = e.response.data.message;
    }else{
      error.value = "Error al crear la cuenta";
    }
  }
};
</script>

<template>
  <div class="auth-container">
    <div class="auth-card">
      <h2 class="auth-title">Crear Cuenta</h2>
      <p class="auth-subtitle">Únete a ProxiMarkt hoy mismo</p>

      <p v-if="error" style="color: red;">{{ error }}</p>

      <form @submit.prevent="handleRegister" class="auth-form">
        
        <div class="form-group">
          <label>Nombre</label>
          <input v-model="form.name" type="text" placeholder="Nombre" required />
        </div>

        <div class="form-group">
          <label>Apellidos</label>
          <input v-model="form.surname" type="text" placeholder="Apellido" required>
        </div>

        <div class="form-group">
          <label>Email</label>
          <input v-model="form.email" type="email" placeholder="ejemplo@mail.com" required />
        </div>

        <div class="form-group">
          <label>Contraseña</label>
          <input v-model="form.password" type="password" placeholder="******" required />
        </div>

        <div class="form-group">
          <label>Confirmar Contraseña</label>
          <input v-model="form.password_confirmation" type="password" placeholder="******" required />
        </div>

        <button type="submit" class="submit-btn">Registrarse</button>
      </form>

      <div class="auth-footer">
        <p>¿Ya tienes cuenta? <router-link to="/login">Entrar aquí</router-link></p>
      </div>
    </div>
  </div>
</template>