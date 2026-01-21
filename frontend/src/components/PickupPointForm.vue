<template>
    <div class="pickup-form">

        <input v-model="form.address" placeholder="Dirección" />
        <input type="number" step="0.000001" v-model="form.latitude" placeholder="Latitud" />
        <input type="number" step="0.000001" v-model="form.longitude" placeholder="Longitud" />

        <button class="save-button" @click="save">{{ pickupPoint && pickupPoint.id ? 'Actualizar' : 'Crear' }}</button>
        <button type="button" class="cancel-button" @click="cancel">Cancelar</button>
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

    const emit = defineEmits(['saved','cancel']);

    const form = ref({
        address: '',
        latitude: '',
        longitude: ''
    });

    watch(() => props.pickupPoint, (val) => {
        if (val) {
            form.value = {
                address: val.address || '',
                latitude: val.latitude || '',
                longitude: val.longitude || ''
            };
        } else {
            form.value = { address: '', latitude: '', longitude: '' };
        }
    }, { immediate: true });

    const save = async () => {
        try {
            const payload = {
                ...form.value 
            };

            if (props.pickupPoint && props.pickupPoint.id) {
                await updatePickupPoint(props.pickupPoint.id, payload);
            } else {
                await createPickupPoint(payload);
            }
            form.value = { address: '', latitude: '', longitude: '' };
            emit('saved'); 
        } catch (error) {
            console.error("Error al guardar:", error);
            alert(error.response?.data?.error || "No se pudo guardar el punto de entrega");
        }
    };

    const cancel = () => {
        emit('cancel');
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

.form-buttons {
    display: flex;
    justify-content: center;
    gap: 10px;
    margin-top: 10px;
}

button {
    padding: 10px 20px;
    border-radius: 4px;
    border: none;
    cursor: pointer;
    color: white;
}

.save-button {
    background-color: blue;
}

.cancel-button {
    background-color: red;
    margin-left: 20px;
}

</style>