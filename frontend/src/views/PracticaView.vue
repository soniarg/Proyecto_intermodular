<script setup>
    import { ref, computed } from 'vue'
    import ItemCompraView from './ItemCompraView.vue'

    const nuevoItem = ref('')
    const listaCompra = ref([
        { id: 1, nombre: 'Manzanas', cantidad: 0 },
        { id: 2, nombre: 'Cereales', cantidad: 0 },
        { id: 3, nombre: 'Leche', cantidad: 0 }
    ])

    const agregar = () => {
        if (nuevoItem.value !== '') {
            const nuevoProducto = {
                id: Date.now(),
                nombre: nuevoItem.value,
                cantidad: 0
            }
            listaCompra.value.push(nuevoProducto)
            nuevoItem.value = ''
        }
    }

    const incrementar = (index) => {
        listaCompra.value[index].cantidad++
    }

    const disminuir = (index) => {
        if (listaCompra.value[index].cantidad > 0) {
            listaCompra.value[index].cantidad--
        }
    }

    const borrar = (index) => {
        listaCompra.value.splice(index, 1)
    }

    const totalCarrito = computed(() => {
        return listaCompra.value.reduce((suma, item) => suma + item.cantidad, 0)
    })

</script>

<template>
    <div class="pantalla-completa">
        
        <div class="tarjeta">
            <h1 class="titulo">Lista de la Compra</h1>

            <div class="input-group">
                <input 
                    v-model="nuevoItem" 
                    type="text" 
                    placeholder="Escribir producto..."
                    @keyup.enter="agregar"
                >
                <button class="btn-anadir" @click="agregar">Añadir</button>
            </div>

            <div class="lista-items">
                <ItemCompraView
                    v-for="(producto, index) in listaCompra"
                    :key="producto.id"
                    :nombre="producto.nombre"
                    :comprada="producto.comprada"
                    @cambiar-estado="alternarCompra(index)"
                    @borrar-producto="borrar(index)"
                    @addToCart="addToCart(index)"
                />
            </div>

            <p class="resumen">
                Tienes {{ totalItems }} productos en el carrito.
            </p>

            <div class="carrito">



            </div>

        </div>

    </div>
</template>

<style>
    body, html {
        margin: 0;
        padding: 0;
        background-color: #f0f2f5;
        font-family: 'Segoe UI', sans-serif;
    }
</style>

<style scoped>
    /* El contenedor flexible que centra todo */
    .pantalla-completa {
        min-height: 100vh; /* Altura mínima: 100% de la ventana */
        width: 100%;
        display: flex;
        justify-content: center; /* Centrado Horizontal */
        align-items: center;     /* Centrado Vertical */
    }

    /* Diseño de la tarjeta */
    .tarjeta {
        background-color: white;
        width: 100%;
        max-width: 400px; /* Que no se haga gigante en pantallas grandes */
        padding: 2rem;
        border-radius: 15px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.1); /* Efecto flotante */
        text-align: center;
    }

    .titulo {
        color: #2c3e50;
        margin-top: 0;
        margin-bottom: 40px;
    }

    .input-group {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }

    input {
        flex: 1;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }

    .btn-anadir {
        background-color: #42b883;
        color: white;
        border: none;
        padding: 10px 15px;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
    }

    .lista-items {
        text-align: left; /* Alineamos la lista a la izquierda para leer mejor */
        margin: 20px 0;
    }

    .resumen {
        font-size: 0.9rem;
        color: #7f8c8d;
        border-top: 1px solid #eee;
        padding-top: 15px;
    }
</style>