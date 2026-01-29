<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '../axios.js'; 

// IMPORTA TU IMAGEN POR DEFECTO AQUÍ
import defaultImage from '@/assets/logo.png'; 

const products = ref([]);
const loading = ref(true);
const router = useRouter();

// Variables para el MODAL y filtros
const showModal = ref(false);
const selectedProduct = ref(null);
const pickupPoints = ref([]);
const selectedPickupId = ref(null);
const loadingPoints = ref(false);
const submitting = ref(false);
const selectedQuantity = ref(1); 
const searchQuery = ref('');

onMounted(async () => {
  try {
    const response = await api.get('/products');
    products.value = response.data.map(p => ({
      ...p,
      isFavorite: false,
      isAdded: false
    }));
  } catch (error) {
    console.error("Error cargando productos:", error);
  } finally {
    loading.value = false;
  }
});

const toggleFavorite = (product) => product.isFavorite = !product.isFavorite;
const toggleCart = (product) => product.isAdded = !product.isAdded;

const openPurchaseModal = async (product) => {
    const token = localStorage.getItem('auth_token');
    if (!token) {
        if(confirm("Necesitas iniciar sesión para comprar. ¿Ir al Login?")) {
            router.push('/login');
        }
        return;
    }
    selectedProduct.value = product;
    selectedQuantity.value = 1; 
    selectedPickupId.value = null; 
    showModal.value = true;
    loadingPoints.value = true;

    try {
        const response = await api.get(`/sellers/${product.seller_id}/pickup-points`);
        pickupPoints.value = response.data;
    } catch (error) {
        console.error("Error cargando puntos:", error);
        showModal.value = false;
    } finally {
        loadingPoints.value = false;
    }
};

const closeModal = () => {
    showModal.value = false;
    selectedProduct.value = null;
    pickupPoints.value = [];
};

const confirmPurchase = async () => {
  if (!selectedPickupId.value) return;
  submitting.value = true;
  try {
    await api.post('/orders', {
      product_id: selectedProduct.value.id,
      quantity: selectedQuantity.value,
      pickup_id: selectedPickupId.value
    });
    alert("¡Pedido realizado con éxito!");
    closeModal();
    router.push('/my-purchases'); 
  } catch (error) {
    console.error("Error comprando:", error);
    alert(error.response?.data?.message || "Error al realizar el pedido.");
  } finally {
    submitting.value = false;
  }
};

