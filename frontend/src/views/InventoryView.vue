<script setup>
import { ref, onMounted, reactive } from 'vue';
import api from '@/api/axios';

const products = ref([]);
const loading = ref(true);
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

// Formulario reactivo
const form = reactive({
    title: '',
    price: '',
    unit: 'kg', 
    stock: '',
    image_file: null // Aquí guardaremos el archivo binario
});

// Variable para la previsualización local de la imagen
const previewImage = ref(null);

// IMPORTANTE: Asegúrate de que esto coincide con tu dominio de Laravel
// Si estás en local suele ser: http://localhost:8000/storage/
const BASE_URL = 'http://localhost:8000/storage/';

onMounted(() => {
    loadProducts();
});

const loadProducts = async () => {
    try {
        const response = await api.get('/seller/my-products');
        products.value = response.data;
    } catch (error) {
        console.error("Error cargando inventario:", error);
    } finally {
        loading.value = false;
    }
};

const openModal = (product = null) => {
    showModal.value = true;
    form.image_file = null; // Reiniciar archivo
    
    if (product) {
        isEditing.value = true;
        editingId.value = product.id;
        form.title = product.title;
        form.price = product.price;
        form.unit = product.unit; 
        form.stock = product.stock;
        
        // Si tiene imagen guardada, mostramos esa URL, si no, null
        previewImage.value = product.image_url ? BASE_URL + product.image_url : null;
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.title = ''; form.price = ''; form.unit = 'kg'; form.stock = ''; 
        previewImage.value = null;
    }
};

// Función para capturar el archivo cuando el usuario lo selecciona
const handleFileUpload = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.image_file = file;
        // Crear una URL local temporal para previsualizar inmediatamente
        previewImage.value = URL.createObjectURL(file);
    }
};

