<script setup>
    defineProps(['nombre', 'cantidad'])
    defineEmits(['incrementar', 'disminuir', 'borrar'])
</script>

<template>

    <div class="item">

        <div class="info-producto"
            :class="{ 'en-carrito': cantidad > 0}"
            @click="$emit('incrementar')"
        >
            {{ cantidad > 0 ? '✅' : ' - ' }}
            {{ nombre }}
        </div>

        <div class="acciones">

            <div v-if="cantidad > 0" class="controles-cantidad">
                <button class="btn-cantidad" @click="$emit('disminuir')">-</button>
                <span class="numero">{{ cantidad }}</span>
                <button class="btn-cantidad" @click="$emit('incrementar')">+</button>
            </div>

            <button class="btn-borrar" @click="$emit('borrar')">🗑️</button>

        </div>

    </div>

</template>

<style scoped>
    .item {
        border-bottom: 1px solid #eee;
        padding: 15px 10px;
        display: flex;
        justify-content: space-between; /* Separa nombre de los botones */
        align-items: center;
        transition: background 0.3s;
    }

    .info-producto {
        cursor: pointer;
        flex-grow: 1; /* Ocupa todo el espacio posible a la izquierda */
        font-size: 1.1rem;
    }

    .en-carrito {
        color: #42b883; /* Verde Vue */
        font-weight: bold;
    }

    .acciones {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .controles-cantidad {
        background-color: #f8f9fa;
        border-radius: 20px;
        padding: 2px 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .btn-cantidad {
        background: white;
        border: 1px solid #ddd;
        width: 25px;
        height: 25px;
        border-radius: 50%;
        cursor: pointer;
        font-weight: bold;
        color: #2c3e50;
    }
    
    .btn-cantidad:hover {
        background-color: #eee;
    }

    .numero {
        font-weight: bold;
        min-width: 15px;
        text-align: center;
    }

    .btn-borrar {
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1.2rem;
        opacity: 0.5;
        transition: opacity 0.2s;
    }

    .btn-borrar:hover {
        opacity: 1;
        color: red;
    }
</style>