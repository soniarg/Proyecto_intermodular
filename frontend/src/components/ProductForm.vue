<template>
    <div class="product-form">
        <h3>{{ product ? 'Editar Producto' : 'Nuevo Producto' }}</h3>

        <input v-model="form.title" placeholder="Nombre" />
        <input type="number" step="0.01" v-model="form.price" placeholder="Precio" />
        <input type="number" step="0.1" v-model="form.estimated_weight" placeholder="Peso estimado(kg)" />
        <input type="number" v-model="form.stock" placeholder="Stock" />

        <div class="product-container">
            <label>Producto activo</label>
            <input type="checkbox" v-model="form.is_active" />
        </div>

        <button @click="save">{{ product ? 'Actualizar' : 'Guardar' }}</button>
    </div>
</template>

<script setup>

    import { ref, watch, defineProps, defineEmits } from 'vue';
    import { createProduct, updateProduct } from '@/api/Products';

    const props = defineProps({
        product: {
            type: Object,
            default: null
        }
    });

    const emit = defineEmits(['saved']);

    const form = ref({
        title: '',
        price: '',
        stock: '',
        estimated_weight: '',
        is_active: true
    });

    watch(() => props.product, (val) => {
        if (val) {
            form.value = { ...val };
        } else {
            form.value = { title: '', price: '', stock: '', estimated_weight: '', is_active: true };
        }
    }, { immediate: true });

    const save = async () => {
        try {
            if (props.product) {
                await updateProduct(props.product.id, form.value);
            } else {
                await createProduct(form.value);
            }
            emit('saved');
        } catch (error) {
            console.error("Error al guardar producto:", error);
            alert("Error al conectar con el servidor");
        }
    };
</script>

<style scoped>

.product-form { 
    max-width: 320px;
    margin: 20px auto;
    padding: 2rem;
    border: 1px solid #ddd;
    border-radius: 12px;
}

input { 
    display: block;
    width: 90%;
    margin: 10px auto;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
}

.product-container {
    display: flex;
    align-items: center;
    justify-content: center; 
    gap: 10px;
    margin: 15px 0;
}

.product-container input {
    width: auto;
    margin: 0;
}

button { 
    width: 100%;
    padding: 12px;
    background: #27ae60;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold; 
}

</style>