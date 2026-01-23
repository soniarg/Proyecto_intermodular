<script setup>
import { ref, onMounted } from 'vue';
import api from '../axios.js'; // Asegúrate de importar tu instancia de axios configurada

// Variables reactivas para almacenar los pedidos por estado
const newOrders = ref([]);
const pendingOrders = ref([]);
const adjustedOrders = ref([]);
const readyOrders = ref([]);
const historyOrders = ref([]);

// Variable para controlar la pestaña activa (por defecto 'new')
const activeTab = ref('new');
const loading = ref(false);

// Función para cargar los pedidos según la pestaña activa
const loadOrders = async () => {
    loading.value = true;
    try {
        // Llamada dinámica según la pestaña activa
        // Mapeamos el nombre de la pestaña a la ruta de la API
        let endpoint = '';
        switch (activeTab.value) {
            case 'new': endpoint = '/seller/orders/new'; break;
            case 'pending': endpoint = '/seller/orders/pending'; break;
            case 'adjusted': endpoint = '/seller/orders/adjusted'; break;
            case 'ready': endpoint = '/seller/orders/ready'; break;
            case 'history': endpoint = '/seller/orders/history'; break;
        }

        const response = await api.get(endpoint);
        
        // Asignamos la respuesta a la variable correspondiente
        if (activeTab.value === 'new') newOrders.value = response.data;
        else if (activeTab.value === 'pending') pendingOrders.value = response.data;
        else if (activeTab.value === 'adjusted') adjustedOrders.value = response.data;
        else if (activeTab.value === 'ready') readyOrders.value = response.data;
        else if (activeTab.value === 'history') historyOrders.value = response.data;

    } catch (error) {
        console.error("Error cargando pedidos:", error);
        alert("Error al cargar los pedidos. Revisa la consola.");
    } finally {
        loading.value = false;
    }
};

// Cargar pedidos al montar el componente
onMounted(() => {
    loadOrders();
});

// Función para cambiar de pestaña
const setTab = (tabName) => {
    activeTab.value = tabName;
    loadOrders(); // Recargamos los datos al cambiar de pestaña
};

// Funciones para acciones rápidas (ej: Aceptar pedido)
const acceptOrder = async (orderId) => {
    if(!confirm("¿Aceptar este pedido? Pasará a estado Pendiente.")) return;
    try {
        await api.put(`/seller/orders/${orderId}/mark-pending`);
        // Recargar la lista actual para que desaparezca de 'new'
        loadOrders();
        alert("Pedido aceptado correctamente.");
    } catch (error) {
        console.error(error);
        alert("Error al aceptar el pedido.");
    }
};

// Función para rechazar/cancelar (se usará en varias pestañas)
const rejectOrder = async (orderId) => {
    const reason = prompt("Indica el motivo del rechazo:");
    if (!reason) return;

    try {
        await api.put(`/seller/orders/${orderId}/cancel-reject`, { rejection_reason: reason });
        loadOrders();
        alert("Pedido rechazado correctamente.");
    } catch (error) {
        console.error(error);
        alert("Error al rechazar el pedido.");
    }
};

// ... Puedes añadir aquí más funciones para markAsReady, markAsCompleted, etc.

</script>

