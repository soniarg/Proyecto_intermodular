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
    import { ref, computed, watch, onMounted } from 'vue';
    const juegos = ref([]);

    const carrito = ref([]);

    const claveLocalStorage = 'carrito';

    if(localStorage.getItem(claveLocalStorage)){
        carrito.value = JSON.parse(localStorage.getItem(claveLocalStorage));
    }

    const agregar = (juego) => {
        const juegoCarrito = carrito.value.find(item => item.id === juego.id);

        if(juegoCarrito){
            juegoCarrito.cantidad++;
        }else{
            const nuevoJuego = {...juego, cantidad: 1};

            carrito.value.push(nuevoJuego);
        }
    }

    const quitar = (juego) => {
        const juegoCarrito = carrito.value.find(item => item.id === juego.id);

        if(!juegoCarrito){
            return;
        }

        if(juegoCarrito.cantidad > 1){
            juegoCarrito.cantidad--;
        }else{
            carrito.value = carrito.value.filter(item => item.id !== juego.id);
        }
    }

    const busqueda = ref('');

    const buscar = (juegoId) => {
        return carrito.value.some(item => item.id === juegoId);
    }

    const obtenerCantidad = (juego) => {
        const juegoCarrito = carrito.value.find(item => item.id === juego.id);

        return juegoCarrito ? juegoCarrito.cantidad : 0;
    }

    const juegosFiltrados = computed(() => {
        if(busqueda.value === ''){
            return juegos.value
        }

        return juegos.value.filter(juego => juego.name.toLocaleLowerCase().includes(busqueda.value.toLocaleLowerCase()));
    });

    const totalCarrito = computed(() => {
        let total = 0;

        if(carrito.value.length === 0){
            return 0;
        }

        carrito.value.forEach(juego => {
            total += juego.cost * juego.cantidad
        });

        return total;
    });

    // La variable watch accede al carrito internamente, por lo que al definir un
    // parámetro, podemos acceder al carrito sin poner .value . Si no se usara
    // dicho parametro, todas las veces que se ponga carrito tendría que 
    // ponerse también .value
    watch(carrito, (carritoLimpio) => {
        const carritoJson = JSON.stringify(carritoLimpio);
        localStorage.setItem(claveLocalStorage, carritoJson);
    }, {deep: true});

    const cargando = ref(true);

    onMounted(() => {
        setTimeout(() => {
            juegos.value = [
                {id: 1, name: "The Legend of Zelda", cost: 60}, 
                {id: 2, name: "Super Mario Galaxy", cost: 50},
                {id: 3, name: "Mario Kart", cost: 90},
                {id: 4, name: "Minecraft", cost: 30}
            ]

            cargando.value = false;
        }, 2000);
    });
    
</script>

<template>
    <h2 v-if="cargando">Cargando...</h2>

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

        <p>Total: {{ totalCarrito }}€</p>
    </div>

</template>