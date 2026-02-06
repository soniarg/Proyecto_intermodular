<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api/axios.js'; 
import StarRating from '@/components/StarRating.vue';

const router = useRouter();
const orders = ref([]);
const loading = ref(true);
const activeTab = ref('new');

// VARIABLES PARA VALORACIÓN (Modal y Formulario)
const showRateModal = ref(false);
const submittingRate = ref(false);
const isViewingLocalReview = ref(false); 
const ratingForm = reactive({
    orderId: null,
    rating: 0,
    comment: ''
});

// --- CARGA DE DATOS ---
onMounted(async () => {
  try {
    const response = await api.get('/my-orders');
    orders.value = response.data;
  } catch (error) {
    console.error("Error al cargar pedidos:", error);
  } finally {
    loading.value = false;
  }
});

// --- COMPUTED: FILTRADO POR PESTAÑAS ---
const filteredOrders = computed(() => {
    switch (activeTab.value) {
        case 'new': return orders.value.filter(o => o.status === 'new');
        case 'processing': return orders.value.filter(o => ['pending', 'weight_adjusted'].includes(o.status));
        case 'ready': return orders.value.filter(o => o.status === 'ready');
        case 'history': return orders.value.filter(o => ['completed', 'rejected', 'cancelled'].includes(o.status));
        default: return [];
    }
});

// --- ACCIONES ---
const goToChat = (orderId) => router.push(`/chat/${orderId}`);

const cancelOrder = async (orderId) => {
    const reason = prompt("¿Por qué deseas cancelar el pedido? (Mínimo 5 letras)");
    if (!reason || reason.length < 5) {
        if(reason) alert("El motivo debe ser más detallado.");
        return;
    }
    try {
        await api.put(`/user/cancel/${orderId}`, { rejection_reason: reason });
        const response = await api.get('/my-orders');
        orders.value = response.data;
        alert("✅ Pedido cancelado correctamente.");
    } catch (error) {
        console.error(error);
        alert("Error al cancelar: " + (error.response?.data?.message || "Inténtalo de nuevo."));
    }
};

// LÓGICA DE VALORACIÓN (Frontend Simulation)
const openRateModal = (order) => {
    ratingForm.orderId = order.id;
    ratingForm.rating = 0;
    ratingForm.comment = '';
    isViewingLocalReview.value = false;

    if (order.local_review) {
        ratingForm.rating = order.local_review.rating;
        ratingForm.comment = order.local_review.comment;
        isViewingLocalReview.value = true;
    }
    showRateModal.value = true;
};

const submitRating = async () => {
    if (isViewingLocalReview.value) { showRateModal.value = false; return; }
    if (ratingForm.rating === 0) { alert("Por favor, selecciona al menos una estrella."); return; }

    submittingRate.value = true;
    try {
        await api.post(`/orders/${ratingForm.orderId}/reviews`, {
            rating: ratingForm.rating,
            comment: ratingForm.comment
        });
        alert("¡Valoración enviada! Gracias por tu opinión.");

        const orderIndex = orders.value.findIndex(o => o.id === ratingForm.orderId);
        if (orderIndex !== -1) {
            orders.value[orderIndex].local_review = {
                rating: ratingForm.rating,
                comment: ratingForm.comment
            };
        }
        showRateModal.value = false;
    } catch (error) {
        console.error(error);
        if (error.response && error.response.status === 409) {
            alert("⚠️ Ya habías valorado este pedido anteriormente.");
            const orderIndex = orders.value.findIndex(o => o.id === ratingForm.orderId);
            if (orderIndex !== -1) {
                orders.value[orderIndex].local_review = { rating: 0, comment: 'Ya valorado' };
            }
            showRateModal.value = false;
        } else {
            alert("Error: " + (error.response?.data?.message || error.message));
        }
    } finally {
        submittingRate.value = false;
    }
};

const formatDate = (dateString) => {
    if(!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-ES', { 
        day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute:'2-digit' 
    });
};
</script>

