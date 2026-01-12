<template>
  <div class="container">
    <div class="header">
      <h2>Gestión de Usuarios</h2>
      <button @click="logout" class="btn-logout">Cerrar Sesión</button>
    </div>

    <div class="card">
      <h3>Nuevo Usuario</h3>
      <form @submit.prevent="crearUsuario" class="form-grid">
        <input v-model="nuevo.name" placeholder="Nombre" required />
        <input v-model="nuevo.surname" placeholder="Apellido" required />
        <input v-model="nuevo.email" type="email" placeholder="Email" required />
        <input v-model="nuevo.password" type="password" placeholder="Contraseña" required />
        <select v-model="nuevo.role">
          <option value="buyer">Comprador</option>
          <option value="seller">Vendedor</option>
          <option value="admin">Admin</option>
        </select>
        <button type="submit">Crear</button>
      </form>
    </div>

    <p v-if="cargando">Cargando datos...</p>
    <table v-else>
      <thead>
        <tr>
          <th>ID</th>
          <th>Nombre</th>
          <th>Email</th>
          <th>Rol</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="u in usuarios" :key="u.id">
          <td>{{ u.id }}</td>
          <td>{{ u.name }} {{ u.surname }}</td>
          <td>{{ u.email }}</td>
          <td>{{ u.role }}</td>
          <td>
            <button @click="borrarUsuario(u.id)" class="btn-del">Eliminar</button>
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const usuarios = ref([]);
const cargando = ref(true);
const nuevo = ref({ name: '', surname: '', email: '', password: '', role: 'buyer' });

// 1. GET (Index)
const cargarUsuarios = async () => {
    try {
        const res = await api.get('/users');
        usuarios.value = res.data;
    } catch (e) {
        if(e.response?.status === 401) router.push('/'); // Si no autorizado, al login
    } finally {
        cargando.value = false;
    }
};

// 2. POST (Store)
const crearUsuario = async () => {
    try {
        await api.post('/users', nuevo.value);
        nuevo.value = { name: '', surname: '', email: '', password: '', role: 'buyer' };
        cargarUsuarios(); // Recargar tabla
    } catch (e) {
        alert('Error: ' + (e.response?.data?.message || 'Revisa los datos'));
    }
};

// 3. DELETE (Destroy)
const borrarUsuario = async (id) => {
    if (!confirm('¿Seguro?')) return;
    try {
        await api.delete(`/users/${id}`);
        cargarUsuarios();
    } catch (e) {
        alert('Error al borrar');
    }
};

// Logout
const logout = async () => {
    try { await api.post('/logout'); } catch(e){}
    localStorage.removeItem('token');
    router.push('/');
};

onMounted(() => cargarUsuarios());
</script>

<style scoped>
.container { max-width: 800px; margin: 0 auto; padding: 20px; font-family: sans-serif; }
.header { display: flex; justify-content: space-between; margin-bottom: 20px; }
.card { background: #f4f4f4; padding: 15px; margin-bottom: 20px; border-radius: 8px; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
.btn-del { background: #e74c3c; color: white; border: none; padding: 5px 10px; cursor: pointer; }
.btn-logout { background: #333; color: white; border: none; padding: 8px 15px; cursor: pointer; }
</style>