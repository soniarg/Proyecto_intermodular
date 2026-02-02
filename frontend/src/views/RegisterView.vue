<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api/axios';
const router = useRouter();

const form = ref({
  name: '',
  surname: '',
  email: '',
  password: '',
  password_confirmation: ''
});

const handleRegister = async () => {
  if (form.value.password !== form.value.password_confirmation) {
    alert("Les contrasenyes no coincideixen");
    return;
  }

  try {
    // 2. CAMBIAMOS EL CONSOLE.LOG POR LA LLAMADA REAL
    const response = await api.post('/register', form.value);
    
    console.log("Registro exitoso:", response.data);
    alert("Compte creat correctament! Ara pots entrar.");
    
    // Opcional: Si el backend devuelve token al registrar, podrías loguearlo directamente aquí.
    // De momento, lo mandamos al login:
    router.push('/login'); 
  } catch (error) {
    console.error("Error al registrar:", error);
    // Mostramos el mensaje de error del backend si existe (ej: "Email ya en uso")
    alert(error.response?.data?.message || "Error al crear el compte");
  }
};
</script>

<template>
  <div class="auth-container">
    <div class="auth-card">
      <h2 class="auth-title">Crear Cuenta</h2>
      <p class="auth-subtitle">Únete a ProxiMarkt hoy mismo</p>

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