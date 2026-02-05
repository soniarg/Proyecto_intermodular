<script setup>
import { ref, onMounted, reactive, computed } from 'vue';
import { useRouter } from 'vue-router'; 
import api from '../api/axios.js'; 
import StarRating from '@/components/StarRating.vue';

const router = useRouter();

// --- VARIABLES DE ESTADO ---
const newOrders = ref([]);
const pendingOrders = ref([]);
const adjustedOrders = ref([]);
const readyOrders = ref([]);
const historyOrders = ref([]);

const activeTab = ref('new');
const loading = ref(false);

// Variables Modal Edición
const showEditModal = ref(false);
const editingOrder = ref(null);
const submittingEdit = ref(false);

// 👇 VARIABLES PARA VALORACIÓN (Adaptadas)
const showRateModal = ref(false);
const submittingRate = ref(false);
const isViewingLocalReview = ref(false); // Para saber si es modo lectura
const ratingForm = reactive({
    orderId: null,
    rating: 0,
    comment: ''
});

// Computada para saber qué lista mostrar
const currentList = computed(() => {
    switch(activeTab.value) {
        case 'new': return newOrders.value;
        case 'pending': return pendingOrders.value;
        case 'adjusted': return adjustedOrders.value;
        case 'ready': return readyOrders.value;
        case 'history': return historyOrders.value;
        default: return [];
    }
});

