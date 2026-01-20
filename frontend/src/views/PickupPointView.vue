<template>
    <div class="pickup-container">
        <h2>Puntos de Entrega</h2>
        <button @click="openNewForm">Crear Pickup Point</button>

        <PickupPointForm v-if="showForm" :pickupPoint="formPickupPoint || {}" @saved="onSaved"/>

        <ul>
            <li v-for="p in points" :key="p.id">
                {{ p.address }}
                <button @click="editPickupPoint(p)">Editar</button>
                <button @click="removePickupPoint(p.id)">Eliminar</button>
            </li>
        </ul>
    </div>
</template>

<script setup>

import { ref, onMounted } from 'vue';
import { getPickupPoints, deletePickupPoint } from '@/api/PickupPoints';
import PickupPointForm from '@/components/PickupPointForm.vue';

const points = ref([]);

const showForm = ref(false);


const formPickupPoint = ref(null);


const loadPickupPoints = async () => {
    try {
        points.value = await getPickupPoints();
    } catch (error) {
        console.error("Error al cargar los puntos de entrega.", error);
    }
};


const editPickupPoint = (p) => {
    formPickupPoint.value = p;
    showForm.value = true;
};

const openNewForm = () => {
    formPickupPoint.value = null;
    showForm.value = true;
};


const removePickupPoint = async (pickup_id) => {
    try {
        await deletePickupPoint(pickup_id);
        await loadPickupPoints();
    } catch (error) {
        console.error("Error al eliminar el punto de entrega", error);
        alert(error.response?.data?.error || "No se pudo eliminar el punto de entrega");
    }
};


const onSaved = () => {
    loadPickupPoints();
    formPickupPoint.value = null;
    showForm.value = false;
};

onMounted(loadPickupPoints);

</script>


<style scoped>

.pickup-container {
    max-width: 600px;
    margin: 100px auto 20px auto; 
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #fafafa;
    font-family: Arial, sans-serif;
    display: flex;
    flex-direction: column;
    align-items: center; 
}

.pickup-container h2 {
    margin-bottom: 15px;
    color: #333;
    text-align: center;
}

.pickup-container > button {
    display: block;
    margin: 10px auto 20px auto;
    padding: 10px 20px;
    background-color: #4CAF50;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    width: auto;
}

.pickup-form { 
    width: 100%;
    max-width: 300px; 
    margin: 20px auto; 
    padding: 2rem;
    border: 1px solid #ccc;
    border-radius: 8px;
    background-color: white;
    text-align: center;
    box-sizing: border-box; 
}

.pickup-form input { 
    display: block;
    width: 100%;
    margin: 10px 0;
    padding: 8px; 
    box-sizing: border-box;
}

.pickup-form button { 
    width: 100%; 
    padding: 10px; 
    background: #2c3e50;
    color: white; 
    border: none; 
    border-radius: 4px; 
    cursor: pointer; 
}

.pickup-container ul {
    width: 100%;
    list-style: none;
    padding: 0;
    margin-top: 15px;
}

.pickup-container li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    margin-bottom: 8px;
    border: 1px solid #eee;
    border-radius: 4px;
    background-color: #fff;
}

</style>