<script setup>
import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api/axios.js';
import { useToast } from 'vue-toastification';

const products = ref([]);
const loading = ref(true);
const router = useRouter();
const toast = useToast();

// URL BASE PARA IMÁGENES
const BASE_URL = 'http://localhost:8000/storage/'; 

// Variables Modal
const showModal = ref(false);
const selectedProduct = ref(null);
const pickupPoints = ref([]);
const selectedPickupId = ref(null);
const loadingPoints = ref(false);
const submitting = ref(false);
const selectedQuantity = ref(1);

// Variables Filtro
const searchQuery = ref('');
const searchCity = ref(''); 
const maxPrice = ref(100);    
const maxPriceLimit = ref(100); 

// ID del usuario actual
const currentUserId = ref(null);

// --- COMPUTED: FILTRADO ---
const filteredProducts = computed(() => {
  return products.value.filter(product => {
    
    // 1. Filtro Nombre
    const prodTitle = product.title ? product.title.toLowerCase() : '';
    const matchesName = prodTitle.includes(searchQuery.value.toLowerCase());
    
    // 2. Filtro Precio
    const matchesPrice = parseFloat(product.price) <= maxPrice.value;
    
    // 3. FILTRO CIUDAD (Buscamos en los puntos que hemos cargado manualmente)
    let matchesCity = true; 
    
    if (searchCity.value.trim() !== '') {
        const busqueda = searchCity.value.toLowerCase();
        
        // Ahora sí tendremos los puntos aquí gracias al truco del onMounted
        const puntos = product.seller?.pickup_points || [];
        
        // Comprobamos si ALGUNO de los puntos coincide con la ciudad
        matchesCity = puntos.some(punto => 
            punto.city && punto.city.toLowerCase().includes(busqueda)
        );
    }
    
    return matchesName && matchesPrice && matchesCity;
  });
});

// --- RESETEAR FILTROS ---
const resetFilters = () => {
    searchQuery.value = '';
    searchCity.value = '';
    maxPrice.value = maxPriceLimit.value; 
};

// --- CARGA INICIAL (CON EL TRUCO PARA CARGAR CIUDADES) ---
onMounted(async () => {
  loading.value = true;
  try {
    try {
        const userResponse = await api.get('/user');
        currentUserId.value = userResponse.data.id;
    } catch (e) {
        console.log("Usuario no logueado");
    }

    // 1. Cargamos productos normales (vienen SIN ciudad)
    const response = await api.get('/products');
    let incomingData = response.data.data || response.data;

    if (Array.isArray(incomingData)) {
        // Filtrar mis productos
        if (currentUserId.value) {
            incomingData = incomingData.filter(product => product.seller_id !== currentUserId.value);
        }
        
        products.value = incomingData;

        // Calcular precio máximo
        if (products.value.length > 0) {
            const highest = Math.max(...products.value.map(p => parseFloat(p.price)));
            maxPriceLimit.value = Math.ceil(highest); 
            maxPrice.value = maxPriceLimit.value;     
        }

        // ============================================================
        // TRUCO: Cargar las ciudades manualmente una por una
        // ============================================================
        // Obtenemos lista de IDs de vendedores únicos para no llamar repetido
        const sellerIds = [...new Set(products.value.map(p => p.seller_id))];

        // Por cada vendedor, pedimos sus puntos de recogida
        for (const sellerId of sellerIds) {
             try {
                 // Usamos el endpoint que ya tenías para el modal
                 const pointsResponse = await api.get(`/seller/pickup-points/${sellerId}`);
                 const pointsData = pointsResponse.data.data || pointsResponse.data;

                 // Asignamos esos puntos a TODOS los productos de ese vendedor
                 products.value.forEach(product => {
                     if (product.seller_id === sellerId && product.seller) {
                         // Inyectamos los puntos "a la fuerza" en el objeto seller
                         product.seller.pickup_points = pointsData;
                     }
                 });
             } catch (err) {
                 console.error(`Error cargando puntos para vendedor ${sellerId}`, err);
             }
        }
        // ============================================================
    }

  } catch (error) {
    console.error("Error cargando datos:", error);
  } finally {
    loading.value = false;
  }
});

