<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api/axios.js'; 

const router = useRouter();
const orders = ref([]);
const loading = ref(true);
const activeTab = ref('new'); // Pestaña activa por defecto

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
        case 'new':
            // Estado 'new': Acaba de pedir, esperando que el vendedor diga "Sí".
            return orders.value.filter(o => o.status === 'new');
        case 'processing':
            // Estados 'pending' (Aceptado/Pesando) y 'weight_adjusted' (Ya pesado/Precio final)
            return orders.value.filter(o => ['pending', 'weight_adjusted'].includes(o.status));
        case 'ready':
            // Estado 'ready': Listo para recoger
            return orders.value.filter(o => o.status === 'ready');
        case 'history':
            // Estados finales
            return orders.value.filter(o => ['completed', 'rejected', 'cancelled'].includes(o.status));
        default:
            return [];
    }
});

// --- ACCIONES ---

const goToChat = (orderId) => {
    router.push(`/chat/${orderId}`);
};

// CANCELAR PEDIDO
// Solo permitido en estado 'new'. Si ya pasó a 'pending', el botón no se mostrará.
const cancelOrder = async (orderId) => {
    const reason = prompt("¿Por qué deseas cancelar el pedido? (Mínimo 5 letras)");
    
    if (!reason || reason.length < 5) {
        if(reason) alert("El motivo debe ser más detallado.");
        return;
    }

    try {
        await api.put(`/user/cancel/${orderId}`, { rejection_reason: reason });
        
        // Recargamos datos
        const response = await api.get('/my-orders');
        orders.value = response.data;
        
        alert("✅ Pedido cancelado correctamente.");
    } catch (error) {
        console.error(error);
        alert("Error al cancelar: " + (error.response?.data?.message || "Inténtalo de nuevo."));
    }
};

// Formateador de fecha
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
        <button :class="{ active: activeTab === 'new' }" @click="activeTab = 'new'">
            ⏳ Por Aceptar
        </button>
        
        <button :class="{ active: activeTab === 'processing' }" @click="activeTab = 'processing'">
            ⚖️ En Preparación
        </button>
        
        <button :class="{ active: activeTab === 'ready' }" @click="activeTab = 'ready'">
            📦 Listos
        </button>
        
        <button :class="{ active: activeTab === 'history' }" @click="activeTab = 'history'">
            📜 Historial
        </button>
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
                    {{ 
                        order.status === 'new' ? 'Esperando confirmación' : 
                        order.status === 'pending' ? 'Siendo pesado...' : 
                        order.status === 'weight_adjusted' ? 'Precio Final Ajustado' : 
                        order.status 
                    }}
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
            </div>
        </div>
    </div>
  </div>
</template>

<style scoped>
.purchases-container { max-width: 800px; margin: 0 auto; padding: 20px; font-family: 'Segoe UI', sans-serif; }

/* Header */
.header-row { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
.page-title { margin: 0; color: #2c3e50; text-align: center; flex-grow: 1; }
.btn-home { background: #f8f9fa; border: 1px solid #ddd; padding: 8px 15px; border-radius: 8px; cursor: pointer; }

/* Tabs */
.tabs { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 20px; border-bottom: 2px solid #f0f0f0; }
.tabs button { background: none; border: none; padding: 10px 15px; cursor: pointer; font-weight: 600; color: #7f8c8d; border-radius: 8px; white-space: nowrap; transition: color 0.2s; }
.tabs button.active { background-color: #e3f2fd; color: #1565c0; border-radius: 8px; }
.tabs button:hover:not(.active) { background-color: #f5f5f5; }

/* Tarjetas */
.order-card { background: white; border: 1px solid #e0e0e0; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
.order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 1px solid #f0f0f0; font-weight: bold; }

/* Badges de Estado */
.status-badge { padding: 5px 12px; border-radius: 12px; font-size: 0.8em; text-transform: uppercase; letter-spacing: 0.5px; }
.status-badge.new { background: #e3f2fd; color: #1565c0; } /* Azul: Esperando */
.status-badge.pending { background: #fff3e0; color: #ef6c00; } /* Naranja: En proceso */
.status-badge.weight_adjusted { background: #e8f5e9; color: #2e7d32; } /* Verde suave: Ajustado */
.status-badge.ready { background: #00897b; color: white; } /* Verde azulado: Listo */
.status-badge.completed { background: #f1f8e9; color: #33691e; } /* Verde oscuro: Historial */
.status-badge.rejected, .status-badge.cancelled { background: #ffebee; color: #c62828; } /* Rojo */

/* Detalles */
.info-row { display: flex; justify-content: space-between; color: #666; font-size: 0.9em; margin-bottom: 10px; }
.total-row { font-size: 1.1em; color: #2c3e50; margin: 10px 0; }
.product-list { list-style: none; padding: 0; background: #fafafa; padding: 10px; border-radius: 5px; }
.product-list li { margin-bottom: 5px; color: #555; display: flex; align-items: center; gap: 5px; }
.qty { color: #888; font-size: 0.9em; }
.real-weight-badge { font-size: 0.8em; color: #2e7d32; font-weight: bold; background: #e8f5e9; padding: 0 4px; border-radius: 4px; }
.reason-alert { background: #fff5f5; color: #c53030; padding: 10px; border-radius: 4px; font-size: 0.9em; margin-bottom: 10px; border-left: 3px solid #ef5350; }

/* Botones */
.order-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px; border-top: 1px solid #f0f0f0; padding-top: 15px; }
.btn { border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold; transition: background 0.2s; }
.btn-chat { background: #3490dc; color: white; }
.btn-chat:hover { background: #2779bd; }
.btn-cancel { background: #fff; border: 1px solid #ef5350; color: #ef5350; }
.btn-cancel:hover { background: #ffebee; }

.loading-state, .empty-state { text-align: center; padding: 40px; color: #999; font-style: italic; }
</style>