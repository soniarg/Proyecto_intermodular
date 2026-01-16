<template>
    <div class="inventory-container">
        <h2>Inventario de Productos</h2>
        <button @click="showForm=true">Nuevo Producto</button>

        <ProductForm v-if="showForm" @saved="loadProducts"/>

        <ul>
            <li v-for="p in products" :key="p.product_id">
                {{ p.title }} - Stock: {{ p.stock }}
                <button @click="edit(p)">Editar</button>
                <button @click="remove(p.product_id)">Eliminar</button>
            </li>
        </ul>
    </div>
</template>

<script setup>

    import { ref, onMounted } from 'vue';
    import { getProducts, deleteProduct } from '@/api/Products';
    import ProductForm from '@/components/ProductForm.vue';

    const products = ref([]);
    const showForm = ref(false);

    const loadProducts = async () => {
        products.value = (await getProducts()).data;
    };

    const remove = async (id) => {
        await deleteProduct(id);
        loadProducts();
    }

    onMounted(loadProducts);

</script>

<style scoped>
    
.inventory-container {
    max-width: 600px;
    margin: 20px auto;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background-color: #fafafa;
    font-family: Arial, sans-serif;
}

.inventory-container h2 {
    margin-bottom: 15px;
    color: #333;
    text-align: center;
}

.inventory-container button {
    padding: 6px 12px;
    margin: 5px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    background-color: #4CAF50;
    color: white;
    transition: background-color 0.2s;
}

.inventory-container ul {
    list-style: none;
    padding: 0;
    margin-top: 15px;
}

.inventory-container li {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    margin-bottom: 8px;
    border: 1px solid #eee;
    border-radius: 4px;
    background-color: #fff;
}

.inventory-container li button {
    background-color: #2196F3;
}

.inventory-container li button:last-child {
    background-color: #f44336;
}

</style>