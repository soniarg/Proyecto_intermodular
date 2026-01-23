<template>
  <div class="marketplace-container">
    <h2 class="title">🍏 Mercado de Proximidad</h2>
    <p class="subtitle">Productos frescos directos del agricultor a tu mesa.</p>

    <div v-if="loading" class="loading">
      <p>Cargando productos frescos...</p>
    </div>

    <div v-else class="products-grid">
      <div v-for="product in products" :key="product.id" class="product-card">
        
        <div class="image-container">
           <img :src="product.image_url || 'https://via.placeholder.com/300x200?text=Producto+Local'" alt="Producto" class="product-img">
           <span class="stock-badge" v-if="product.stock > 0">Stock: {{ product.stock }}</span>
           <span class="stock-badge no-stock" v-else>Agotado</span>
        </div>
        
        <div class="card-body">
          <h3>{{ product.title }}</h3>
          <p class="seller-name">👨‍🌾 {{ product.seller?.name || 'Vendedor Local' }}</p>
          
          <div class="price-row">
            <span class="price">{{ product.price }}€</span>
            <span class="unit">/ {{ product.unit }}</span>
          </div>

          <button 
            @click="openPurchaseModal(product)" 
            class="btn-buy"
            :disabled="product.stock <= 0"
          >
            {{ product.stock > 0 ? '🛒 Comprar' : 'Agotado' }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="modal-overlay">
      <div class="modal-content">
        <h3>📍 Elige dónde recogerlo</h3>
        <p>Estás comprando: <strong>{{ selectedProduct?.title }}</strong></p>

        <div class="quantity-section">
            <label>Cantidad ({{ selectedProduct?.unit }}):</label>
            <div class="qty-control">
                <input 
                    type="number" 
                    v-model="selectedQuantity" 
                    min="1" 
                    :max="selectedProduct?.stock"
                    class="qty-input"
                >
            </div>
            <p class="total-price">
                Total a pagar: 
                <strong>{{ (selectedProduct?.price * selectedQuantity).toFixed(2) }}€</strong>
            </p>
        </div>
        <div v-if="loadingPoints" class="loading-spinner">
            Cargando puntos de entrega...
        </div>

        <div v-else-if="pickupPoints.length === 0" class="no-points">
            <p>⚠️ Este vendedor no ha configurado puntos de entrega aún.</p>
            <p class="text-sm">Contacta con él por el chat después de comprar o busca otro producto.</p>
        </div>

        <div v-else class="points-list">
            <label v-for="point in pickupPoints" :key="point.id" class="point-option">
                <input 
                    type="radio" 
                    :value="point.id" 
                    v-model="selectedPickupId" 
                    name="pickup_point"
                >
                <div class="point-details">
                    <span class="point-name">{{ point.address }}</span>
                    <span class="point-city">{{ point.city }} ({{ point.postal_code }})</span>
                </div>
            </label>
        </div>

        <div class="modal-actions">
            <button @click="closeModal" class="btn-cancel">Cancelar</button>
            <button 
                @click="confirmPurchase" 
                class="btn-confirm"
                :disabled="!selectedPickupId || submitting || selectedQuantity < 1"
            >
                {{ submitting ? 'Procesando...' : '✅ Confirmar Pedido' }}
            </button>
        </div>
      </div>
    </div>

  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../axios.js'; 

const products = ref([]);
const loading = ref(true);
const router = useRouter();

// Variables para el MODAL
const showModal = ref(false);
const selectedProduct = ref(null);
const pickupPoints = ref([]);
const selectedPickupId = ref(null);
const loadingPoints = ref(false);
const submitting = ref(false);

// 👇 NUEVA VARIABLE PARA LA CANTIDAD
const selectedQuantity = ref(1); 

// 1. Cargar productos al entrar
onMounted(async () => {
  try {
    const response = await api.get('/products');
    products.value = response.data;
  } catch (error) {
    console.error("Error cargando productos:", error);
  } finally {
    loading.value = false;
  }
});

// 2. ABRIR EL MODAL (Paso 1 de la compra)
const openPurchaseModal = async (product) => {
    const token = localStorage.getItem('auth_token');
    if (!token) {
        if(confirm("Necesitas iniciar sesión para comprar. ¿Ir al Login?")) {
            router.push('/login');
        }
        return;
    }

    selectedProduct.value = product;
    selectedQuantity.value = 1; // <--- RESETEAMOS A 1 SIEMPRE AL ABRIR
    selectedPickupId.value = null; 
    showModal.value = true;
    loadingPoints.value = true;

    try {
        // Pedimos al backend los puntos de ESTE vendedor concreto
        const response = await api.get(`/sellers/${product.seller_id}/pickup-points`);
        pickupPoints.value = response.data;
    } catch (error) {
        console.error("Error cargando puntos:", error);
        alert("Error al cargar los puntos de entrega.");
        showModal.value = false;
    } finally {
        loadingPoints.value = false;
    }
};

// 3. CERRAR MODAL
const closeModal = () => {
    showModal.value = false;
    selectedProduct.value = null;
    pickupPoints.value = [];
};

// 4. CONFIRMAR COMPRA (Paso final)
const confirmPurchase = async () => {
  if (!selectedPickupId.value) return;

  submitting.value = true;

  try {
    // Enviamos el pickup_id Y LA CANTIDAD al backend
    await api.post('/orders', {
      product_id: selectedProduct.value.id,
      quantity: selectedQuantity.value, // <--- ¡AQUÍ ENVIAMOS LA CANTIDAD ELEGIDA!
      pickup_id: selectedPickupId.value
    });

    alert("¡Pedido realizado con éxito! 🎉");
    closeModal();
    router.push('/my-orders'); 
    
  } catch (error) {
    console.error("Error comprando:", error);
    alert(error.response?.data?.message || "Error al realizar el pedido.");
  } finally {
    submitting.value = false;
  }
};
</script>

<style scoped>
/* Estilos generales */
.marketplace-container { padding: 20px; max-width: 1200px; margin: 0 auto; }
.title { text-align: center; color: #2c3e50; margin-bottom: 5px; }
.subtitle { text-align: center; color: #666; margin-bottom: 30px; }
.products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }

/* Tarjetas */
.product-card { border: 1px solid #eee; border-radius: 10px; overflow: hidden; background: white; transition: transform 0.2s; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.product-card:hover { transform: translateY(-3px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
.image-container { height: 180px; overflow: hidden; position: relative; }
.product-img { width: 100%; height: 100%; object-fit: cover; }
.stock-badge { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em; }
.stock-badge.no-stock { background: #e74c3c; }
.card-body { padding: 15px; }
.card-body h3 { margin: 0 0 5px 0; font-size: 1.1em; }
.seller-name { color: #7f8c8d; font-size: 0.9em; margin-bottom: 15px; }
.price-row { display: flex; align-items: baseline; margin-bottom: 15px; }
.price { font-size: 1.4em; font-weight: bold; color: #27ae60; }
.unit { color: #999; margin-left: 5px; }
.btn-buy { width: 100%; background: #27ae60; color: white; border: none; padding: 10px; border-radius: 5px; cursor: pointer; font-weight: bold; transition: background 0.3s; }
.btn-buy:hover { background: #219150; }
.btn-buy:disabled { background: #ccc; cursor: not-allowed; }

/* --- ESTILOS DEL MODAL --- */
.modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 0, 0.5);
    display: flex; justify-content: center; align-items: center;
    z-index: 1000;
}
.modal-content {
    background: white; padding: 25px; border-radius: 10px;
    width: 90%; max-width: 500px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
}

/* 👇 ESTILOS NUEVOS PARA LA CANTIDAD 👇 */
.quantity-section {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 15px;
    text-align: center;
    border: 1px solid #e9ecef;
}

.qty-input {
    width: 80px;
    padding: 8px;
    font-size: 1.2em;
    text-align: center;
    border: 1px solid #ced4da;
    border-radius: 5px;
    margin: 10px 0;
}

.total-price {
    font-size: 1.1em;
    color: #2c3e50;
    margin-top: 5px;
}
/* 👆 FIN ESTILOS NUEVOS 👆 */

.points-list {
    max-height: 300px; overflow-y: auto; margin: 15px 0;
    border: 1px solid #eee; border-radius: 5px;
}
.point-option {
    display: flex; align-items: center; padding: 12px;
    border-bottom: 1px solid #eee; cursor: pointer;
    transition: background 0.2s;
}
.point-option:hover { background: #f9f9f9; }
.point-option input { margin-right: 15px; }
.point-details { display: flex; flex-direction: column; }
.point-name { font-weight: bold; color: #333; }
.point-city { font-size: 0.9em; color: #666; } /* Cambiado clase */

.modal-actions { display: flex; justify-content: space-between; margin-top: 20px; }
.btn-cancel { background: #e74c3c; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
.btn-confirm { background: #3490dc; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
.btn-confirm:disabled { background: #a0aec0; cursor: not-allowed; }
</style>