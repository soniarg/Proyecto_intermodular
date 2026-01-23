<template>
  <div class="orders-container">
    <h2>📦 Mis Compras</h2>

    <div v-if="loading">Cargando historial...</div>
    
    <div v-else-if="orders.length === 0">
      <p>No has hecho ningún pedido aún.</p>
    </div>

    <div v-else class="orders-list">
      <div v-for="order in orders" :key="order.id" class="order-card">
        
        <div class="order-header">
          <span class="order-id">Pedido #{{ order.id }}</span>
          <span :class="['status-badge', order.status]">{{ order.status }}</span>
        </div>

        <div class="order-body">
          <p><strong>Vendedor:</strong> {{ order.seller?.name || 'Desconocido' }}</p>
          <p><strong>Total:</strong> {{ order.total_price }}€</p>
          <p><strong>Fecha:</strong> {{ new Date(order.created_at).toLocaleDateString() }}</p>
          
          <ul class="product-list">
            <li v-for="line in order.lines" :key="line.id">
              {{ line.product?.title || 'Producto' }} (x{{ line.quantity }})
            </li>
          </ul>
        </div>

        <div class="order-actions">
            <button @click="goToChat(order.id)" class="btn-chat">
                💬 Contactar Vendedor
            </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import api from '../axios.js'; // Tu axios configurado
import { useRouter } from 'vue-router';

const orders = ref([]);
const loading = ref(true);
const router = useRouter();

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

const goToChat = (orderId) => {
    // Redirige a la vista del chat que arreglamos antes
    router.push(`/chat/${orderId}`);
};
</script>

<style scoped>
.orders-container { max-width: 800px; margin: 0 auto; padding: 20px; }
.order-card { border: 1px solid #ddd; padding: 15px; margin-bottom: 15px; border-radius: 8px; background: white; }
.order-header { display: flex; justify-content: space-between; margin-bottom: 10px; font-weight: bold; }
.status-badge { padding: 4px 8px; border-radius: 4px; font-size: 0.9em; background: #eee; }
.status-badge.pending { background: #fff3cd; color: #856404; }
.status-badge.completed { background: #d4edda; color: #155724; }
.btn-chat { background: #3490dc; color: white; border: none; padding: 8px 12px; border-radius: 4px; cursor: pointer; }
.btn-chat:hover { background: #2779bd; }
</style>