<template>
  <div class="seller-orders-container">
    <h2 class="page-title">📦 Gestión de Pedidos</h2>

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
        <div v-if="loading" class="loading-state">Cargando pedidos...</div>

        <div v-else>
            <div v-for="order in (activeTab === 'new' ? newOrders : 
                                  activeTab === 'pending' ? pendingOrders : 
                                  activeTab === 'adjusted' ? adjustedOrders : 
                                  activeTab === 'ready' ? readyOrders : historyOrders)" 
                 :key="order.id" 
                 class="order-card">
                
                <div class="order-header">
                    <span class="order-id">#{{ order.id }}</span>
                    <span class="order-buyer">👤 {{ order.buyer_name }}</span>
                    <span class="order-status" :class="order.status">{{ order.status }}</span>
                </div>

                <div class="order-body">
                    <p class="order-total">Total estimado: <strong>{{ order.total_price }}€</strong></p>
                    
                    <ul class="order-lines-preview">
                        <li v-for="(line, index) in order.lines.slice(0, 2)" :key="index">
                            {{ line.quantity }}x {{ line.name }} 
                            <span class="text-muted">({{ line.unit }})</span>
                        </li>
                        <li v-if="order.lines.length > 2" class="more-lines">
                            ... y {{ order.lines.length - 2 }} más
                        </li>
                    </ul>
                </div>

                <div class="order-actions">
                    <router-link :to="`/seller/orders/${order.id}`" class="btn btn-details">
                        Ver Detalles
                    </router-link>

                    <button v-if="activeTab === 'new'" @click="acceptOrder(order.id)" class="btn btn-accept">
                        ✅ Aceptar
                    </button>
                    
                    <button v-if="activeTab !== 'history'" @click="rejectOrder(order.id)" class="btn btn-reject">
                        ❌ Rechazar
                    </button>
                </div>
            </div>

            <div v-if="(activeTab === 'new' ? newOrders : 
                        activeTab === 'pending' ? pendingOrders : 
                        activeTab === 'adjusted' ? adjustedOrders : 
                        activeTab === 'ready' ? readyOrders : historyOrders).length === 0" 
                 class="empty-state">
                <p>No hay pedidos en esta sección.</p>
            </div>
        </div>
    </div>
  </div>
</template>

<style scoped>
.seller-orders-container { max-width: 800px; margin: 0 auto; padding: 20px; }
.page-title { text-align: center; color: #2c3e50; margin-bottom: 20px; }

/* Tabs */
.tabs { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 20px; border-bottom: 1px solid #eee; }
.tabs button {
    background: none; border: none; padding: 10px 15px; cursor: pointer;
    font-weight: bold; color: #666; border-radius: 5px; white-space: nowrap;
}
.tabs button.active { background-color: #e3f2fd; color: #1976d2; }
.tabs button:hover:not(.active) { background-color: #f5f5f5; }
.badge { background: #ff5252; color: white; padding: 2px 6px; border-radius: 10px; font-size: 0.8em; margin-left: 5px; }

/* Tarjetas de Pedido */
.order-card {
    background: white; border: 1px solid #e0e0e0; border-radius: 8px;
    padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}
.order-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; border-bottom: 1px solid #f0f0f0; padding-bottom: 8px; }
.order-id { font-weight: bold; color: #333; }
.order-status { text-transform: uppercase; font-size: 0.8em; padding: 4px 8px; border-radius: 4px; font-weight: bold; }

/* Colores de estado */
.order-status.new { background: #e3f2fd; color: #1976d2; }
.order-status.pending { background: #fff3e0; color: #f57c00; }
.order-status.weight_adjusted { background: #e8f5e9; color: #388e3c; }
.order-status.ready { background: #e0f7fa; color: #006064; }
.order-status.completed { background: #eeeeee; color: #616161; }
.order-status.rejected, .order-status.cancelled { background: #ffebee; color: #c62828; }

.order-body { margin-bottom: 15px; }
.order-lines-preview { list-style: none; padding: 0; margin: 10px 0 0 0; color: #555; font-size: 0.9em; }
.text-muted { color: #999; }

/* Botones de acción */
.order-actions { display: flex; gap: 10px; justify-content: flex-end; }
.btn { padding: 8px 12px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; font-size: 0.9em; text-decoration: none; display: inline-block; }
.btn-details { background: #f5f5f5; color: #333; border: 1px solid #ddd; }
.btn-accept { background: #4caf50; color: white; }
.btn-reject { background: #ef5350; color: white; }
.btn:hover { opacity: 0.9; }

.empty-state { text-align: center; color: #777; padding: 40px; font-style: italic; }
.loading-state { text-align: center; padding: 20px; color: #1976d2; }
</style>