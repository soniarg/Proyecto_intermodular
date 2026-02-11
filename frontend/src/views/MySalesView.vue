<script setup>
import { ref, onMounted, reactive, computed, watch } from 'vue';
import { useRouter } from 'vue-router'; 
import api from '../api/axios.js'; 
import StarRating from '@/components/StarRating.vue';
import { useToast } from 'vue-toastification';

const router = useRouter();
const toast = useToast();

// --- VARIABLES DE ESTADO ---
const orders = ref([]); 
const activeTab = ref('new');
const loading = ref(false); 
const loadingUser = ref(true); 
const isSeller = ref(false); 

// Variables Modal Edición (AJUSTE DE PESO)
const showEditModal = ref(false);
const editingOrder = ref(null);
const submittingEdit = ref(false);

// Variables Modal Valoración
const showRateModal = ref(false);
const submittingRate = ref(false);
const isEditingReview = ref(false); 
const ratingForm = reactive({
    orderId: null,
    rating: 0,
    comment: ''
});

// --- 1. COMPROBAR SI ES VENDEDOR ---
const checkUserRole = async () => {
    loadingUser.value = true;
    try {
        const response = await api.get('/user');
        const user = response.data;
        
        if (['seller', 'vendedor'].includes(user.role)) {
            isSeller.value = true;
            loadOrders(); 
        } else {
            isSeller.value = false;
        }
    } catch (error) {
        console.error("Error verificando usuario:", error);
    } finally {
        loadingUser.value = false;
    }
};

// --- 2. CARGAR VENTAS (Endpoints de Vendedor) ---
const loadOrders = async () => {
    loading.value = true;
    orders.value = []; 
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
        // La API a veces devuelve { data: [...] } o [...]
        orders.value = response.data.data || response.data || [];

    } catch (error) {
        console.error("Error cargando ventas:", error);
    } finally {
        loading.value = false;
    }
};

watch(activeTab, () => {
    if (isSeller.value) loadOrders();
});

onMounted(() => {
    checkUserRole();
});

const goToChat = (orderId) => router.push(`/chat/${orderId}`);
const goToProfile = () => router.push('/perfil');

// --- ACCIONES DE GESTIÓN (VENDEDOR) ---

const acceptOrder = async (orderId) => {
    if(!confirm("¿Aceptar pedido? Pasará a 'Pendiente'.")) return;
    try {
        await api.put(`/seller/orders/${orderId}/mark-pending`);
        loadOrders();
        toast.success("✅ Pedido aceptado.");
    } catch (error) {
        toast.error("Error: ");
    }
};

const markAsReady = async (orderId) => {
    if(!confirm("¿El pedido está preparado para recoger?")) return;
    try {
        await api.put(`/seller/orders/${orderId}/mark-ready`);
        loadOrders();
        toast.success("✅ Pedido marcado como LISTO.");
    } catch (error) {
        toast.error("Error: ");
    }
};

const markAsCompleted = async (orderId) => {
    if(!confirm("¿Confirmas que has entregado el pedido al cliente?")) return;
    try {
        await api.put(`/seller/orders/${orderId}/mark-completed`);
        loadOrders();
        toast.success("🎉 ¡Pedido entregado y completado!");
    } catch (error) {
        toast.error("Error: ");
    }
};

const rejectOrder = async (orderId) => {
    const reason = prompt("Indica el motivo del rechazo:");
    if (!reason) return;

    try {
        await api.put(`/seller/orders/${orderId}/reject`, { rejection_reason: reason });
        loadOrders();
        toast.success("✅ Pedido rechazado.");
    } catch (error) {
        toast.error("Error: ");
    }
};