// --- ABRIR MODAL ---
const openPurchaseModal = async (product) => {
    const token = localStorage.getItem('auth_token');
    if (!token) {
        if(confirm("Necesitas iniciar sesión. ¿Ir al Login?")) router.push('/login');
        return;
    }

    selectedProduct.value = product;
    selectedQuantity.value = 1;
    selectedPickupId.value = null;
    showModal.value = true;
    loadingPoints.value = true; 
    pickupPoints.value = [];    

    try {
        // Como ya hemos cargado los puntos en el truco de arriba, los usamos directamente
        if (product.seller && product.seller.pickup_points) {
             pickupPoints.value = product.seller.pickup_points;
        } else {
            // Si falló el truco, los pedimos de nuevo por seguridad
            const sellerId = product.seller_id || product.seller?.id; 
            const response = await api.get(`/seller/pickup-points/${sellerId}`);
            pickupPoints.value = response.data.data || response.data;
        }
        loadingPoints.value = false;
    } catch (error) {
        console.error("Error cargando puntos modal:", error);
        loadingPoints.value = false;
    }
};

const closeModal = () => { 
    showModal.value = false; 
    selectedProduct.value = null;
};

// --- CONFIRMAR COMPRA ---
const confirmPurchase = async () => {
    if (!selectedPickupId.value) return;
    submitting.value = true;

    try {
        const productId = selectedProduct.value.id;
        await api.post(`/seller/orders/${productId}/store`, {
            quantity: selectedQuantity.value,
            pickup_id: selectedPickupId.value
        });
        
        toast.success("¡Pedido realizado con éxito!");
        closeModal();
        router.push('/my-purchases'); 

    } catch (error) {
        console.error(error);
        toast.error("Error al realizar el pedido.");
    } finally {
        submitting.value = false;
    }
};
</script>

