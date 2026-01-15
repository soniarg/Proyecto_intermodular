<template>
    <div class="pickup-container">
        <h2>Puntos de Entrega</h2>
        <button @click="showForm=true">Nuevo Punto de Entrega</button>

        <PickupPointForm v-if="showForm" @saved="loadPickupPoints"/>

        <ul>
            <li v-for="p in points" :key="p.pickup_id">
                {{ p.address }}
                <button @click="edit(p)">Editar</button>
                <button @click="remove(p.pickup_id)">Eliminar</button>
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

    const loadPoints= async () => {
        points.value = (await getPickupPoints()).data;
    };

    const remove = async (id) => {
        await deletePickupPoint(id);
        await loadPoints();
    }

    onMounted(loadPoints);

</script>

<style scoped>

.pickup-container {
    max-width: 600px;
    margin: 20px auto;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #fafafa;
    font-family: Arial, sans-serif;
}

.pickup-container h2 {
    margin-bottom: 15px;
    color: #333;
    text-align: center;
}

.pickup-container button {
    padding: 6px 12px;
    margin: 5px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    background-color: #4CAF50;
    color: white;
    transition: background-color 0.2s;
}

.pickup-container ul {
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

.pickup-container li button {
    background-color: #2196F3;
}

.pickup-container li button:last-child {
    background-color: #f44336;
}

</style>