<template>
  <div class="orders-container">
    
    <div class="page-header">
      <div class="header-top">
        <button @click="goBack" class="btn-back">← Volver</button>
      </div>
      <h2>Mis Compras</h2>
    </div>

    <div class="tabs-toolbar">
      
      <div class="sort-wrapper">
        <div class="custom-select-container" ref="dropdownRef">
          
          <div class="custom-select-trigger" @click="isOpen = !isOpen" :class="{ 'is-open': isOpen }">
            <span>{{ sortOrder === 'desc' ? ' Más Recientes' : ' Más Antiguos' }}</span>
            <span class="arrow-icon">▼</span>
          </div>

          <transition name="fade">
            <ul v-if="isOpen" class="custom-options-list">
              <li @click="selectOption('desc')" :class="{ selected: sortOrder === 'desc' }">
                 Más Recientes
              </li>
              <li @click="selectOption('asc')" :class="{ selected: sortOrder === 'asc' }">
                 Más Antiguos
              </li>
            </ul>
          </transition>

        </div>
      </div>

      <div class="tabs-wrapper">
        <div class="tabs-container">
          <button 
            v-for="tab in tabs" 
            :key="tab.key"
            @click="activeTab = tab.key"
            :class="['tab-btn', { active: activeTab === tab.key }]"
          >
            {{ tab.label }}
            
            <span v-if="orderCounts[tab.key] > 0 && tab.key !== 'all'" class="notification-badge">
              {{ orderCounts[tab.key] }}
            </span>
          </button>
        </div>
      </div>

    </div>

    <div v-if="loading" class="loading-state">
      <div class="spinner"></div>
      <p>Cargando pedidos...</p>
    </div>
    
    <div v-else-if="orders.length === 0" class="empty-state">
      <h3>No tienes pedidos</h3>
      <p>Aún no has realizado ninguna compra.</p>
    </div>

    <div v-else-if="filteredOrders.length === 0" class="empty-state">
      <p>No hay pedidos en <strong>"{{ getTabLabel(activeTab) }}"</strong>.</p>
    </div>

    <div v-else class="orders-grid">
      <div v-for="order in filteredOrders" :key="order.id" class="order-card">
        
        <div class="order-header">
          <div class="order-id">
            <span class="hash">#</span>{{ order.id }}
          </div>
          <span :class="['status-badge', order.status]">
            {{ getStatusLabel(order.status) }}
          </span>
        </div>

        <div class="order-body">
          <div class="info-row">
            <span class="label">Vendedor:</span>
            <span class="value">{{ order.seller?.name || 'Vendedor Desconocido' }}</span>
          </div>
          <div class="info-row">
            <span class="label">Fecha:</span>
            <span class="value">{{ formatDate(order.created_at) }}</span>
          </div>
          <div class="info-row total-row">
            <span class="label">Total:</span>
            <span class="value price">{{ formatPrice(order.total_price) }}</span>
          </div>
          
          <div class="divider"></div>

          <div class="products-section">
            <p class="products-title">Productos:</p>
            <ul class="product-list">
              <li v-for="line in order.lines" :key="line.id">
                <span class="bullet">•</span> 
                {{ line.product?.title || 'Producto' }} 
                <span class="qty">x{{ line.quantity }}</span>
              </li>
            </ul>
          </div>

          <div v-if="order.status === 'rejected' && order.rejection_reason" class="rejection-box">
            <strong>Motivo:</strong> {{ order.rejection_reason }}
          </div>
        </div>

        <div class="order-footer">
            <button @click="goToChat(order.id)" class="btn-chat">
               Chat con el vendedor
            </button>
        </div>

      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import api from '../axios.js';
import { useRouter } from 'vue-router';

const orders = ref([]);
const loading = ref(true);
const router = useRouter();

// VARIABLES ESTADO
const sortOrder = ref('desc');
const activeTab = ref('all');

// VARIABLES PARA EL DROPDOWN PERSONALIZADO
const isOpen = ref(false);
const dropdownRef = ref(null);

const tabs = [
  { key: 'all',       label: 'Todos' },
  { key: 'new',       label: 'Por Confirmar' },
  { key: 'pending',   label: 'En Preparación' },
  { key: 'ready',     label: 'Listos' },
  { key: 'completed', label: 'Historial' },
  { key: 'cancelled', label: 'Cancelados' }
];

// LÓGICA DROPDOWN
const selectOption = (value) => {
  sortOrder.value = value;
  isOpen.value = false;
};

// Cerrar dropdown si clicamos fuera
const closeDropdown = (e) => {
  if (dropdownRef.value && !dropdownRef.value.contains(e.target)) {
    isOpen.value = false;
  }
};