// --- LÓGICA DE AJUSTE DE PESO (RECUPERADA) ---
const openEditModal = (order) => {
    const orderCopy = JSON.parse(JSON.stringify(order));
    // Preparamos los campos para editar
    orderCopy.lines = orderCopy.lines.map(line => {
        let calculatedPrice = 0;
        if(line.unit === 'kg' && line.estimated_weight > 0) {
            calculatedPrice = line.line_price / line.estimated_weight;
        } else if (line.quantity > 0) {
            calculatedPrice = line.line_price / line.quantity;
        }
        return {
            ...line,
            // Si ya tiene peso real usamos ese, si no, el estimado
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
        toast.success("✅ Pedido actualizado correctamente.");
        showEditModal.value = false;
        loadOrders();
    } catch (error) {
        console.error(error);
        toast.error("Error al actualizar.Revisa los datos.");
    } finally {
        submittingEdit.value = false;
    }
};

const closeEditModal = () => {
    showEditModal.value = false;
    editingOrder.value = null;
};

// --- LÓGICA VALORACIÓN (VENDEDOR -> COMPRADOR) ---
const openRateModal = (order) => {
    ratingForm.orderId = order.id;
    if (order.local_review) {
        ratingForm.rating = order.local_review.rating;
        ratingForm.comment = order.local_review.comment;
        isEditingReview.value = true; 
    } else {
        ratingForm.rating = 0;
        ratingForm.comment = '';
        isEditingReview.value = false;
    }
    showRateModal.value = true;
};

const submitRating = async () => {
    if (ratingForm.rating === 0) {
        toast.error("Por favor, selecciona al menos una estrella.");
        return;
    }
    submittingRate.value = true;
    try {
        const payload = {
            rating: ratingForm.rating,
            comment: ratingForm.comment
        };

        if (isEditingReview.value) {
            await api.put(`/orders/${ratingForm.orderId}/reviews`, payload);
            toast.success("¡Valoración actualizada correctamente!");
        } else {
            await api.post(`/orders/${ratingForm.orderId}/reviews`, payload);
            toast.success("¡Valoración enviada! Gracias.");
        }

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
        toast.error("Error: ");
    } finally {
        submittingRate.value = false;
    }
};
</script>

<template>
  <div class="sales-container">
    
    <div class="header-row">
        <h2 class="page-title">Mis Ventas</h2>
        <div style="width: 80px;"></div> 
    </div>

    <div v-if="loadingUser" class="loading-state">
        <div class="spinner"></div>
        <p>Verificando cuenta...</p>
    </div>

    <div v-else-if="!isSeller" class="not-seller-container">
        <div class="not-seller-box">
            <div class="icon"></div>
            <h3>Aún no eres vendedor</h3>
            <p>Para ver este panel y gestionar ventas, necesitas configurar tu tienda.</p>
            <button @click="goToProfile" class="btn btn-primary-lg">Ir a mi perfil y empezar a vender</button>
        </div>
    </div>

    <div v-else>
        <div class="tabs">
            <button :class="{ active: activeTab === 'new' }" @click="activeTab = 'new'">
                Nuevos <span v-if="activeTab === 'new' && orders.length" class="badge">{{ orders.length }}</span>
            </button>
            <button :class="{ active: activeTab === 'pending' }" @click="activeTab = 'pending'">Pendientes</button>
            <button :class="{ active: activeTab === 'adjusted' }" @click="activeTab = 'adjusted'">Ajustados</button>
            <button :class="{ active: activeTab === 'ready' }" @click="activeTab = 'ready'">Listos</button>
            <button :class="{ active: activeTab === 'history' }" @click="activeTab = 'history'">Historial</button>
        </div>

        <div v-if="loading" class="loading-state">
            <div class="spinner"></div>
            <p>Cargando ventas...</p>
        </div>

        <div v-else class="orders-list">
            <div v-if="orders.length === 0" class="empty-state">
                <p>No tienes ventas en esta sección.</p>
            </div>

            <div v-for="order in orders" :key="order.id" class="order-card">
                
                <div class="order-header">
                    <span class="order-id">Pedido #{{ order.id }}</span>
                    <span :class="['status-badge', order.status]">
                        {{ order.status === 'new' ? 'NUEVO PEDIDO' : order.status === 'pending' ? 'PENDIENTE' : order.status === 'weight_adjusted' ? 'PRECIO AJUSTADO' : order.status === 'ready' ? 'LISTO PARA RECOGER' : order.status }}
                    </span>
                </div>

                <div class="order-body">
                    <div class="info-row">
                        <span><strong>Comprador:</strong> 👤 {{ order.buyer_name || 'Usuario' }}</span>
                        <span><strong>Total:</strong> {{ order.total_price }}€</span>
                    </div>

                    <div v-if="(order.status === 'rejected' || order.status === 'cancelled') && order.rejection_reason" class="reason-alert">
                        ⚠️ <strong>Motivo:</strong> {{ order.rejection_reason }}
                    </div>
                    
                    <ul class="product-list">
                        <li v-for="(line, index) in order.lines" :key="index">
                            {{ line.name }} 
                            <span class="qty">x{{ line.quantity }} {{ line.unit }}</span>
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
                        Chat
                    </button>

                    <router-link :to="`/seller/orders/${order.id}`" class="btn btn-details">
                        Detalles
                    </router-link>

                    <button v-if="activeTab === 'new'" 
                            @click="acceptOrder(order.id)" 
                            class="btn btn-accept">
                        Aceptar
                    </button>

                    <template v-if="activeTab === 'pending' || activeTab === 'adjusted'">
                        <button @click="openEditModal(order)" class="btn btn-edit">
                             Ajustar Peso
                        </button>
                        <button @click="markAsReady(order.id)" class="btn btn-ready">
                             Listo
                        </button>
                    </template>

                    <button v-if="activeTab === 'ready'" 
                            @click="markAsCompleted(order.id)" 
                            class="btn btn-completed">
                         Entregado
                    </button>

                    <button v-if="!['history', 'rejected', 'cancelled'].includes(activeTab)" 
                            @click="rejectOrder(order.id)" 
                            class="btn btn-reject">
                        Rechazar
                    </button>

                    <button v-if="order.status === 'completed'" 
                            @click="openRateModal(order)"
                            class="btn"
                            :class="order.local_review ? 'btn-rated' : 'btn-rate'">
                        {{ order.local_review ? 'Ver valoración' : 'Valorar Comprador' }}
                    </button>
                </div>
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
                        {{ submittingEdit ? 'Guardando...' : 'Guardar Cambios' }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div v-if="showRateModal" class="modal-overlay">
        <div class="modal-content rate-modal">
            <h3>{{ isEditingReview ? '✏️ Editar Valoración' : '⭐ Valorar al Comprador' }}</h3>
            <div class="rating-form-container">
                <div class="stars-selection">
                    <label>Puntuación:</label>
                    <StarRating v-model:rating="ratingForm.rating" />
                </div>
                <div class="comment-selection">
                    <label>Comentario:</label>
                    <textarea v-model="ratingForm.comment" rows="4" class="input-control textarea-control" placeholder="Ej: Comprador puntual..."></textarea>
                </div>
                <div class="modal-actions-bar">
                    <button @click="showRateModal = false" class="btn btn-details">Cancelar</button>
                    <button @click="submitRating" class="btn btn-rate" :disabled="submittingRate">
                        {{ submittingRate ? 'Guardando...' : (isEditingReview ? '🔄 Actualizar' : 'Enviar Valoración') }}
                    </button>
                </div>
            </div>
        </div>
    </div>

  </div>
</template>

<style scoped>
/* ESTILOS UNIFICADOS */
.sales-container { max-width: 800px; margin: 0 auto; padding: 20px; font-family: 'Segoe UI', sans-serif; }
.header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.page-title { margin: 0; color: #2c3e50; text-align: center; flex-grow: 1; }
.btn-home { background: #f8f9fa; border: 1px solid #ddd; padding: 8px 15px; border-radius: 8px; cursor: pointer; }
.tabs { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; }
.tabs button { background: none; border: none; padding: 10px 15px; cursor: pointer; font-weight: 600; color: #7f8c8d; border-radius: 8px; white-space: nowrap; transition: color 0.2s; }
.tabs button.active { background-color: #e3f2fd; color: #1565c0; border-radius: 8px; }
.tabs button:hover:not(.active) { background-color: #f5f5f5; }
.badge { background: #ff5252; color: white; padding: 2px 8px; border-radius: 12px; font-size: 0.75em; margin-left: 6px; }

/* Estilos Estado "No Vendedor" */
.not-seller-container { display: flex; justify-content: center; padding: 40px 0; }
.not-seller-box { background: white; border-radius: 16px; padding: 40px; text-align: center; max-width: 400px; box-shadow: 0 4px 20px rgba(0,0,0,0.05); border: 1px solid #eee; }
.not-seller-box .icon { font-size: 4rem; margin-bottom: 20px; }
.not-seller-box h3 { color: #2c3e50; margin-bottom: 10px; }
.not-seller-box p { color: #7f8c8d; margin-bottom: 25px; }
.btn-primary-lg { background-color: #3b82f6; color: white; padding: 12px 24px; border-radius: 8px; font-size: 1rem; border: none; cursor: pointer; font-weight: bold; transition: background 0.2s; }
.btn-primary-lg:hover { background-color: #2563eb; }

/* Tarjetas */
.order-card { background: white; border: 1px solid #e0e0e0; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
.order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0; font-weight: bold; }
.order-id { font-size: 1.1em; color: #333; }
.status-badge { padding: 5px 12px; border-radius: 12px; font-size: 0.8em; text-transform: uppercase; letter-spacing: 0.5px; }
.status-badge.new { background: #e3f2fd; color: #1565c0; } 
.status-badge.pending { background: #fff3e0; color: #ef6c00; } 
.status-badge.weight_adjusted { background: #e8f5e9; color: #2e7d32; } 
.status-badge.ready { background: #00897b; color: white; } 
.status-badge.completed { background: #f1f8e9; color: #33691e; } 
.status-badge.rejected, .status-badge.cancelled { background: #ffebee; color: #c62828; }
.info-row { display: flex; justify-content: space-between; color: #666; font-size: 0.9em; margin-bottom: 10px; }
.reason-alert { background: #fff5f5; color: #c53030; padding: 10px; border-radius: 4px; font-size: 0.9em; margin-bottom: 10px; border-left: 3px solid #ef5350; }
.total-row { font-size: 1.1em; color: #2c3e50; margin: 10px 0; display: flex; justify-content: space-between; border-top: 1px dashed #eee; padding-top: 10px; }
.product-list { list-style: none; padding: 0; background: #fafafa; padding: 10px; border-radius: 5px; margin-top: 10px; }
.product-list li { margin-bottom: 5px; color: #555; display: flex; align-items: center; gap: 5px; }
.qty { color: #888; font-size: 0.9em; }
.real-weight-badge { font-size: 0.8em; color: #2e7d32; font-weight: bold; background: #e8f5e9; padding: 0 4px; border-radius: 4px; }

/* Acciones */
.order-actions { display: flex; justify-content: flex-end; gap: 10px; flex-wrap: wrap; margin-top: 15px; border-top: 1px solid #f0f0f0; padding-top: 15px; }
.btn { border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: background 0.2s; font-size: 0.9em; }
.btn-chat { background: #3490dc; color: white; }
.btn-chat:hover { background: #2779bd; }
.btn-details { background: #f0f2f5; color: #333; text-decoration: none; display: inline-flex; align-items: center; }
.btn-details:hover { background: #e4e6eb; }
.btn-accept { background: #4caf50; color: white; }
.btn-accept:hover { background: #43a047; }
.btn-reject { background: #fff; border: 1px solid #ef5350; color: #ef5350; }
.btn-reject:hover { background: #ffebee; }
.btn-edit { background: #ff9800; color: white; }
.btn-edit:hover { background: #fb8c00; }
.btn-ready { background: #00897b; color: white; }
.btn-ready:hover { background: #00796b; }
.btn-completed { background: #2e7d32; color: white; }
.btn-completed:hover { background: #1b5e20; }
.btn-rate { background: #8e44ad; color: white; border: 1px solid #732d91; }
.btn-rate:hover { background: #9b59b6; }
.btn-rated { background: #ffffff; color: #27ae60; border: 1px solid #27ae60; }
.btn-rated:hover { background: #eafaf1; }

.loading-state, .empty-state { text-align: center; padding: 40px; color: #999; font-style: italic; }
.spinner { border: 4px solid #f3f3f3; border-top: 4px solid #3498db; border-radius: 50%; width: 30px; height: 30px; animation: spin 1s linear infinite; margin: 0 auto 10px auto; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

/* Modales */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; justify-content: center; align-items: center; backdrop-filter: blur(2px); }
.modal-content { background: white; padding: 30px; border-radius: 12px; width: 95%; max-width: 600px; max-height: 90vh; overflow-y: auto; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
.rate-modal { max-width: 500px; text-align: center; }
.stars-selection { margin: 20px 0; display: flex; flex-direction: column; align-items: center; gap: 10px; }
.stars-selection label { font-weight: bold; color: #333; }
.stars-selection :deep(.star) { font-size: 2.5rem; }
.comment-selection { text-align: left; margin-bottom: 20px; }
.comment-selection label { display: block; margin-bottom: 8px; font-weight: bold; color: #555; }
.input-control.textarea-control { width: 100%; padding: 10px; border: 1px solid #cbd5e0; border-radius: 6px; resize: vertical; min-height: 80px; font-family: inherit; }
.line-edit-row { border-bottom: 1px solid #eee; padding: 15px 0; display: flex; gap: 15px; }
.line-info { flex: 1; }
.line-inputs { flex: 1; display: flex; flex-direction: column; gap: 10px; }
.input-control { padding: 8px; border: 1px solid #ddd; border-radius: 5px; width: 100%; }
.badge-unit { background: #eee; padding: 2px 6px; border-radius: 4px; font-size: 0.8em; }
.modal-actions-bar { margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px; }
.modal-subtitle { color: #666; margin-bottom: 20px; font-size: 0.95em; }
</style>