const filteredProducts = computed(() => {
  return products.value.filter(p => 
    p.title.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});
</script>

<template>
  <div class="page-container">
    
    <div class="page-header">
      <div class="header-top">
        <button @click="router.push('/')" class="btn-back">← Volver</button>
      </div>
      <h2> Mercado de Proximidad</h2>
      <p class="subtitle">Productos frescos directos del agricultor a tu mesa.</p>
    </div>

    <div class="toolbar">
      <div class="search-wrapper">
        <input type="text" v-model="searchQuery" placeholder=" Buscar productos..." class="search-input">
      </div>
    </div>

    <div v-if="loading" class="empty-state">
      <div class="spinner"></div>
      <p>Cargando la huerta...</p>
    </div>

    <div v-else class="products-grid">
      <div v-for="product in filteredProducts" :key="product.id" class="product-card">
        
        <div class="card-image-wrapper">
          <img 
            :src="product.image_url || defaultImage" 
            alt="Producto" 
            class="product-img"
          >
          
          <div class="badges-overlay">
            <span v-if="product.stock > 0" class="status-badge moss">Stock: {{ product.stock }}</span>
            <span v-else class="status-badge burnt">Agotado</span>
          </div>

          <button 
            class="favorite-btn" 
            :class="{ 'is-favorite': product.isFavorite }"
            @click.stop="toggleFavorite(product)"
          >
            {{ product.isFavorite ? '❤' : '♡' }}
          </button>
        </div>
        
        <div class="card-body">
          <div class="title-row">
            <h3 class="product-title">{{ product.title }}</h3>
          </div>
          <p class="seller-info"><span class="icon"></span> {{ product.seller?.name || 'Vendedor Local' }}</p>
          <div class="divider"></div>
          <div class="info-row total-row">
            <span class="label">Precio:</span>
            <span class="price-tag">{{ product.price }}€ <small>/ {{ product.unit }}</small></span>
          </div>
        </div>

        <div class="card-footer">
          <button 
            class="action-btn btn-cart" 
            :class="{ 'added': product.isAdded }"
            @click.stop="toggleCart(product)"
            :disabled="product.stock <= 0"
          >
            {{ product.isAdded ? '✓ Añadido' : '+ Cesta' }}
          </button>

          <button 
            @click="openPurchaseModal(product)" 
            class="action-btn btn-buy"
            :disabled="product.stock <= 0"
          >
            Comprar
          </button>
        </div>
      </div>
    </div>

    <transition name="fade">
      <div v-if="showModal" class="modal-overlay">
        <div class="modal-content">
          <div class="modal-header"><h3> Finalizar Compra</h3></div>
          <div class="modal-body">
            <p class="product-summary">Producto: <strong>{{ selectedProduct?.title }}</strong></p>
            <div class="control-group">
                <label>Cantidad ({{ selectedProduct?.unit }}):</label>
                <div class="qty-wrapper">
                    <input type="number" v-model="selectedQuantity" min="1" :max="selectedProduct?.stock" class="qty-input">
                </div>
            </div>
            <div class="price-summary">
                <span>Total a pagar:</span>
                <span class="final-price">{{ (selectedProduct?.price * selectedQuantity).toFixed(2) }}€</span>
            </div>
            <div class="divider"></div>
            <p class="label-points">Selecciona punto de recogida:</p>
            <div v-if="loadingPoints" class="spinner-small"></div>
            <div v-else-if="pickupPoints.length === 0" class="rejection-box"> Este vendedor no tiene puntos configurados.</div>
            <div v-else class="points-list">
                <label v-for="point in pickupPoints" :key="point.id" class="point-option" :class="{ 'selected': selectedPickupId === point.id }">
                    <input type="radio" :value="point.id" v-model="selectedPickupId" name="pickup">
                    <div class="point-info">
                        <span class="p-address">{{ point.address }}</span>
                        <span class="p-city">{{ point.city }}</span>
                    </div>
                </label>
            </div>
          </div>
          <div class="modal-footer">
            <button @click="closeModal" class="btn-cancel">Cancelar</button>
            <button @click="confirmPurchase" class="btn-confirm" :disabled="!selectedPickupId || submitting || selectedQuantity < 1">
                {{ submitting ? '...' : 'Confirmar Pedido' }}
            </button>
          </div>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
/* --- ESTILOS GENERALES --- */
.page-container {
  width: 100%; padding: 30px 40px; box-sizing: border-box;
  font-family: 'Segoe UI', sans-serif; background-color: #f8fafc;
  min-height: 100vh; color: #0f172a;
}
@media (max-width: 768px) { .page-container { padding: 20px 15px; } }

.page-header { margin-bottom: 30px; text-align: center; }
.header-top { display: flex; justify-content: flex-start; margin-bottom: 10px; }
.btn-back { background: white; border: 1px solid #cbd5e1; padding: 6px 14px; border-radius: 8px; color: #64748b; cursor: pointer; font-weight: 600; transition: all 0.2s; }
.btn-back:hover { background: #1e293b; color: white; border-color: #1e293b; }
.page-header h2 { font-size: 2rem; color: #14532d; margin: 0; font-weight: 800; }
.subtitle { color: #64748b; margin-top: 5px; font-size: 1.1rem; }

.toolbar { margin-bottom: 30px; display: flex; justify-content: center; }
.search-wrapper { width: 100%; max-width: 500px; }
.search-input { width: 100%; padding: 12px 20px; border-radius: 12px; border: 1px solid #cbd5e1; background: white; font-size: 1rem; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
.search-input:focus { outline: none; border-color: #16a34a; ring: 2px solid #bbf7d0; }

.products-grid { display: grid; gap: 25px; width: 100%; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }

.product-card {
  background: white; border-radius: 16px;
  box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
  border: 1px solid #e2e8f0; overflow: hidden; display: flex; flex-direction: column;
  transition: transform 0.2s, box-shadow 0.2s;
}
.product-card:hover { transform: translateY(-4px); box-shadow: 0 15px 25px -5px rgba(0, 0, 0, 0.08); border-color: #bbf7d0; }

.card-image-wrapper { height: 180px; position: relative; overflow: hidden; background: #f1f5f9; display: flex; justify-content: center; align-items: center; }
.product-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s; }
.product-card:hover .product-img { transform: scale(1.05); }

.badges-overlay { position: absolute; top: 10px; left: 10px; display: flex; gap: 5px; }
.status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; backdrop-filter: blur(4px); }
.status-badge.moss { background: rgba(240, 253, 244, 0.9); color: #15803d; border: 1px solid #86efac; }
.status-badge.burnt { background: rgba(254, 242, 242, 0.9); color: #9f1239; border: 1px solid #fecdd3; }

.favorite-btn { position: absolute; top: 10px; right: 10px; width: 35px; height: 35px; border-radius: 50%; background: white; border: none; cursor: pointer; box-shadow: 0 2px 8px rgba(0,0,0,0.15); display: flex; align-items: center; justify-content: center; font-size: 1.2rem; transition: transform 0.2s; color: #cbd5e1; }
.favorite-btn:hover { transform: scale(1.1); }
.favorite-btn.is-favorite { color: #ef4444; }

.card-body { padding: 18px; flex-grow: 1; }
.product-title { margin: 0 0 5px 0; font-size: 1.2rem; color: #0f172a; font-weight: 700; }
.seller-info { color: #64748b; font-size: 0.9rem; }
.divider { height: 1px; background: #f1f5f9; margin: 12px 0; }
.info-row { display: flex; justify-content: space-between; align-items: center; }
.price-tag { font-size: 1.4rem; font-weight: 800; color: #16a34a; }
.price-tag small { font-size: 0.8rem; color: #94a3b8; font-weight: 500; }

/* --- FOOTER Y BOTONES (ARREGLO NIVELADO) --- */
.card-footer { 
  padding: 15px; 
  background: #f8fafc; 
  border-top: 1px solid #f1f5f9; 
  display: flex; 
  gap: 10px; 
  align-items: center; /* Centrado vertical */
}

.action-btn { 
  flex: 1; /* Mismo ancho */
  height: 42px; /* Misma altura fija */
  border-radius: 8px; 
  font-weight: 600; 
  cursor: pointer; 
  transition: all 0.2s;
  
  /* Flexbox para centrar contenido */
  display: flex; 
  align-items: center; 
  justify-content: center;
  font-size: 0.95rem;
  
  /* Esto asegura que el borde cuente dentro de los 42px */
  box-sizing: border-box; 
  padding: 0; 
  margin: 0;
  line-height: normal;
}

/* Botón Cesta (Tiene borde gris visible) */
.btn-cart { 
  background: white; 
  border: 1px solid #cbd5e1; 
  color: #475569; 
}
.btn-cart:hover { border-color: #1e293b; color: #1e293b; }
.btn-cart.added { background: #dcfce7; border-color: #86efac; color: #15803d; }

/* Botón Comprar (Tiene borde transparente para igualar pixels) */
.btn-buy { 
  background: #0f172a; 
  color: white; 
  border: 1px solid transparent; /* Truco para igualar altura */
}
.btn-buy:hover { background: #1e293b; transform: translateY(-1px); }
.btn-buy:disabled { background: #94a3b8; cursor: not-allowed; }

/* --- MODAL --- */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(2px); display: flex; justify-content: center; align-items: center; z-index: 2000; }
.modal-content { background: white; width: 95%; max-width: 450px; border-radius: 16px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); overflow: hidden; animation: slideUp 0.3s ease-out; }
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
.modal-header { background: #0f172a; padding: 15px 20px; color: white; }
.modal-header h3 { margin: 0; font-size: 1.1rem; }
.modal-body { padding: 25px; }
.product-summary { font-size: 1.1rem; margin-bottom: 20px; color: #334155; }
.control-group { margin-bottom: 15px; text-align: center; }
.qty-input { width: 100px; padding: 10px; text-align: center; border-radius: 8px; border: 1px solid #cbd5e1; font-size: 1.2rem; font-weight: bold; }
.price-summary { display: flex; justify-content: space-between; align-items: center; font-size: 1.1rem; }
.final-price { color: #16a34a; font-weight: 800; font-size: 1.5rem; }
.points-list { max-height: 200px; overflow-y: auto; display: flex; flex-direction: column; gap: 8px; }
.point-option { border: 1px solid #e2e8f0; padding: 12px; border-radius: 8px; cursor: pointer; display: flex; gap: 10px; align-items: center; transition: all 0.2s; }
.point-option:hover { background: #f8fafc; }
.point-option.selected { border-color: #16a34a; background: #f0fdf4; }
.point-info { display: flex; flex-direction: column; }
.p-address { font-weight: 600; color: #334155; }
.p-city { font-size: 0.85rem; color: #64748b; }
.modal-footer { padding: 15px 25px; background: #f8fafc; display: flex; justify-content: space-between; border-top: 1px solid #e2e8f0; }
.btn-cancel { background: transparent; color: #64748b; font-weight: 600; border: none; cursor: pointer; }
.btn-confirm { background: #16a34a; color: white; padding: 10px 20px; border-radius: 8px; border: none; font-weight: 700; cursor: pointer; transition: background 0.2s; }
.btn-confirm:hover { background: #15803d; }
.btn-confirm:disabled { background: #cbd5e1; cursor: not-allowed; }
.empty-state { text-align: center; padding: 60px; color: #94a3b8; }
.spinner { border: 4px solid #e2e8f0; border-top: 4px solid #16a34a; border-radius: 50%; width: 40px; height: 40px; animation: spin 1s linear infinite; margin: 0 auto 10px; }
.spinner-small { border: 2px solid #e2e8f0; border-top: 2px solid #16a34a; border-radius: 50%; width: 20px; height: 20px; animation: spin 1s linear infinite; margin: 10px auto; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>