const filteredOrders = computed(() => {
  let result = orders.value;
  if (activeTab.value !== 'all') {
    if (activeTab.value === 'cancelled') {
       result = result.filter(o => ['cancelled', 'rejected'].includes(o.status));
    } else if (activeTab.value === 'completed') {
       result = result.filter(o => o.status === 'completed');
    } else {
       result = result.filter(o => o.status === activeTab.value);
    }
  }
  return [...result].sort((a, b) => {
    const dateA = new Date(a.created_at);
    const dateB = new Date(b.created_at);
    return sortOrder.value === 'desc' ? dateB - dateA : dateA - dateB;
  });
});

const orderCounts = computed(() => {
  const counts = { all: orders.value.length };
  tabs.forEach(t => { if(t.key !== 'all') counts[t.key] = 0; });
  orders.value.forEach(order => {
    if (counts.hasOwnProperty(order.status)) counts[order.status]++;
    if (['cancelled', 'rejected'].includes(order.status)) counts['cancelled']++;
  });
  return counts;
});

const getTabLabel = (key) => tabs.find(t => t.key === key)?.label || key;
const goBack = () => router.back();

const statusConfig = {
  draft:           { label: 'Borrador', class: 'gray' },
  new:             { label: 'Esperando', class: 'olive' },
  pending:         { label: 'Preparando', class: 'lime' },
  weight_adjusted: { label: 'Peso Ajustado', class: 'moss' },
  ready:           { label: 'Listo', class: 'forest' },
  completed:       { label: 'Completado', class: 'black' },
  rejected:        { label: 'Rechazado', class: 'burnt' },
  cancelled:       { label: 'Cancelado', class: 'slate' }
};

const getStatusLabel = (status) => statusConfig[status]?.label || status;
const formatDate = (dateString) => dateString ? new Date(dateString).toLocaleDateString('es-ES', { day: '2-digit', month: 'short' }) : '';
const formatPrice = (price) => Number(price).toLocaleString('es-ES', { style: 'currency', currency: 'EUR' });

onMounted(async () => {
  document.addEventListener('click', closeDropdown); // Escuchar clicks globales
  try {
    const response = await api.get('/my-orders');
    orders.value = response.data;
  } catch (error) { console.error("Error:", error); } 
  finally { loading.value = false; }
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown); // Limpiar evento
});

const goToChat = (id) => router.push(`/chat/${id}`);
</script>

<style scoped>
/* --- 1. FONDO Y COLORES GENERALES --- */
.orders-container {
  width: 100%;
  padding: 30px 40px;
  box-sizing: border-box;
  font-family: 'Segoe UI', sans-serif;
  background-color: #f8fafc;
  min-height: 100vh;
  color: #0f172a;
}

@media (max-width: 768px) {
  .orders-container { padding: 20px 15px; }
}

.page-header { margin-bottom: 20px; text-align: center; position: relative; }
.header-top { display: flex; justify-content: flex-start; }

