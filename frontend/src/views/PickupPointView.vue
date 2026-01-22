<script setup>
import { ref, onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router'; // Para el botón de volver
import api from '@/axios';

const router = useRouter();
const points = ref([]);
const loading = ref(true);
const showModal = ref(false);
const isEditing = ref(false);
const editingId = ref(null);

// Formulario (Solo pedimos dirección, la lat/lon la calcula el backend)
const form = reactive({
    address: '',
    city: '',
    postal_code: ''
});

// Mensaje de error específico para el modal
const modalError = ref('');

onMounted(() => {
    loadPoints();
});

const loadPoints = async () => {
    try {
        const response = await api.get('/seller/pickup-points');
        points.value = response.data;
    } catch (error) {
        console.error("Error cargando puntos:", error);
    } finally {
        loading.value = false;
    }
};

const openModal = (point = null) => {
    modalError.value = ''; // Limpiar errores previos
    showModal.value = true;
    
    if (point) {
        isEditing.value = true;
        editingId.value = point.id;
        form.address = point.address;
        form.city = point.city;
        form.postal_code = point.postal_code;
    } else {
        isEditing.value = false;
        editingId.value = null;
        form.address = ''; form.city = ''; form.postal_code = ''; 
    }
};

const savePoint = async () => {
    modalError.value = ''; // Reseteamos error
    try {
        if (isEditing.value) {
            await api.put(`/seller/pickup-points/${editingId.value}`, form);
        } else {
            await api.post('/seller/pickup-points/store', form);
        }

        showModal.value = false;
        loadPoints(); 
        alert(isEditing.value ? "Punto actualizado" : "Punto creado correctamente");

    } catch (error) {
        console.error(error);
        // Manejo de errores de validación (422) o de mapa no encontrado
        if (error.response && error.response.status === 422) {
            // Si el backend devuelve un mensaje específico (ej: "No pudimos localizar...")
            modalError.value = error.response.data.message || "Revisa los datos del formulario.";
        } else {
            alert("Ocurrió un error inesperado.");
        }
    }
};

const remove = async (id) => {
    if(!confirm("¿Seguro que quieres eliminar este punto de recogida?")) return;
    try {
        await api.delete(`/seller/pickup-points/${id}`);
        loadPoints();
    } catch (error) {
        console.error(error);
        alert("No se pudo eliminar el punto.");
    }
};

const goBack = () => {
    router.push('/perfil'); // O la ruta que tengas para el perfil
};
</script>

<template>
    <div class="inventory-wrapper">
        <div class="inventory-card">
            <div class="inventory-header">
                <div class="header-left">
                    <button @click="goBack" class="btn-back">← Volver</button>
                    <div>
                        <h2>📍 Puntos de Recogida</h2>
                        <p class="subtitle">Gestiona dónde entregas tus productos</p>
                    </div>
                </div>
                <button @click="openModal()" class="btn-create">
                    <span class="icon">+</span> Nuevo Punto
                </button>
            </div>

            <div v-if="loading" class="loading-state">
                <div class="spinner"></div> Cargando mapa...
            </div>

            <ul v-else-if="points.length > 0" class="product-list">
                <li v-for="p in points" :key="p.id" class="product-item">
                    
                    <div class="map-icon-wrapper">
                        🗺️
                    </div>

                    <div class="product-info">
                        <h4 class="product-title">{{ p.address }}</h4>
                        <div class="product-meta">
                            <span class="badge city">{{ p.city }}</span>
                            <span class="badge zip">CP: {{ p.postal_code }}</span>
                            
                            <a :href="`https://www.google.com/maps/search/?api=1&query=${p.latitude},${p.longitude}`" 
                               target="_blank" 
                               class="map-link">
                               Ver en mapa ↗
                            </a>
                        </div>
                    </div>

                    <div class="product-actions">
                        <button @click="openModal(p)" class="btn-icon edit">Editar</button>
                        <button @click="remove(p.id)" class="btn-icon delete">Eliminar</button>
                    </div>
                </li>
            </ul>
            
            <div v-else class="empty-state">
                <div class="empty-icon">🌍</div>
                <h3>No tienes puntos de recogida</h3>
                <p>Añade lugares donde los clientes puedan recoger sus pedidos.</p>
                <button @click="openModal()" class="btn-create-small">Añadir Punto</button>
            </div>
        </div>

        <transition name="fade">
            <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>{{ isEditing ? '✏️ Editar Ubicación' : '📍 Nuevo Punto' }}</h3>
                        <button @click="showModal = false" class="close-btn">×</button>
                    </div>
                    
                    <form @submit.prevent="savePoint" class="modal-body">
                        
                        <div v-if="modalError" class="error-box">
                            ⚠️ {{ modalError }}
                        </div>

                        <div class="form-group">
                            <label>Dirección (Calle y Número)</label>
                            <input v-model="form.address" type="text" required placeholder="Ej: Calle Mayor 15" class="input-field">
                            <small class="helper-text">Sé preciso para que el GPS te localice.</small>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group half">
                                <label>Ciudad / Pueblo</label>
                                <input v-model="form.city" type="text" required placeholder="Ej: Gandía" class="input-field">
                            </div>
                            <div class="form-group half">
                                <label>Código Postal</label>
                                <input v-model="form.postal_code" type="text" required placeholder="46701" class="input-field">
                            </div>
                        </div>

                        <div class="modal-actions">
                            <button type="button" @click="showModal = false" class="btn-cancel">Cancelar</button>
                            <button type="submit" class="btn-save">
                                {{ isEditing ? 'Actualizar Mapa' : 'Guardar Ubicación' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </transition>
    </div>
</template>

<style scoped>
/* REUTILIZANDO ESTILOS DE TU INVENTARIO PARA COHERENCIA */
.inventory-wrapper { display: flex; justify-content: center; padding: 40px 20px; background-color: #f8fafc; min-height: 80vh; font-family: 'Segoe UI', sans-serif; }
.inventory-card { background: white; width: 100%; max-width: 700px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.06); overflow: hidden; display: flex; flex-direction: column; }
.inventory-header { padding: 25px 30px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: white; }
.header-left { display: flex; align-items: center; gap: 15px; }
.inventory-header h2 { margin: 0; color: #1e293b; font-size: 1.5rem; }
.subtitle { margin: 5px 0 0; color: #64748b; font-size: 0.9rem; }

.btn-create { background-color: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.2s; box-shadow: 0 4px 6px rgba(59, 130, 246, 0.2); }
.btn-create:hover { background-color: #2563eb; transform: translateY(-2px); }
.btn-back { background: none; border: none; color: #64748b; font-weight: 600; cursor: pointer; font-size: 0.9rem; }
.btn-back:hover { color: #334155; text-decoration: underline; }

.product-list { list-style: none; padding: 0; margin: 0; }
.product-item { display: flex; align-items: center; padding: 20px 30px; border-bottom: 1px solid #f1f5f9; }
.map-icon-wrapper { width: 50px; height: 50px; background: #eff6ff; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-right: 20px; }
.product-info { flex: 1; }
.product-title { margin: 0 0 8px 0; color: #334155; font-size: 1.1rem; }
.product-meta { display: flex; gap: 10px; align-items: center; }

.badge { padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
.badge.city { background-color: #f1f5f9; color: #475569; }
.badge.zip { background-color: #fff7ed; color: #c2410c; }
.map-link { font-size: 0.8rem; color: #3b82f6; text-decoration: none; margin-left: 10px; }
.map-link:hover { text-decoration: underline; }

.product-actions { display: flex; gap: 10px; }
.btn-icon { padding: 6px 12px; border-radius: 6px; border: none; font-size: 0.9rem; font-weight: 600; cursor: pointer; }
.btn-icon.edit { background-color: #e0f2fe; color: #0284c7; }
.btn-icon.delete { background-color: #fee2e2; color: #dc2626; }

.empty-state { text-align: center; padding: 60px 20px; color: #64748b; }
.empty-icon { font-size: 3rem; margin-bottom: 15px; }
.btn-create-small { margin-top: 15px; background: transparent; border: 2px solid #3b82f6; color: #3b82f6; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer; }

/* Modal & Form */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 1000; display: flex; justify-content: center; align-items: center; backdrop-filter: blur(4px); }
.modal-content { background: white; width: 90%; max-width: 450px; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); overflow: hidden; }
.modal-header { padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
.close-btn { background: none; border: none; font-size: 1.5rem; color: #94a3b8; cursor: pointer; }
.modal-body { padding: 25px; }
.form-group { margin-bottom: 18px; }
.form-row { display: flex; gap: 15px; }
.form-group.half { flex: 1; }
label { display: block; margin-bottom: 6px; font-weight: 600; color: #475569; font-size: 0.9rem; }
.input-field { width: 100%; padding: 10px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; color: #1e293b; }
.helper-text { display: block; margin-top: 5px; color: #94a3b8; font-size: 0.8rem; }
.error-box { background-color: #fef2f2; color: #dc2626; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 0.9rem; border: 1px solid #fecaca; }

.modal-actions { display: flex; gap: 10px; margin-top: 25px; }
.btn-save { flex: 2; background-color: #3b82f6; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; }
.btn-cancel { flex: 1; background-color: white; border: 1px solid #cbd5e1; color: #475569; padding: 12px; border-radius: 8px; font-weight: 600; cursor: pointer; }

.loading-state { padding: 40px; text-align: center; color: #64748b; }
.spinner { border: 3px solid #f3f3f3; border-top: 3px solid #3b82f6; border-radius: 50%; width: 24px; height: 24px; animation: spin 1s linear infinite; margin: 0 auto 10px; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>