const saveProduct = async () => {
    try {
        // Para enviar archivos, necesitamos FormData
        let formData = new FormData();
        formData.append('title', form.title);
        formData.append('price', form.price);
        formData.append('unit', form.unit);
        formData.append('stock', form.stock);

        // Solo adjuntamos la imagen si el usuario seleccionó una nueva
        if (form.image_file) {
            formData.append('image', form.image_file);
        }

        if (isEditing.value) {
            // TRUCO: Laravel no procesa bien 'multipart/form-data' en peticiones PUT.
            // Solución: Enviar como POST y agregar campo _method = PUT
            formData.append('_method', 'PUT');
            await api.post(`/products/${editingId.value}`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        } else {
            await api.post('/products', formData, {
                headers: { 'Content-Type': 'multipart/form-data' }
            });
        }

        showModal.value = false;
        loadProducts(); 
    } catch (error) {
        console.error(error);
        if (error.response && error.response.status === 422) {
            let errorMsg = "Error de validación:\n";
            const errors = error.response.data.errors;
            for (const key in errors) { errorMsg += `- ${errors[key][0]}\n`; }
            alert(errorMsg);
        } else {
            alert("Error al guardar. Revisa la consola.");
        }
    }
};

const remove = async (id) => {
    if(!confirm("¿Seguro que quieres eliminar este producto?")) return;
    try {
        await api.delete(`/products/${id}`);
        loadProducts();
    } catch (error) {
        console.error(error);
    }
};
</script>

<template>
    <div class="inventory-wrapper">
        <div class="inventory-card">
            <div class="inventory-header">
                <div>
                    <h2>📦 Gestión de Inventario</h2>
                    <p class="subtitle">Administra tus productos en venta</p>
                </div>
                <button @click="openModal()" class="btn-create">
                    <span class="icon">+</span> Nuevo Producto
                </button>
            </div>

            <div v-if="loading" class="loading-state">
                <div class="spinner"></div> Cargando inventario...
            </div>

            <ul v-else-if="products.length > 0" class="product-list">
                <li v-for="p in products" :key="p.id" class="product-item">
                    
                    <div class="product-img-wrapper">
                        <img :src="p.image_url ? BASE_URL + p.image_url : 'public/no-disponible.jpg'" 
                             alt="Producto" class="product-thumb">
                    </div>

                    <div class="product-info">
                        <h4 class="product-title">{{ p.title }}</h4>
                        <div class="product-meta">
                            <span class="badge price">{{ p.price }}€ / {{ p.unit }}</span>
                            <span class="badge stock" :class="{ 'low-stock': p.stock < 5 }">
                                Stock: {{ p.stock }}
                            </span>
                        </div>
                    </div>

                    <div class="product-actions">
                        <button @click="openModal(p)" class="btn-icon edit">Editar</button>
                        <button @click="remove(p.id)" class="btn-icon delete">Eliminar</button>
                    </div>
                </li>
            </ul>
            <div v-else class="empty-state">
                <div class="empty-icon">🌱</div>
                <h3>Tu inventario está vacío</h3>
                <button @click="openModal()" class="btn-create-small">Crear Producto</button>
            </div>
        </div>

        <transition name="fade">
            <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>{{ isEditing ? '✏️ Editar Producto' : '✨ Nuevo Producto' }}</h3>
                        <button @click="showModal = false" class="close-btn">×</button>
                    </div>
                    
                    <form @submit.prevent="saveProduct" class="modal-body">
                        <div class="form-group">
                            <label>Nombre del Producto</label>
                            <input v-model="form.title" type="text" required class="input-field">
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group half">
                                <label>Precio (€)</label>
                                <input v-model="form.price" type="number" step="0.01" required class="input-field">
                            </div>
                            <div class="form-group half">
                                <label>Unidad</label>
                                <select v-model="form.unit" class="input-field">
                                    <option value="kg">Kilogramo (kg)</option>
                                    <option value="unit">Unidad (unit)</option>
                                    <option value="box">Caja (box)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Stock Disponible</label>
                            <input v-model="form.stock" type="number" step="0.001" required class="input-field">
                        </div>

                        <div class="form-group">
                            <label>Imagen del Producto</label>
                            <input type="file" @change="handleFileUpload" accept="image/*" class="input-field file-input">
                            <small class="helper-text">Formatos: JPG, PNG. Máx 2MB</small>
                            
                            <div v-if="previewImage" class="image-preview-box">
                                <img :src="previewImage" alt="Vista previa">
                            </div>
                        </div>

                        <div class="modal-actions">
                            <button type="button" @click="showModal = false" class="btn-cancel">Cancelar</button>
                            <button type="submit" class="btn-save">Guardar Producto</button>
                        </div>
                    </form>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
/* Agrega este estilo extra para el input de tipo archivo si quieres que se vea mejor */
.file-input {
    padding: 6px;
    background: white;
}
/* Mantenemos el resto de estilos igual... */
.inventory-wrapper { display: flex; justify-content: center; padding: 40px 20px; background-color: #f8fafc; min-height: 80vh; font-family: 'Segoe UI', sans-serif; }
.inventory-card { background: white; width: 100%; max-width: 700px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); overflow: hidden; display: flex; flex-direction: column; }
.inventory-header { padding: 25px 30px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: white; }
.inventory-header h2 { margin: 0; color: #1e293b; font-size: 1.5rem; }
.subtitle { margin: 5px 0 0; color: #64748b; font-size: 0.9rem; }
.btn-create { background-color: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2); }
.btn-create:hover { background-color: #059669; transform: translateY(-2px); }
.product-list { list-style: none; padding: 0; margin: 0; }
.product-item { display: flex; align-items: center; padding: 20px 30px; border-bottom: 1px solid #f1f5f9; transition: background 0.1s; }
.product-item:hover { background-color: #f8fafc; }
.product-img-wrapper { width: 60px; height: 60px; border-radius: 10px; overflow: hidden; margin-right: 20px; border: 1px solid #e2e8f0; background: #fff; }
.product-thumb { width: 100%; height: 100%; object-fit: cover; }
.product-info { flex: 1; }
.product-title { margin: 0 0 8px 0; color: #334155; font-size: 1.1rem; }
.product-meta { display: flex; gap: 10px; }
.badge { padding: 4px 10px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
.badge.price { background-color: #eff6ff; color: #3b82f6; }
.badge.stock { background-color: #f1f5f9; color: #64748b; }
.badge.stock.low-stock { background-color: #fef2f2; color: #ef4444; }
.product-actions { display: flex; gap: 10px; }
.btn-icon { padding: 6px 12px; border-radius: 6px; border: none; font-size: 0.9rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
.btn-icon.edit { background-color: #e0f2fe; color: #0284c7; }
.btn-icon.delete { background-color: #fee2e2; color: #dc2626; }
.empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; display: flex; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
.modal-content { background: white; width: 90%; max-width: 450px; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); overflow: hidden; animation: slideUp 0.3s ease-out; }
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.modal-header { padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
.close-btn { background: none; border: none; font-size: 1.5rem; color: #94a3b8; cursor: pointer; }
.modal-body { padding: 25px; }
.form-group { margin-bottom: 18px; }
.form-row { display: flex; gap: 15px; }
.form-group.half { flex: 1; }
label { display: block; margin-bottom: 6px; font-weight: 600; color: #475569; font-size: 0.9rem; }
.input-field { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; color: #1e293b; transition: border-color 0.2s; }
.input-field:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.helper-text { display: block; margin-top: 5px; color: #94a3b8; font-size: 0.8rem; }
.image-preview-box { margin-top: 10px; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; width: 100px; height: 100px; }
.image-preview-box img { width: 100%; height: 100%; object-fit: cover; }
.modal-actions { display: flex; gap: 10px; margin-top: 25px; }
.btn-save { flex: 2; background-color: #3b82f6; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: background 0.2s; }
.btn-cancel { flex: 1; background-color: white; border: 1px solid #cbd5e1; color: #475569; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; }
.loading-state { padding: 40px; text-align: center; color: #64748b; }
.spinner { border: 3px solid #f3f3f3; border-top: 3px solid #3b82f6; border-radius: 50%; width: 24px; height: 24px; animation: spin 1s linear infinite; margin: 0 auto 10px; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>