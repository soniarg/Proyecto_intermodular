<script setup>
  import { RouterView } from 'vue-router'
  import { useAuthStore } from './stores/auth';
  import { onMounted } from 'vue';

  const authStore = useAuthStore();

  onMounted(() => {
    authStore.getUser();
  });
</script>

<template>
  <div>
    <header>
      <h1>Tienda de juegos</h1>
      <nav>
        <RouterLink to="/prueba/componente">Juegos</RouterLink>
        <RouterLink to="/prueba/carrito">Carrito</RouterLink>
      </nav>
    </header>
  </div>

  <RouterView />
</template>

<style scoped>
/* Un poco de estilo para que parezca un menú */
nav {
  padding: 10px;
  background-color: #f0f0f0;
}
a {
  margin: 0 10px;
  text-decoration: none;
  font-weight: bold;
  color: #2c3e50;
}
/* Vue le pone esta clase automática al enlace activo */
.router-link-active {
  color: #42b983; /* Verde Vue */
}
</style>