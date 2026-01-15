<template>
    <div class="pickup-form">
        <h3>{{ pickupPoint ? 'Editar Punto de Entrega' : 'Nuevo Punto de Entrega' }}</h3>

        <input v-model="form.address" placeholder="Dirección" />
        <input type="number" step="0.000001" v-model="form.latitude" placeholder="Latitud" />
        <input type="number" step="0.000001" v-model="form.longitude" placeholder="Longitud" />

        <button @click="save">{{ pickupPoint ? 'Actualizar' : 'Guardar' }}</button>
    </div>
</template>

<script setup>

    import { ref, watch, defineProps, defineEmits } from 'vue';
    import { createPickupPoint, updatePickupPoint } from '@/api/PickupPoints';

    const props = defineProps({
        pickupPoint: {
            type: Object,
            default: null
        }
    });

    const emit = defineEmits(['saved']);

    const form = ref({
        address: '',
        latitude: '',
        longitude: ''
    });

    watch(() => props.pickupPoint, (val) => {
        if (val) {
            form.value = { ...val };
        } else {
            form.value = { address: '', latitude: '', longitude: '' };
        }
    }, { immediate: true });

    const save = async () => {
        try {
            if (props.pickupPoint) {
                await updatePickupPoint(props.pickupPoint.id, form.value);
            } else {
                await createPickupPoint(form.value);
            }
            form.value = { address: '', latitude: '', longitude: '' };
            emit('saved'); 
        } catch (error) {
            console.error("Error al guardar:", error);
            alert("No se pudo guardar el punto de entrega");
        }
    };
</script>

<style scoped>

.pickup-form { 
    max-width: 300px; 
    margin: 20px auto; 
    padding: 2rem;
    border: 1px solid #ccc;
    border-radius: 8px;
    text-align: center; 
}

input { 
    display: block;
    width: 90%;
    margin: 10px auto;
    padding: 8px; 
}

button { 
    width: 95%; 
    padding: 10px; 
    background: #2c3e50; 
    color: white; 
    border: none; 
    border-radius: 4px; 
    cursor: pointer; 
}

</style>