<template>
  <div class="purchases-container">
    
    <div class="header-row">
        <button @click="router.push('/')" class="btn-home">🏠 Inicio</button>
        <h2 class="page-title">🛍️ Mis Compras</h2>
        <div style="width: 80px;"></div>
    </div>

    <div class="tabs">
        <button :class="{ active: activeTab === 'new' }" @click="activeTab = 'new'">⏳ Por Aceptar</button>
        <button :class="{ active: activeTab === 'processing' }" @click="activeTab = 'processing'">⚖️ En Preparación</button>
        <button :class="{ active: activeTab === 'ready' }" @click="activeTab = 'ready'">📦 Listos</button>
        <button :class="{ active: activeTab === 'history' }" @click="activeTab = 'history'">📜 Historial</button>
    </div>

    <div v-if="loading" class="loading-state">Cargando tus compras...</div>

    <div v-else class="orders-list">
        <div v-if="filteredOrders.length === 0" class="empty-state">
            <p v-if="activeTab === 'new'">No tienes pedidos pendientes de aceptación.</p>
            <p v-else-if="activeTab === 'processing'">No tienes pedidos en preparación.</p>
            <p v-else>No hay pedidos en esta sección.</p>
        </div>

        <div v-for="order in filteredOrders" :key="order.id" class="order-card">
            
            <div class="order-header">
                <span class="order-id">Pedido #{{ order.id }}</span>
                <span :class="['status-badge', order.status]">
                    {{ order.status === 'new' ? 'Esperando confirmación' : order.status === 'pending' ? 'Siendo pesado...' : order.status === 'weight_adjusted' ? 'Precio Final Ajustado' : order.status }}
                </span>
            </div>

            <div class="order-body">
                <div class="info-row">
                    <span><strong>Vendedor:</strong> {{ order.seller?.name || 'Vendedor Local' }}</span>
                    <span><strong>Fecha:</strong> {{ formatDate(order.created_at) }}</span>
                </div>
                
                <div v-if="order.rejection_reason && (order.status === 'rejected' || order.status === 'cancelled')" class="reason-alert">
                     ⚠️ <strong>Motivo cancelación:</strong> {{ order.rejection_reason }}
                </div>

                <div class="total-row">
                    <span v-if="['new', 'pending'].includes(order.status)">Total estimado:</span>
                    <span v-else>Total final:</span>
                    <strong>{{ order.total_price }}€</strong>
                </div>

                <ul class="product-list">
                    <li v-for="line in order.lines" :key="line.id">
                        {{ line.product?.title }} 
                        <span class="qty">x{{ line.quantity }} {{ line.product?.unit }}</span>
                        <span v-if="line.real_weight && line.real_weight > 0" class="real-weight-badge">
                             (Real: {{ line.real_weight }}kg)
                        </span>
                    </li>
                </ul>
            </div>

            <div class="order-actions">
                <button v-if="activeTab !== 'history'" 
                    @click="goToChat(order.id)" 
                    class="btn btn-chat">
                    💬 Chat
                </button>

                <button v-if="order.status === 'new'" 
                        @click="cancelOrder(order.id)" 
                        class="btn btn-cancel">
                    ❌ Cancelar
                </button>

                <button v-if="order.status === 'completed'" 
                        @click="openRateModal(order)"
                        class="btn"
                        :class="order.local_review ? 'btn-rated' : 'btn-rate'">
                    {{ order.local_review ? '✅ Ver Valoración' : '⭐ Valorar Pedido' }}
                </button>
            </div>
        </div>
    </div>

    <div v-if="showRateModal" class="modal-overlay">
        <div class="modal-content rate-modal">
            <h3>{{ isViewingLocalReview ? '✅ Valoración Enviada' : '⭐ Valorar al Vendedor' }}</h3>
            <p class="modal-subtitle">{{ isViewingLocalReview ? 'Tu opinión registrada:' : '¿Qué tal llegó el producto?' }}</p>

            <div class="rating-form-container">
                <div class="stars-selection">
                    <label>Puntuación:</label>
                    <StarRating v-model:rating="ratingForm.rating" :readOnly="isViewingLocalReview" />
                </div>

                <div class="comment-selection">
                    <label>Comentario:</label>
                    <textarea v-model="ratingForm.comment" rows="4" class="input-control textarea-control" :disabled="isViewingLocalReview" placeholder="Ej: Productos muy frescos y buen trato..."></textarea>
                </div>

                <div class="modal-actions-bar">
                    <button @click="showRateModal = false" class="btn btn-details">
                        {{ isViewingLocalReview ? 'Cerrar' : 'Cancelar' }}
                    </button>
                    <button v-if="!isViewingLocalReview" @click="submitRating" class="btn btn-rate" :disabled="submittingRate">
                        {{ submittingRate ? 'Enviando...' : 'Enviar Valoración' }}
                    </button>
                </div>
            </div>
        </div>
    </div>

  </div>