<template>
  <div class="marketplace-container">
    <h2 class="title">Mercado de Proximidad</h2>
    <p class="subtitle">Productos frescos directos del agricultor a tu mesa.</p>

    <div class="filters-wrapper">
      <div class="search-box">
        <input v-model="searchQuery" type="text" placeholder="🔍 Buscar" class="form-control" />
      </div>
      
      <div class="city-filter">
        <input v-model="searchCity" type="text" placeholder="📍 Filtrar por ciudad..." class="form-control"/>
      </div>

      <div class="price-filter">
        <label>Precio máx: <strong>{{ maxPrice }}€</strong></label>
        <input type="range" v-model.number="maxPrice" :min="0" :max="maxPriceLimit" step="0.5" class="range-slider" />
        <div class="range-labels">
            <span>0€</span><span>{{ maxPriceLimit }}€</span>
        </div>
      </div>
    </div>

    <div v-if="loading" class="loading"><p>Cargando productos frescos...</p></div>

    <div v-else>
      <div v-if="filteredProducts.length === 0" class="no-results">
        <p>No hay productos disponibles con esos filtros.</p>
        <button @click="resetFilters" class="btn-clear">Limpiar filtros</button>
      </div>

      <div v-else class="products-grid">
        <div v-for="product in filteredProducts" :key="product.id" class="product-card">
          
          <div class="image-container" 
               @click="product.stock > 0 ? openPurchaseModal(product) : null"
               :class="{ 'is-clickable': product.stock > 0 }">
             
             <img 
                :src="product.image_url ? (product.image_url.startsWith('http') ? product.image_url : BASE_URL + product.image_url) : 'https://via.placeholder.com/300x200?text=Sin+Imagen'" 
                :alt="product.title" 
                class="product-img"
             >
             <span class="stock-badge" v-if="product.stock > 0">
                Stock: {{ parseFloat(product.stock) }} {{ product.unit }}
             </span>
             <span class="stock-badge no-stock" v-else>Agotado</span>
          </div>

          <div class="card-body">
            <h3>{{ product.title }}</h3>
            
            <div class="seller-info">

                <span class="seller-name">
                    👨‍🌾 {{ product.seller?.store_name || ('Vendedor #' + product.seller_id) }}
                </span>
                
                <span v-if="product.seller?.pickup_points?.length > 0" class="location-badge">
                   📍 {{ product.seller.pickup_points[0].city }}
                </span>
            </div>

            <div class="price-row">
              <span class="price" :class="{'highlight-price': product.price <= maxPrice}">{{ product.price }}€</span>
              <span class="unit">/ {{ product.unit }}</span>
            </div>
            <button @click="openPurchaseModal(product)" class="btn-buy" :disabled="product.stock <= 0">
              {{ product.stock > 0 ? 'Comprar' : 'Agotado' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showModal" class="modal-overlay">
       <div class="modal-content">
           <h3>Elige dónde recogerlo</h3>
           <p>Estás comprando: <strong>{{ selectedProduct?.title }}</strong></p>

           <div class="quantity-section">
               <label>Cantidad ({{ selectedProduct?.unit }}):</label>
               <div class="qty-control">
                   <input type="number" v-model="selectedQuantity" min="1" :max="selectedProduct?.stock" class="qty-input">
               </div>
               <p class="total-price">
                   Total estimado: <strong>{{ (selectedProduct?.price * selectedQuantity).toFixed(2) }}€</strong>
               </p>
           </div>

           <div class="pickup-section">
               <h4 style="margin-bottom:10px; font-size:1em;">Punto de entrega:</h4>
               
               <div v-if="loadingPoints" class="loading-spinner">Cargando puntos...</div>

               <div v-else-if="pickupPoints.length === 0" class="no-points">
                   <p>Este vendedor no tiene puntos configurados.</p>
                   <small>Contacta con él por el chat tras comprar.</small>
               </div>

               <div v-else class="points-list">
                   <label v-for="point in pickupPoints" :key="point.id" class="point-option">
                       <input type="radio" :value="point.id" v-model="selectedPickupId" name="pickup">
                       <div class="point-details">
                           <span class="point-name">{{ point.address }}</span>
                           <span class="point-city">{{ point.city }} ({{ point.postal_code }})</span>
                       </div>
                   </label>
               </div>
           </div>

           <div class="modal-actions">
               <button @click="closeModal" class="btn-cancel">Cancelar</button>
               <button 
                   @click="confirmPurchase" 
                   class="btn-confirm" 
                   :disabled="!selectedPickupId || submitting"
               >
                   {{ submitting ? 'Procesando...' : 'Confirmar Pedido' }}
               </button>
           </div>
       </div>
    </div>

  </div>
</template>

<style scoped>
/* Filtros */
.filters-wrapper { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; align-items: center; background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
.search-box { flex: 1; min-width: 250px; }
.city-filter { flex: 1; min-width: 200px; } 

.form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }

.price-filter { flex: 0 0 250px; display: flex; flex-direction: column; }
.range-slider { width: 100%; cursor: pointer; accent-color: #27ae60; margin: 10px 0; }
.range-labels { display: flex; justify-content: space-between; font-size: 0.8em; color: #7f8c8d; }

/* Grid y Card */
.marketplace-container { padding: 20px 40px; max-width: 100%; margin: 0 auto; }
.products-grid { display: grid; gap: 30px; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); }
@media (min-width: 1024px) { .products-grid { grid-template-columns: repeat(3, 1fr); } }

.product-card { border: 1px solid #eee; border-radius: 10px; overflow: hidden; background: white; box-shadow: 0 2px 8px rgba(0,0,0,0.05); transition: transform 0.2s; }
.product-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }

.image-container { height: 250px; overflow: hidden; position: relative; }
.image-container.is-clickable { cursor: pointer; }
.image-container.is-clickable:hover .product-img { transform: scale(1.05); transition: transform 0.3s ease; }
.product-img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s ease; }

.stock-badge { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em; }
.no-stock { background: #e74c3c; }

.card-body { padding: 20px; }

/* Estilos extra para info vendedor */
.seller-info { margin-bottom: 10px; display: flex; flex-direction: column; gap: 4px; }
.seller-name { font-size: 0.95em; color: #555; }
.location-badge { font-size: 0.85em; color: #666; background: #f0f0f0; padding: 2px 6px; border-radius: 4px; width: fit-content; }

.price { font-size: 1.5em; font-weight: bold; color: #27ae60; }
.btn-buy { width: 100%; background: #27ae60; color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 15px; font-size: 1rem; }
.btn-buy:disabled { background: #ccc; cursor: not-allowed; }

/* Modal */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 1000; }
.modal-content { background: white; padding: 25px; border-radius: 10px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; }
.quantity-section { background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: center; border: 1px solid #eee; }
.qty-input { padding: 8px; width: 80px; text-align: center; font-size: 1.2em; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px; }
.total-price { margin-top: 10px; font-size: 1.1em; color: #333; }
.pickup-section { margin-bottom: 20px; }
.points-list { max-height: 200px; overflow-y: auto; border: 1px solid #eee; border-radius: 5px; }
.point-option { display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; }
.point-option:hover { background: #f0fdf4; }
.point-option input { margin-right: 10px; }
.point-details { display: flex; flex-direction: column; }
.point-name { font-weight: bold; font-size: 0.95em; }
.point-city { font-size: 0.85em; color: #666; }
.no-points { padding: 15px; background: #fff3cd; color: #856404; border-radius: 5px; text-align: center; }
.modal-actions { display: flex; justify-content: space-between; margin-top: 20px; }
.btn-cancel { background: #e74c3c; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
.btn-confirm { background: #3490dc; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; }
.btn-confirm:disabled { background: #a0aec0; cursor: not-allowed; }
</style>