.btn-back {
  background: white; border: 1px solid #1e293b; padding: 8px 16px;
  border-radius: 8px; color: #1e293b; cursor: pointer; font-weight: 600;
  transition: all 0.2s;
}
.btn-back:hover { background: #1e293b; color: #4ade80; }
.page-header h2 { font-size: 2rem; color: #14532d; margin: 10px 0 0 0; font-weight: 800; }

/* --- 2. BARRA DE HERRAMIENTAS --- */
.tabs-toolbar {
  display: flex;
  flex-direction: column-reverse;
  gap: 20px;
  margin-bottom: 30px;
}
@media (min-width: 900px) {
  .tabs-toolbar {
    flex-direction: row;
    align-items: center;
    justify-content: space-between;
  }
  .sort-wrapper { order: 2; }
}

/* --- 3. CUSTOM DROPDOWN (SIN AZUL) --- */
.sort-wrapper { width: 100%; }
@media (min-width: 900px) { .sort-wrapper { width: auto; } }

.custom-select-container {
  position: relative;
  width: 100%;
  min-width: 220px;
}

/* Botón disparador */
.custom-select-trigger {
  background-color: #f0fdf4; /* Fondo Menta */
  border: 1px solid #bbf7d0;
  border-radius: 10px;
  padding: 12px 20px;
  font-size: 0.95rem;
  font-weight: 600;
  color: #166534;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  transition: all 0.2s;
}

.custom-select-trigger:hover {
  background-color: #dcfce7;
  border-color: #15803d;
}

.arrow-icon {
  font-size: 0.7rem;
  transition: transform 0.3s;
}

.custom-select-trigger.is-open .arrow-icon {
  transform: rotate(180deg);
}

/* Lista desplegable */
.custom-options-list {
  position: absolute;
  top: 110%; /* Justo debajo */
  left: 0;
  width: 100%;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
  padding: 5px;
  z-index: 50; /* Por encima de todo */
  list-style: none;
  margin: 0;
}

/* Opciones individuales */
.custom-options-list li {
  padding: 10px 15px;
  border-radius: 6px;
  cursor: pointer;
  color: #334155;
  font-weight: 500;
  transition: background 0.2s;
}

/* HOVER PERSONALIZADO (AQUÍ ES DONDE QUITAMOS EL AZUL) */
.custom-options-list li:hover {
  background-color: #dcfce7; /* Verde menta al pasar ratón */
  color: #166534;
}

/* Opción seleccionada */
.custom-options-list li.selected {
  background-color: #f0fdf4;
  color: #15803d;
  font-weight: 700;
}

/* Animación Fade */
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }


/* --- 4. PESTAÑAS (Estilo Negro/Verde) --- */
.tabs-wrapper { width: 100%; }
.tabs-container {
  display: flex;
  flex-wrap: wrap;       
  justify-content: center; 
  gap: 8px;
}
@media (min-width: 900px) { .tabs-container { justify-content: flex-start; } }

.tab-btn {
  background: transparent; border: 1px solid transparent; padding: 8px 16px;
  border-radius: 6px; color: #64748b; font-weight: 600; font-size: 0.9rem;
  cursor: pointer; display: flex; align-items: center; gap: 6px;
  transition: all 0.2s; flex-shrink: 0; 
}
.tab-btn:hover { color: #15803d; background: #dcfce7; }
.tab-btn.active {
  background: #111827; color: #4ade80; border: 1px solid #111827;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}
.notification-badge {
  background: #15803d; color: white; font-size: 0.75rem;
  font-weight: bold; padding: 2px 7px; border-radius: 4px;
}

/* --- 5. TARJETAS Y GRID --- */
.orders-grid { display: grid; gap: 25px; width: 100%; grid-template-columns: 1fr; }
@media (min-width: 768px) { .orders-grid { grid-template-columns: repeat(2, 1fr); } }
@media (min-width: 1100px) { .orders-grid { grid-template-columns: repeat(3, 1fr); } }
@media (min-width: 1500px) { .orders-grid { grid-template-columns: repeat(4, 1fr); } }

.order-card {
  background: white; border-radius: 12px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
  border: 1px solid #e2e8f0; overflow: hidden; display: flex; flex-direction: column; 
  transition: transform 0.2s, box-shadow 0.2s;
}
.order-card:hover {
  transform: translateY(-3px);
  box-shadow: 0 10px 15px -3px rgba(20, 83, 45, 0.1);
  border-color: #bbf7d0;
}

.order-header { padding: 15px 20px; background: #fff; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.order-id { font-weight: 800; color: #0f172a; font-size: 1.1rem; }
.hash { color: #94a3b8; }

.status-badge { padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
.status-badge.olive { background: #f7fee7; color: #3f6212; border: 1px solid #d9f99d; } 
.status-badge.lime  { background: #ecfccb; color: #4d7c0f; border: 1px solid #bef264; } 
.status-badge.moss  { background: #f0fdf4; color: #15803d; border: 1px solid #86efac; } 
.status-badge.forest{ background: #dcfce7; color: #166534; border: 1px solid #86efac; } 
.status-badge.black { background: #0f172a; color: #4ade80; } 
.status-badge.slate { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; } 
.status-badge.burnt { background: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; } 
.status-badge.gray  { background: #f8fafc; color: #94a3b8; } 

.order-body { padding: 20px; flex-grow: 1; }
.info-row { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 0.95rem; }
.label { color: #64748b; }
.value { color: #0f172a; font-weight: 600; }
.total-row .price { font-size: 1.3rem; font-weight: 800; color: #16a34a; }
.divider { height: 1px; background: #f1f5f9; margin: 15px 0; }
.product-list { list-style: none; padding: 0; margin: 0; }
.product-list li { font-size: 0.95rem; color: #334155; margin-bottom: 5px; }
.qty { color: #15803d; font-weight: bold; background: #dcfce7; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem; margin-left: 5px; }
.rejection-box { margin-top: 15px; padding: 12px; background: #fff1f2; border-left: 4px solid #9f1239; border-radius: 4px; color: #881337; font-size: 0.9rem; }
.order-footer { padding: 15px 20px; background: #f8fafc; border-top: 1px solid #f1f5f9; margin-top: auto; }

.btn-chat { 
  width: 100%; background: white; border: 1px solid #0f172a; color: #0f172a; 
  padding: 12px; border-radius: 8px; font-weight: 700; cursor: pointer; transition: all 0.2s; 
}
.btn-chat:hover { background: #0f172a; color: #4ade80; border-color: #0f172a; }

.empty-state { text-align: center; padding: 60px 20px; color: #94a3b8; font-size: 1.1rem; grid-column: 1 / -1; }
.spinner { border: 4px solid #e2e8f0; border-top: 4px solid #16a34a; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 20px; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>