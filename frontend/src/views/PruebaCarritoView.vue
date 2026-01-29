<script setup>
    import { storeToRefs } from 'pinia';
    // import { useCarrito } from '@/views/composables/useCarrito';
    import { useCarritoStore } from '@/stores/carrito';

    const store = useCarritoStore();

    const { carrito, totalCarrito } = storeToRefs(store);
    const { eliminar, eliminarTodo } = store;
</script>

<template>
    <h2>Carrito</h2>
    <div v-if="carrito.length > 0">
        <h3>Lista de Juegos</h3>
        <ul>
            <li v-for="juego in carrito" :key="juego.id">
                Nombre: {{ juego.name }} | Precio: {{ juego.cost }} | Cantidad: {{ juego.cantidad }} | Subtotal: {{ juego.cost * juego.cantidad }}€
            <button @click="eliminar(juego)">Eliminar</button>
            </li>
        </ul>
        <button @click="eliminarTodo">Vaciar carrito</button>
    </div>

    <div v-else>
        <p>No se ha añadido aún ningún juego</p>
    </div>
    
    <p :class="{
        'caro' : totalCarrito > 100,
        'barato' : totalCarrito <= 100
    }">Total: {{ totalCarrito }}€</p>
</template>

<style scoped>
.caro{
    color: red;
    font-weight: bold;
}

.barato {
    color: green;
    font-weight: bold;
}
</style>