</template>

<style scoped>
/* Tus estilos base */
.purchases-container { max-width: 800px; margin: 0 auto; padding: 20px; font-family: 'Segoe UI', sans-serif; }
.header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.page-title { margin: 0; color: #2c3e50; text-align: center; flex-grow: 1; }
.btn-home { background: #f8f9fa; border: 1px solid #ddd; padding: 8px 15px; border-radius: 8px; cursor: pointer; }
.tabs { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; }
.tabs button { background: none; border: none; padding: 10px 15px; cursor: pointer; font-weight: 600; color: #7f8c8d; border-radius: 8px; white-space: nowrap; transition: color 0.2s; }
.tabs button.active { background-color: #e3f2fd; color: #1565c0; border-radius: 8px; }
.tabs button:hover:not(.active) { background-color: #f5f5f5; }
.order-card { background: white; border: 1px solid #e0e0e0; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
.order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0; font-weight: bold; }
.status-badge { padding: 5px 12px; border-radius: 12px; font-size: 0.8em; text-transform: uppercase; letter-spacing: 0.5px; }
.status-badge.new { background: #e3f2fd; color: #1565c0; } 
.status-badge.pending { background: #fff3e0; color: #ef6c00; } 
.status-badge.weight_adjusted { background: #e8f5e9; color: #2e7d32; } 
.status-badge.ready { background: #00897b; color: white; } 
.status-badge.completed { background: #f1f8e9; color: #33691e; } 
.status-badge.rejected, .status-badge.cancelled { background: #ffebee; color: #c62828; }
.info-row { display: flex; justify-content: space-between; color: #666; font-size: 0.9em; margin-bottom: 10px; }
.total-row { font-size: 1.1em; color: #2c3e50; margin: 10px 0; }
.product-list { list-style: none; padding: 0; background: #fafafa; padding: 10px; border-radius: 5px; }
.product-list li { margin-bottom: 5px; color: #555; display: flex; align-items: center; gap: 5px; }
.qty { color: #888; font-size: 0.9em; }
.real-weight-badge { font-size: 0.8em; color: #2e7d32; font-weight: bold; background: #e8f5e9; padding: 0 4px; border-radius: 4px; }
.reason-alert { background: #fff5f5; color: #c53030; padding: 10px; border-radius: 4px; font-size: 0.9em; margin-bottom: 10px; border-left: 3px solid #ef5350; }

/* 👇 ESTILOS DE BOTONES (Alineación derecha) */
.order-actions { 
    display: flex; 
    justify-content: flex-end; /* Alinea todo a la derecha */
    gap: 10px; 
    margin-top: 15px; 
    border-top: 1px solid #f0f0f0; 
    padding-top: 15px; 
}

.btn { border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: background 0.2s; }
.btn-chat { background: #3490dc; color: white; }
.btn-chat:hover { background: #2779bd; }
.btn-cancel { background: #fff; border: 1px solid #ef5350; color: #ef5350; }
.btn-cancel:hover { background: #ffebee; }
.loading-state, .empty-state { text-align: center; padding: 40px; color: #999; font-style: italic; }

/* Modal Valoración */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; justify-content: center; align-items: center; backdrop-filter: blur(2px); }
.modal-content { background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px; text-align: center; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
.modal-subtitle { color: #666; margin-bottom: 20px; font-size: 0.95em; }
.rate-modal { max-width: 500px; text-align: center; }
.stars-selection { margin: 20px 0; display: flex; flex-direction: column; align-items: center; gap: 10px; }
.stars-selection label { font-weight: bold; color: #333; }
.stars-selection :deep(.star) { font-size: 2.5rem; }
.comment-selection { text-align: left; margin-bottom: 20px; }
.comment-selection label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
.input-control.textarea-control { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; resize: vertical; min-height: 80px; font-family: inherit; }
.modal-actions-bar { margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; }
.btn-details { background: #f0f2f5; color: #333; }

/* Botones de valoración */
.btn-rate { background: #8e44ad; color: white; border: 1px solid #732d91; }
.btn-rate:hover { background: #9b59b6; transform: translateY(-1px); }
.btn-rated { background: #ffffff; color: #27ae60; border: 1px solid #27ae60; }
.btn-rated:hover { background: #eafaf1; }
</style>