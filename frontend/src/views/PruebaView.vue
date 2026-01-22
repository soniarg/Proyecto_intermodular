<!-- Script v-model : muestra como al ir escribiendo sobre un input, se va mostrando el valor en pantalla  -->
<!-- <script setup>
  import { ref } from 'vue'
  const nombre = ref('') // Empieza vacío
</script>

<template>
  <input v-model="nombre" placeholder="Escribe tu nombre aquí" />
  
  <p>Hola, {{ nombre }}</p>
</template> -->

<!-- Script v:if V:else : muestra un texto u otro en función del valor de una variable-->
<!-- <script setup>
  import { ref } from 'vue'
  const estaLogueado = ref(false)
</script>

<template>
  <h1 v-if="estaLogueado">¡Bienvenido de nuevo!</h1>
  <h1 v-else>Por favor, inicia sesión</h1>

  <button @click="estaLogueado = !estaLogueado">
    Cambiar estado
  </button>
</template> -->

<!-- Script v-for : Mostrar un listado de varios elementos de un array sin necesidad de escribir la etiqueta
 'li' por cada elemento -->
<!-- <script setup>
    import { ref } from 'vue';
    const tareas = ref(['Aprender Vue', 'Hacer ejercicio', 'Leer un libro']);
</script>
<template>
    <h1>Tareas</h1>
    <ul>
        <li v-for="tarea in tareas" :key="tarea">
            {{ tarea }}
        </li>
    </ul>
</template> -->

<!-- Script guiado: mostrar una lista de productos y poder añadir un nuevo producto, actualizando la lista al momento -->
<script setup>
import { ref } from 'vue';
let nuevoProducto = ref('');
let lista = ref([]);

const anadirProducto = () => {
    if (nuevoProducto.value !== '') {
        lista.value.push(nuevoProducto.value);

        nuevoProducto.value = '';
    }
}

const borrar = (indice) => {
    lista.value.splice(indice, 1);
}
</script>
<template>
    <input v-model="nuevoProducto" type="text" placeholder="Ej: Manzanas">
    <button @click="anadirProducto">Añadir Producto</button>

    <h1>Lista de la compra</h1>
        <ul>
            <li v-for="(producto, indice) in lista" :key="producto">
                {{ producto }}
                <button @click="borrar(indice)">Borrar</button>
            </li>
        </ul>

        <p v-if="lista.length === 0">No hay productos en la lista</p>
</template>