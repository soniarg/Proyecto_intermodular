<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../api/axios.js';

const route = useRoute();
const router = useRouter();
const order = ref(null);
const loading = ref(true);

// ID del pedido que viene por la URL
const orderId = route.params.id;

onMounted(async () => {
    try {
        const response = await api.get(`/seller/orders/${orderId}`);
        // TU CONTROLADOR DEVUELVE UN ARRAY CON UN SOLO OBJETO (por el formatOrders)
        // Así que accedemos a la posición [0]
        order.value = response.data[0]; 
    } catch (error) {
        console.error("Error cargando pedido:", error);
        alert("No se pudo cargar el pedido.");
        router.push('/seller/orders'); // Volver si falla
    } finally {
        loading.value = false;
    }
});

// Función para volver atrás
const goBack = () => router.back();

// Función auxiliar para formatear fechas
const formatDate = (dateString) => {
    if(!dateString) return '-';
    return new Date(dateString).toLocaleDateString('es-ES', { 
        day: '2-digit', month: 'long', hour: '2-digit', minute: '2-digit'
    });
};

</script>

<template>
  <div class="details-container">
    
    <div v-if="loading" class="loading">Cargando detalles del pedido...</div>

    <div v-else-if="order" class="order-content">
        
        <div class="header-actions">
            <button @click="goBack" class="btn-back">← Volver</button>
            <h2 class="title">Pedido #{{ order.id }}</h2>
        </div>

        <div class="info-card status-card">
            <div class="status-badge" :class="order.status">
                Estado: {{ order.status.toUpperCase() }}
            </div>
            <p><strong>Comprador:</strong> {{ order.buyer_name }}</p>
        </div>

        <div class="info-card">
            <h3>🛒 Productos</h3>
            <table class="lines-table">
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Cant. / Peso Est.</th>
                        <th>Peso Real</th>
                        <th>Precio</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="line in order.lines" :key="line.id">
                        <td>
                            <strong>{{ line.name }}</strong>
                            <br><small class="text-muted">{{ line.unit }}</small>
                        </td>
                        <td>
                            <span v-if="line.unit === 'kg'">~{{ line.estimated_weight }} kg</span>
                            <span v-else>{{ line.quantity }} un.</span>
                        </td>
                        <td>
                            <span v-if="line.real_weight" class="highlight">{{ line.real_weight }}</span>
                            <span v-else>-</span>
                        </td>
                        <td>{{ line.line_price }}€</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="total-section">
            <h3>Total: {{ order.total_price }}€</h3>
        </div>

        <!-- <div class="actions-footer">
            <button class="btn btn-action">Enviar Mensaje al Comprador</button>
        </div> -->

    </div>
  </div>
</template>

<style scoped>
.details-container { max-width: 800px; margin: 20px auto; padding: 20px; font-family: 'Segoe UI', sans-serif; }
.header-actions { display: flex; align-items: center; gap: 20px; margin-bottom: 20px; }
.btn-back { background: none; border: none; font-size: 1rem; color: #666; cursor: pointer; }
.title { margin: 0; color: #2c3e50; }

.info-card { background: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }

.status-badge { display: inline-block; padding: 5px 12px; border-radius: 15px; font-weight: bold; margin-bottom: 10px; }
/* Colores de estado (igual que en la vista anterior) */
.new { background: #e3f2fd; color: #1976d2; }
.pending { background: #fff3e0; color: #f57c00; }
.weight_adjusted { background: #e8f5e9; color: #388e3c; }
.ready { background: #e0f7fa; color: #006064; }
.completed { background: #eeeeee; color: #616161; }

.lines-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
.lines-table th, .lines-table td { text-align: left; padding: 12px; border-bottom: 1px solid #eee; }
.text-muted { color: #888; font-size: 0.9em; }
.highlight { color: #27ae60; font-weight: bold; }

.total-section { text-align: right; font-size: 1.5rem; color: #2c3e50; font-weight: bold; margin-top: 20px; }

.actions-footer { margin-top: 30px; display: flex; gap: 15px; justify-content: flex-end; }
.btn-action { padding: 10px 20px; border-radius: 5px; border: none; background: #3490dc; color: white; cursor: pointer; }
</style>