<!-- BLOQUE DE CÓDIGO QUE SE CORRESPONDE CON EL COMPONENTE HIJO DE 
 PRODUCTOS -->
<!-- <script setup>
defineProps(['nombre', 'precio']);
</script>

<template>
    <div class="tarjeta">
        <h3>{{ nombre }}</h3>
        <p>Cuesta: {{ precio }}€</p>
    </div>
</template>

<style>
  .tarjeta { border: 1px solid #ccc; padding: 10px; margin: 10px; }
</style> -->

<!-- CÓDIGO DEL COMPONENTE HIJO DE USUARIOS Y NIVELES -->
<!-- <script setup>
    defineProps(['usuario', 'nivel']);
    const emit = defineEmits(['subir-nivel']);
</script>

<template>
    <div class="perfil">
        <h2>{{ usuario }} de nivel: {{ nivel }}</h2>
        <button @click="emit('subir-nivel')">
            Subir nivel
        </button>
    </div>
</template>

<style>
  .perfil { border: 1px solid #ccc; padding: 10px; margin: 10px; }
</style> -->


<!-- CÓDIGO DEL COMPONENTE HIJO DEL LISTADO DE JUEGOS -->
<script setup>
  // Definimos qué datos esperamos recibir del padre
    defineProps(['titulo', 'precio', 'comprado', 'cantidad']);
    const emit = defineEmits(['agregar', 'quitar']);
</script>

<template>
  <div class="tarjeta-juego" :class="{'destacado' : comprado}">
    <h3>{{ titulo }}</h3>
    <p>Precio: {{ precio }}€</p>
    <div :hidden="comprado">
      <button @click="emit('agregar')">Añadir al carrito</button>
    </div>

    <div :hidden="!comprado">
      <button @click="emit('quitar')">-</button> {{ cantidad }} <button @click="emit('agregar')">+</button>
    </div>
  </div>
</template>

<style scoped>
  .tarjeta-juego {
    border: 1px solid #ccc;
    padding: 10px;
    margin: 5px;
    border-radius: 8px;
  }

  .destacado {
    border: 2px solid #42b983; /* Borde verde más grueso */
    background-color: #f0fdf4; /* Fondo verde muy clarito */
    transform: scale(1.02); /* Se hace un pelín más grande */
  }
</style>