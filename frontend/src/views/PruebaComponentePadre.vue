<!-- CÓDIGO PARA MOSTRAR UN LISTADO DE PRODUCTOS USANDO UN COMPONENTE HIJO
<script setup>
    import Producto from './PruebaComponenteHijo.vue';
</script>

<template>
    <h1>Tienda Online</h1>

    <Producto nombre="Zapatillas Nike" precio="51.27"/>

    <Producto nombre="Camiseta" precio="10"/>
</template> -->

<!-- CÓDIGO PARA MOSTRAR USUARIOS, NIVELES Y SUBIR DE NIVEL -->
<!-- <script setup>
    import { ref } from 'vue';
    import Usuario from './PruebaComponenteHijo.vue';

    let nivelActual = ref(1);
</script>

<template>
    <h1>Usuarios</h1>

    <Usuario usuario="user1" :nivel="nivelActual" @subir-nivel="nivelActual++"/>

    <Usuario usuario="user2" nivel="2"/>
</template> -->

<!-- CÓDIGO PARA MOSTRAR UN LISTADO DE JUEGOS, FILTRARLOS, AÑADIRLOS A UN CARRITO Y BORRARLOS -->
<script setup>
    import Juego from './PruebaComponenteHijo.vue';
    // import { useCarrito } from '@/views/composables/useCarrito';
    import { useCarritoStore } from '@/stores/carrito';
    import { storeToRefs } from 'pinia';
    import { useJuegosStore } from '@/stores/juegos';
    import { ref, computed, onMounted } from 'vue';

    const { 
        agregar, 
        quitar, 
        buscar, 
        obtenerCantidad 
    } = useCarritoStore();

    const juegosStore = useJuegosStore();
    const { juegos, cargando, error } = storeToRefs(juegosStore);
    const { cargarJuegos } = juegosStore;

    // Esta variable guarda la cadena de texto que se introduce en el input
    // para filtar por nombre los juegos
    const busqueda = ref('');

    // Esta función muestra aquellos juegos que coincidan con el contenido de la 
    // variable 'busqueda' de arriba
    const juegosFiltrados = computed(() => {
        // Si búsqueda no tiene ninguna cadena de texto, se muestran todos los juegos
        if(busqueda.value === ''){
            return juegos.value
        }

        // Se devuelve la lista de juegos filtrada por nombre, de manera que se muestran 
        // todos los juegos cuyo nombre contenga la cadena de texto de la variable 'busqueda'
        return juegos.value.filter(juego => juego.name.toLocaleLowerCase().includes(busqueda.value.toLocaleLowerCase()));
    });

    // La función onMounted sirve para cargar lógica de la web que necesita que cargue
    // primero el html antes de acceder a dicha lógica. Aquí se hace un ejemplo
    // de carga de juegos. Aunque se pongan los juegos manualmente desde el script,
    // realmente se haría desde una base de datos.
    // Las aplicaciones que puede tener son:
    // - Servidores externos (acceder a datos de servidores)
    // - Cargar librerías de gráficos o mapas
    // - Hacer ciertas cosas con el cursos o la barra de desplazamiento
    // - Interactuar con el teclado o la ventana del navegador
    // onMounted(() => {
    //     // Con setTimeout, se establece el tiempo que tarda en rellenarse la lista de juegos
    //     // y luego se cambia el valor de la variable 'cargando' a false para mostrar la lista
    //     // de juegos
    //     setTimeout(() => {
    //         juegos.value = [
    //             {id: 1, name: "The Legend of Zelda", cost: 60}, 
    //             {id: 2, name: "Super Mario Galaxy", cost: 50},
    //             {id: 3, name: "Mario Kart", cost: 90},
    //             {id: 4, name: "Minecraft", cost: 30}
    //         ]

    //         cargando.value = false;
    //     }, 2000);
    // }); Este código es antiguo, anterior a crear el store de juegos

    onMounted(() => {
        cargarJuegos();
    });
    
</script>

<!-- Importante: dentro del html/template no hace falta usar .value para acceder
 al contenido de las variables -->
<template>
    <!-- Se muestra este texto al principio mientras carga la web -->
    <h2 v-if="cargando">Cargando...</h2>

    <div v-else-if="error">
        <p>{{ error }}</p>
        <button @click="cargarJuegos">Reintentar</button>
    </div>

    <!-- Luego de ejecutar el onMounted, se muestran los juegos -->
    <div v-else>
        <h1>Juegos</h1>

        <input v-model="busqueda" type="text" placeholder="Buscar juego...">

        <Juego
            v-for="juego in juegosFiltrados"
            :key="juego.id"
            :titulo="juego.name"
            :precio="juego.cost"
            @agregar="agregar(juego)"
            :cantidad="obtenerCantidad(juego)"
            @quitar="quitar(juego)"
            :comprado="buscar(juego.id)"
        />
    </div>

</template>