// --- CARGA DE DATOS ---
const loadOrders = async () => {
    loading.value = true;
    try {
        let endpoint = '';
        switch (activeTab.value) {
            case 'new': endpoint = '/seller/orders/new'; break;
            case 'pending': endpoint = '/seller/orders/pending'; break;
            case 'adjusted': endpoint = '/seller/orders/adjusted'; break;
            case 'ready': endpoint = '/seller/orders/ready'; break;
            case 'history': endpoint = '/seller/orders/history'; break;
        }

        const response = await api.get(endpoint);
        
        // Asignación de datos
        if (activeTab.value === 'new') newOrders.value = response.data;
        else if (activeTab.value === 'pending') pendingOrders.value = response.data;
        else if (activeTab.value === 'adjusted') adjustedOrders.value = response.data;
        else if (activeTab.value === 'ready') readyOrders.value = response.data;
        else if (activeTab.value === 'history') historyOrders.value = response.data;

    } catch (error) {
        console.error("Error cargando pedidos:", error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    loadOrders();
});

const setTab = (tabName) => {
    activeTab.value = tabName;
    loadOrders();
};

// --- ACCIONES DE ESTADO ---

const acceptOrder = async (orderId) => {
    if(!confirm("¿Aceptar pedido? Pasará a 'Pendiente'.")) return;
    try {
        await api.put(`/seller/orders/${orderId}/mark-pending`);
        loadOrders();
        alert("✅ Pedido aceptado.");
    } catch (error) {
        alert("Error: " + (error.response?.data?.message || error.message));
    }
};

const markAsReady = async (orderId) => {
    if(!confirm("¿El pedido está preparado para recoger?")) return;
    try {
        await api.put(`/seller/orders/${orderId}/mark-ready`);
        loadOrders();
        alert("✅ Pedido marcado como LISTO.");
    } catch (error) {
        alert("Error: " + (error.response?.data?.message || error.message));
    }
};

const markAsCompleted = async (orderId) => {
    if(!confirm("¿Confirmas que has entregado el pedido al cliente?")) return;
    try {
        await api.put(`/seller/orders/${orderId}/mark-completed`);
        loadOrders();
        alert("🎉 ¡Pedido entregado y completado!");
    } catch (error) {
        alert("Error: " + (error.response?.data?.message || error.message));
    }
};

const rejectOrder = async (orderId) => {
    const reason = prompt("Indica el motivo del rechazo:");
    if (!reason) return;

    try {
        await api.put(`/seller/orders/${orderId}/reject`, { rejection_reason: reason });
        loadOrders();
        alert("✅ Pedido rechazado.");
    } catch (error) {
        alert("Error: " + (error.response?.data?.message || error.message));
    }
};

// --- LÓGICA DEL MODAL DE EDICIÓN ---
const openEditModal = (order) => {
    const orderCopy = JSON.parse(JSON.stringify(order));
    orderCopy.lines = orderCopy.lines.map(line => {
        let calculatedPrice = 0;
        if(line.unit === 'kg' && line.estimated_weight > 0) {
            calculatedPrice = line.line_price / line.estimated_weight;
        } else if (line.quantity > 0) {
            calculatedPrice = line.line_price / line.quantity;
        }
        return {
            ...line,
            edit_real_weight: line.real_weight > 0 ? line.real_weight : line.estimated_weight,
            edit_quantity: line.quantity,
            edit_unit_price: calculatedPrice.toFixed(2)
        };
    });
    editingOrder.value = orderCopy;
    showEditModal.value = true;
};

const saveOrderChanges = async () => {
    if (!editingOrder.value) return;
    submittingEdit.value = true;
    try {
        const payload = {
            lines: editingOrder.value.lines.map(line => ({
                id: line.id,
                real_weight: parseFloat(line.edit_real_weight),
                quantity: parseInt(line.edit_quantity),
                unit_price: parseFloat(line.edit_unit_price)
            }))
        };
        await api.put(`/seller/orders/${editingOrder.value.id}/update`, payload);
        alert("✅ Pedido actualizado correctamente.");
        showEditModal.value = false;
        loadOrders();
    } catch (error) {
        console.error(error);
        alert("Error al actualizar: " + (error.response?.data?.message || "Revisa los datos."));
    } finally {
        submittingEdit.value = false;
    }
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingOrder.value = null;
};

// 👇 LÓGICA DE VALORACIÓN (MODIFICADA PARA SIMULACIÓN) ---

// Ahora recibimos el OBJETO order completo, no solo el ID
const openRateModal = (order) => {
    // 1. Reseteamos formulario
    ratingForm.orderId = order.id;
    ratingForm.rating = 0;
    ratingForm.comment = '';
    isViewingLocalReview.value = false;

    // 2. Comprobamos si tenemos una reseña local (hecha en esta sesión)
    if (order.local_review) {
        ratingForm.rating = order.local_review.rating;
        ratingForm.comment = order.local_review.comment;
        isViewingLocalReview.value = true; // Activamos modo lectura
    }

    showRateModal.value = true;
};

const submitRating = async () => {
    // Si estamos en modo lectura, el botón solo cierra el modal
    if (isViewingLocalReview.value) {
        showRateModal.value = false;
        return;
    }

    if (ratingForm.rating === 0) {
        alert("Por favor, selecciona al menos una estrella.");
        return;
    }

    submittingRate.value = true;
    try {
        // Intentamos enviar al backend
        await api.post(`/orders/${ratingForm.orderId}/reviews`, {
            rating: ratingForm.rating,
            comment: ratingForm.comment
        });

        alert("¡Valoración enviada! Gracias.");

        // 👇 TRUCO FRONTEND: Guardamos la reseña en el objeto del pedido localmente
        const orderIndex = currentList.value.findIndex(o => o.id === ratingForm.orderId);
        if (orderIndex !== -1) {
            currentList.value[orderIndex].local_review = {
                rating: ratingForm.rating,
                comment: ratingForm.comment
            };
        }

        showRateModal.value = false;

    } catch (error) {
        console.error(error);
        if (error.response && error.response.status === 409) {
            alert("⚠️ Ya has valorado este pedido anteriormente.\n(No se puede editar sin cambios en el backend, no hay PUT).");
            
            // Opcional: Marcamos visualmente que ya está hecho
            const orderIndex = currentList.value.findIndex(o => o.id === ratingForm.orderId);
            if (orderIndex !== -1) {
                // Creamos un fake review para que el botón cambie a verde
                currentList.value[orderIndex].local_review = { rating: 0, comment: 'Ya valorado' }; 
            }
            showRateModal.value = false;
        } else {
            alert("Error al enviar valoración: " + (error.response?.data?.message || error.message));
        }
    } finally {
        submittingRate.value = false;
    }
};

</script>

<template>
  <div class="seller-orders-container">
    
    <div class="header-row">
        <button @click="router.push('/')" class="btn-home">
            🏠 Inicio
        </button>
        <h2 class="page-title">📦 Gestión de Pedidos</h2>
        <div style="width: 80px;"></div> 
    </div>

    <div class="tabs">
        <button :class="{ active: activeTab === 'new' }" @click="setTab('new')">
            Nuevos <span v-if="newOrders.length" class="badge">{{ newOrders.length }}</span>
        </button>
        <button :class="{ active: activeTab === 'pending' }" @click="setTab('pending')">
            Pendientes
        </button>
        <button :class="{ active: activeTab === 'adjusted' }" @click="setTab('adjusted')">
            Ajustados
        </button>
        <button :class="{ active: activeTab === 'ready' }" @click="setTab('ready')">
            Listos
        </button>
        <button :class="{ active: activeTab === 'history' }" @click="setTab('history')">
            Historial
        </button>
    </div>

    <div class="orders-list-wrapper">
        
        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p>Cargando pedidos...</p>
        </div>

        <div v-else>
            <div v-for="order in currentList" :key="order.id" class="order-card">
                
                <div class="order-header">
                    <span class="order-id">#{{ order.id }}</span>
                    <span class="order-buyer">👤 {{ order.buyer_name }}</span>
                    <span class="order-status" :class="order.status">{{ order.status }}</span>
                </div>

                <div class="order-body">
                    <div v-if="(order.status === 'rejected' || order.status === 'cancelled') && order.rejection_reason" class="reason-alert">
                        ⚠️ <strong>Motivo:</strong> {{ order.rejection_reason }}
                    </div>

                    <p class="order-total">Total: <strong>{{ order.total_price }}€</strong></p>
                    
                    <ul class="order-lines-preview">
                        <li v-for="(line, index) in order.lines.slice(0, 2)" :key="index">
                           {{ line.quantity }}x {{ line.name }} <span class="text-muted">({{ line.unit }})</span>
                        </li>
                        <li v-if="order.lines.length > 2" class="more-lines">
                            ... y {{ order.lines.length - 2 }} más
                        </li>
                    </ul>
                </div>

                <div class="order-actions">
                    <router-link :to="`/seller/orders/${order.id}`" class="btn btn-details">
                        👁️ Detalles
                    </router-link>

                    <button v-if="order.status === 'completed'" 
                            @click="openRateModal(order)" 
                            class="btn"
                            :class="order.local_review ? 'btn-rated' : 'btn-rate'">
                        {{ order.local_review ? '✅ Ver Valoración' : '⭐ Valorar Usuario' }}
                    </button>

                    <button v-if="activeTab === 'new'" 
                            @click="acceptOrder(order.id)" 
                            class="btn btn-accept">
                        ✅ Aceptar Pedido
                    </button>
                    
                    <template v-if="activeTab === 'pending' || activeTab === 'adjusted'">
                        <button @click="openEditModal(order)" class="btn btn-edit">
                            ✏️ Editar / Ajustar
                        </button>

                        <button @click="markAsReady(order.id)" class="btn btn-ready">
                            📦 Listo para recoger
                        </button>
                    </template>

                    <button v-if="activeTab === 'ready'" 
                            @click="markAsCompleted(order.id)" 
                            class="btn btn-completed">
                        🤝 Entregado
                    </button>

                    <button v-if="!['history', 'rejected', 'cancelled'].includes(activeTab)" 
                            @click="rejectOrder(order.id)" 
                            class="btn btn-reject">
                        ❌ Rechazar
                    </button>
                </div>
            </div>

            <div v-if="currentList.length === 0" class="empty-state">
                <p>No hay pedidos en esta sección.</p>
            </div>
        </div>
    </div>

    <div v-if="showEditModal" class="modal-overlay">
        <div class="modal-content">
            <h3>📝 Ajustar Pedido #{{ editingOrder.id }}</h3>
            <p class="modal-subtitle">Introduce peso real, cantidad o corrige el precio.</p>
            <form @submit.prevent="saveOrderChanges" class="edit-form">
                <div v-for="line in editingOrder.lines" :key="line.id" class="line-edit-row">
                    <div class="line-info">
                        <strong>{{ line.name }}</strong>
                        <span class="badge-unit">{{ line.unit }}</span>
                        <div class="original-price"><small>Total orig: {{ line.line_price }}€</small></div>
                    </div>
                    <div class="line-inputs">
                        <div v-if="line.unit === 'kg'">
                            <label>Peso Real (kg):</label>
                            <input type="number" step="0.01" v-model="line.edit_real_weight" class="input-control">
                            <small class="text-muted">Est: {{ line.estimated_weight }}kg</small>
                        </div>
                        <div v-else>
                            <label>Cantidad:</label>
                            <input type="number" step="1" v-model="line.edit_quantity" class="input-control">
                            <div style="margin-top:5px">
                                <label style="font-size:0.8em">Peso Paquete (kg):</label>
                                <input type="number" step="0.01" v-model="line.edit_real_weight" class="input-control small">
                            </div>
                        </div>
                        <div class="price-correction-box">
                            <label>Precio Unitario (€):</label>
                            <div class="input-group">
                                <input type="number" step="0.01" v-model="line.edit_unit_price" class="input-control small">
                                <span>€ / {{ line.unit }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-actions-bar">
                    <button type="button" @click="closeEditModal" class="btn btn-details">Cancelar</button>
                    <button type="submit" class="btn btn-accept" :disabled="submittingEdit">
                        {{ submittingEdit ? 'Guardando...' : '💾 Guardar Cambios' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div v-if="showRateModal" class="modal-overlay">
        <div class="modal-content rate-modal">
            <h3>
                {{ isViewingLocalReview ? '✅ Valoración Enviada' : '⭐ Valorar al Comprador' }}
            </h3>
            
            <p class="modal-subtitle">
                {{ isViewingLocalReview ? 'Estos son los datos que has enviado en esta sesión.' : '¿Qué tal fue la experiencia con este cliente?' }}
            </p>

            <div class="rating-form-container">
                <div class="stars-selection">
                    <label>Puntuación:</label>
                    <StarRating v-model:rating="ratingForm.rating" :readOnly="isViewingLocalReview" />
                </div>

                <div class="comment-selection">
                    <label>Comentario:</label>
                    <textarea 
                        v-model="ratingForm.comment" 
                        rows="4" 
                        class="input-control textarea-control"
                        :disabled="isViewingLocalReview"
                        placeholder="Ej: Cliente puntual y amable..."
                    ></textarea>
                </div>

                <div class="modal-actions-bar">
                    <button @click="showRateModal = false" class="btn btn-details">
                        {{ isViewingLocalReview ? 'Cerrar' : 'Cancelar' }}
                    </button>
                    
                    <button v-if="!isViewingLocalReview" 
                            @click="submitRating" 
                            class="btn btn-rate" 
                            :disabled="submittingRate">
                        {{ submittingRate ? 'Enviando...' : 'Enviar Valoración' }}
                    </button>
                </div>
                
                <p v-if="isViewingLocalReview" style="font-size: 0.8rem; color: #999; margin-top: 10px;">
                    * Para editar, recarga la página (si el servidor lo permite).
                </p>
            </div>
        </div>
    </div>

  </div>
</template>

<style scoped>
/* Contenedor principal */
.seller-orders-container { max-width: 900px; margin: 0 auto; padding: 20px; font-family: 'Segoe UI', sans-serif; }

/* Cabecera Flexible */
.header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.page-title { margin-bottom: 0; text-align: center; flex-grow: 1; color: #2c3e50; }

/* Botón Home */
.btn-home { background-color: #f8f9fa; border: 1px solid #ddd; color: #555; padding: 8px 15px; border-radius: 8px; cursor: pointer; font-weight: bold; display: flex; align-items: center; gap: 5px; transition: all 0.2s; }
.btn-home:hover { background-color: #e9ecef; color: #333; transform: translateX(-3px); }

/* Tabs */
.tabs { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; }
.tabs button { background: none; border: none; padding: 10px 20px; cursor: pointer; font-weight: 600; color: #7f8c8d; border-radius: 8px; white-space: nowrap; transition: all 0.2s; }
.tabs button.active { background-color: #e3f2fd; color: #1976d2; }
.tabs button:hover:not(.active) { background-color: #f5f5f5; }
.badge { background: #ff5252; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75em; margin-left: 6px; vertical-align: middle; }

/* Lista y Tarjetas */
.order-card { background: white; border: 1px solid #e0e0e0; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: transform 0.2s; }
.order-card:hover { transform: translateY(-2px); box-shadow: 0 6px 12px rgba(0,0,0,0.05); }

.order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; }
.order-id { font-weight: bold; color: #333; font-size: 1.1em; }
.order-status { text-transform: uppercase; font-size: 0.75em; padding: 5px 10px; border-radius: 4px; font-weight: 800; letter-spacing: 0.5px; }

/* Colores de Estado */
.order-status.new { background: #e3f2fd; color: #1565c0; }
.order-status.pending { background: #fff3e0; color: #ef6c00; }
.order-status.weight_adjusted { background: #e8f5e9; color: #2e7d32; }
.order-status.ready { background: #e0f7fa; color: #006064; }
.order-status.completed { background: #f5f5f5; color: #616161; }
.order-status.rejected, .order-status.cancelled { background: #ffebee; color: #c62828; }

.reason-alert { background: #fff5f5; border-left: 4px solid #fc8181; color: #c53030; padding: 10px; margin-bottom: 15px; border-radius: 4px; font-size: 0.9em; }

.order-lines-preview { list-style: none; padding: 0; margin: 10px 0; color: #555; }
.order-total { font-size: 1.1em; margin-bottom: 5px; color: #2c3e50; }
.text-muted { color: #999; font-size: 0.9em; }

/* Botones */
.order-actions { display: flex; gap: 10px; justify-content: flex-end; flex-wrap: wrap; margin-top: 15px; }
.btn { padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.9em; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; transition: background 0.2s; }

.btn-details { background: #f0f2f5; color: #333; }
.btn-details:hover { background: #e4e6eb; }

.btn-accept { background: #4caf50; color: white; }
.btn-accept:hover { background: #43a047; }

.btn-edit { background: #ff9800; color: white; }
.btn-edit:hover { background: #fb8c00; }

.btn-ready { background: #00897b; color: white; }
.btn-ready:hover { background: #00796b; }

.btn-completed { background: #2e7d32; color: white; }
.btn-completed:hover { background: #1b5e20; }

.btn-reject { background: #ef5350; color: white; }
.btn-reject:hover { background: #e53935; }

.btn:disabled { opacity: 0.6; cursor: not-allowed; }

/* Estados de carga/vacío */
.loading-state, .empty-state { text-align: center; padding: 40px; color: #7f8c8d; font-style: italic; background: #f9f9f9; border-radius: 8px; }
.spinner { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto 10px auto; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

/* === MODAL ESTILOS === */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; justify-content: center; align-items: center; backdrop-filter: blur(2px); }
.modal-content { background: white; padding: 30px; border-radius: 12px; width: 95%; max-width: 650px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
.modal-subtitle { color: #666; margin-bottom: 20px; font-size: 0.95em; }

.line-edit-row { border-bottom: 1px solid #eee; padding: 20px 0; display: flex; gap: 20px; align-items: flex-start; }
.line-edit-row:last-child { border-bottom: none; }
.line-info { flex: 1; }
.badge-unit { background: #edf2f7; font-size: 0.75em; padding: 2px 6px; border-radius: 4px; color: #4a5568; margin-left: 5px; }

.line-inputs { flex: 1.2; display: flex; flex-direction: column; gap: 12px; }
.input-control { padding: 8px 12px; border: 1px solid #cbd5e0; border-radius: 6px; width: 100%; font-size: 1em; }
.input-control.textarea-control { resize: vertical; min-height: 80px; font-family: inherit; }
.input-control:focus { border-color: #4299e1; outline: none; box-shadow: 0 0 0 3px rgba(66, 153, 225, 0.1); }
.input-control.small { padding: 6px; font-size: 0.9em; }

/* Caja de corrección de precio */
.price-correction-box { background: #fdf6e3; padding: 10px; border-radius: 6px; border: 1px dashed #d6ceba; }
.price-correction-box label { display: block; font-size: 0.8em; color: #8a6d3b; margin-bottom: 4px; font-weight: bold; }
.input-group { display: flex; align-items: center; gap: 8px; }
.input-group span { font-size: 0.85em; color: #666; }

.modal-actions-bar { margin-top: 30px; display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #eee; padding-top: 20px; }

.btn-rate { background: #8e44ad; color: white; border: 1px solid #732d91; }
.btn-rate:hover { background: #9b59b6; transform: translateY(-1px); box-shadow: 0 2px 5px rgba(0,0,0,0.1); }

/* 👇 Estilo nuevo para el botón "Valorado" */
.btn-rated {
    background: #ffffff;
    color: #27ae60;
    border: 1px solid #27ae60;
}
.btn-rated:hover {
    background: #eafaf1;
}

/* Estilos específicos del Modal de Valoración */
.rate-modal { max-width: 500px; text-align: center; }
.stars-selection { margin: 20px 0; display: flex; flex-direction: column; align-items: center; gap: 10px; }
.stars-selection label { font-weight: bold; color: #333; }
.stars-selection :deep(.star) { font-size: 2.5rem; } 

.comment-selection { text-align: left; margin-bottom: 20px; }
.comment-selection label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
</style>