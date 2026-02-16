<script setup>
import { ref, onMounted, computed, watch, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api/axios.js';
import { useToast } from 'vue-toastification';

// IMPORTAMOS TU LOGO
import appLogo from '../assets/logo.png'; 

const products = ref([]);
const loading = ref(true);
const router = useRouter();
const toast = useToast();

const BASE_URL = 'http://localhost:8000/storage/'; 

// Variables Modal Compra
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
const currentUserId = ref(null);

// Variables Perfil Vendedor
const showSellerModal = ref(false);
const sellerInfo = ref(null);
const sellerReviews = ref([]);
const loadingSeller = ref(false);

// --- BLOQUEAR SCROLL (SOLUCIÓN DEFINITIVA) ---
// Propiedad computada que es true si CUALQUIER modal está abierto
const isAnyModalOpen = computed(() => showModal.value || showSellerModal.value);

watch(isAnyModalOpen, (isOpen) => {
    if (isOpen) {
        // Bloqueamos tanto el body como el html para asegurar que no se mueve
        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
    } else {
        // Devolvemos el scroll
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';
    }
});

// Al salir de la página, aseguramos que el scroll vuelve a funcionar
onUnmounted(() => {
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
});

// --- FUNCIÓN HELPER PARA LA FOTO ---
const getAvatarUrl = (sellerData) => {
    if (!sellerData) return null;
    let avatar = sellerData.injected_avatar_url;
    if (!avatar) {
         avatar = sellerData.avatar_url || sellerData.user?.avatar_url;
    }
    if (avatar) {
        return avatar.startsWith('http') ? avatar : BASE_URL + avatar;
    }
    return null;
};

// --- COMPUTED: FILTRADO ---
const filteredProducts = computed(() => {
  return products.value.filter(product => {
    const prodTitle = product.title ? product.title.toLowerCase() : '';
    const matchesName = prodTitle.includes(searchQuery.value.toLowerCase());
    const matchesPrice = parseFloat(product.price) <= maxPrice.value;
    
    let matchesCity = true; 
    if (searchCity.value.trim() !== '') {
        const busqueda = searchCity.value.toLowerCase();
        const puntos = product.seller?.pickup_points || [];
        matchesCity = puntos.some(punto => 
            punto.city && punto.city.toLowerCase().includes(busqueda)
        );
    }
    return matchesName && matchesPrice && matchesCity;
  });
});

// --- LÓGICA PERFIL VENDEDOR ---
const openSellerProfile = async (sellerId) => {
    loadingSeller.value = true;
    showSellerModal.value = true; // Esto activará el watcher y bloqueará el scroll
    
    const prod = products.value.find(p => p.seller_id === sellerId);
    let basicSellerInfo = prod ? prod.seller : {};

    try {
        const userResponse = await api.get(`/users/${sellerId}`);
        const userData = userResponse.data; 

        sellerInfo.value = {
            ...basicSellerInfo,
            avatar_url: userData.avatar_url,
            seller_id: sellerId
        };

        const reviewsResponse = await api.get(`/users/${sellerId}/reviews`);
        sellerReviews.value = reviewsResponse.data.data || reviewsResponse.data;

    } catch (error) {
        console.error("Error cargando perfil:", error);
        sellerInfo.value = { ...basicSellerInfo, seller_id: sellerId };
        sellerReviews.value = [];
    } finally {
        loadingSeller.value = false;
    }
};

const sellerProducts = computed(() => {
    if (!sellerInfo.value) return [];
    return products.value.filter(p => p.seller_id === sellerInfo.value.seller_id);
});

const averageRating = computed(() => {
    if (sellerReviews.value.length === 0) return 0;
    const sum = sellerReviews.value.reduce((acc, rev) => acc + rev.rating, 0);
    return (sum / sellerReviews.value.length).toFixed(1);
});

// --- RESETEAR FILTROS ---
const resetFilters = () => {
    searchQuery.value = '';
    searchCity.value = '';
    maxPrice.value = maxPriceLimit.value; 
};

// --- CARGA INICIAL ---
onMounted(async () => {
  loading.value = true;
  try {
    try {
        const userResponse = await api.get('/user');
        currentUserId.value = userResponse.data.id;
    } catch (e) {}

    const response = await api.get('/products');
    let incomingData = response.data.data || response.data;

    if (Array.isArray(incomingData)) {
        if (currentUserId.value) {
            incomingData = incomingData.filter(product => product.seller_id !== currentUserId.value);
        }
        products.value = incomingData;

        if (products.value.length > 0) {
            const highest = Math.max(...products.value.map(p => parseFloat(p.price)));
            maxPriceLimit.value = Math.ceil(highest); 
            maxPrice.value = maxPriceLimit.value;     
        }

        const sellerIds = [...new Set(products.value.map(p => p.seller_id))];

        for (const sellerId of sellerIds) {
             try {
                 const pointsResponse = await api.get(`/seller/pickup-points/${sellerId}`);
                 const pointsData = pointsResponse.data.data || pointsResponse.data;
                 products.value.forEach(product => {
                     if (product.seller_id === sellerId && product.seller) {
                         product.seller.pickup_points = pointsData;
                     }
                 });
             } catch (err) {}
        }

        for (const sellerId of sellerIds) {
            try {
                const userResp = await api.get(`/users/${sellerId}`);
                const avatarUrl = userResp.data.avatar_url;

                if (avatarUrl) {
                    products.value.forEach(product => {
                        if (product.seller_id === sellerId && product.seller) {
                            product.seller.injected_avatar_url = avatarUrl;
                        }
                    });
                }
            } catch (err) {}
        }
    }
  } catch (error) {
    console.error("Error cargando marketplace:", error);
  } finally {
    loading.value = false;
  }
});

// --- MODAL COMPRA ---
const openPurchaseModal = async (product) => {
    const token = localStorage.getItem('auth_token');
    if (!token) {
        if(confirm("Necesitas iniciar sesión. ¿Ir al Login?")) router.push('/login');
        return;
    }
    selectedProduct.value = product;
    selectedQuantity.value = 1;
    selectedPickupId.value = null;
    showModal.value = true; // Esto activará el watcher y bloqueará el scroll
    loadingPoints.value = true; 
    pickupPoints.value = [];    
    try {
        if (product.seller && product.seller.pickup_points) {
             pickupPoints.value = product.seller.pickup_points;
        } else {
            const sellerId = product.seller_id || product.seller?.id; 
            const response = await api.get(`/seller/pickup-points/${sellerId}`);
            pickupPoints.value = response.data.data || response.data;
        }
        loadingPoints.value = false;
    } catch (error) {
        loadingPoints.value = false;
    }
};

const closeModal = () => { showModal.value = false; selectedProduct.value = null; };

const confirmPurchase = async () => {
    if (!selectedPickupId.value) return;
    submitting.value = true;
    try {
        await api.post(`/seller/orders/${selectedProduct.value.id}/store`, {
            quantity: selectedQuantity.value,
            pickup_id: selectedPickupId.value
        });
        toast.success("¡Pedido realizado con éxito!");
        closeModal();
        router.push('/my-purchases'); 
    } catch (error) {
        toast.error("Error al realizar el pedido.");
    } finally { submitting.value = false; }
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
            
            <div class="seller-compact" @click.stop="openSellerProfile(product.seller_id)">
                <img 
                    :src="getAvatarUrl(product.seller) || appLogo" 
                    class="mini-avatar" 
                    alt="Vendedor"
                >
                <div class="seller-text">
                    <span class="seller-link">{{ product.seller?.store_name || ('Vendedor #' + product.seller_id) }}</span>
                    <span v-if="product.seller?.pickup_points?.length > 0" class="location">
                       📍 {{ product.seller.pickup_points[0].city }}
                    </span>
                </div>
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
               <button @click="confirmPurchase" class="btn-confirm" :disabled="!selectedPickupId || submitting">
                   {{ submitting ? 'Procesando...' : 'Confirmar Pedido' }}
               </button>
           </div>
       </div>
    </div>

    <div v-if="showSellerModal" class="modal-overlay" @click.self="showSellerModal = false">
        <div class="modal-content profile-modal">
            <button class="close-btn" @click="showSellerModal = false">×</button>
            <div v-if="loadingSeller" class="loading">Cargando perfil...</div>
            <div v-else-if="sellerInfo">
                
                <div class="profile-banner-container">
                    <img 
                        :src="getAvatarUrl(sellerInfo) || appLogo" 
                        class="profile-banner"
                        alt="Foto de Perfil"
                    >
                </div>
                
                <div class="profile-header">
                    <h2>{{ sellerInfo.store_name }}</h2>
                    <div class="avg-stars">
                        <span v-for="i in 5" :key="i">{{ i <= Math.round(averageRating) ? '⭐' : '☆' }}</span>
                        <small>({{ averageRating }} / 5)</small>
                    </div>
                </div>

                <div class="profile-body">
                    <p class="desc">{{ sellerInfo.description || 'Productor local de confianza.' }}</p>
                    <hr>
                    <h4>Otros productos:</h4>
                    <div class="mini-gallery">
                        <div v-for="p in sellerProducts" :key="p.id" class="mini-prod">
                            <img :src="p.image_url ? (p.image_url.startsWith('http') ? p.image_url : BASE_URL + p.image_url) : 'https://via.placeholder.com/80'" class="mini-img">
                            <p>{{ p.price }}€</p>
                        </div>
                    </div>
                    <hr>
                    <h4>Valoraciones:</h4>
                    <div v-if="sellerReviews.length === 0" class="no-reviews">Aún no tiene reseñas.</div>
                    <div v-for="rev in sellerReviews" :key="rev.id" class="review-box">
                        <div class="review-stars">{{ '⭐'.repeat(rev.rating) }}</div>
                        <p class="review-comment">"{{ rev.comment }}"</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
  </div>
</template>

<style scoped>
/* ESTILOS ORIGINALES */
.filters-wrapper { display: flex; flex-wrap: wrap; gap: 20px; justify-content: center; align-items: center; background: #f8f9fa; padding: 20px; border-radius: 10px; margin-bottom: 30px; }
.search-box { flex: 1; min-width: 250px; }
.city-filter { flex: 1; min-width: 200px; } 
.form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
.price-filter { flex: 0 0 250px; display: flex; flex-direction: column; }
.range-slider { width: 100%; cursor: pointer; accent-color: #27ae60; margin: 10px 0; }
.range-labels { display: flex; justify-content: space-between; font-size: 0.8em; color: #7f8c8d; }
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
.price { font-size: 1.5em; font-weight: bold; color: #27ae60; }
.btn-buy { width: 100%; background: #27ae60; color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; font-weight: bold; margin-top: 15px; font-size: 1rem; }
.btn-buy:disabled { background: #ccc; cursor: not-allowed; }
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; justify-content: center; align-items: center; z-index: 1000; }
.modal-content { background: white; padding: 25px; border-radius: 10px; width: 90%; max-width: 500px; max-height: 90vh; overflow-y: auto; position: relative; }
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

/* --- ESTILOS VENDEDOR --- */
.seller-compact { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; cursor: pointer; padding: 5px; border-radius: 8px; transition: background 0.2s; background: #f9f9f9; }
.seller-compact:hover { background: #eef9f2; }
.mini-avatar { width: 35px; height: 35px; border-radius: 50%; object-fit: cover; border: 2px solid #27ae60; background: white; }
.seller-text { display: flex; flex-direction: column; }
.seller-link { font-weight: bold; color: #27ae60; text-decoration: underline; font-size: 0.9em; }
.location { font-size: 0.8em; color: #777; }

/* --- ESTILOS MODAL PERFIL --- */
.profile-modal { padding: 0 !important; overflow: hidden; max-width: 550px; border-radius: 15px !important; }
.profile-banner-container { 
    width: 100%; 
    height: 180px; 
    overflow: hidden; 
    background: #f0f0f0; 
    display: flex;
    justify-content: center;
    align-items: center;
}
.profile-banner { 
    width: 100%; 
    height: 100%; 
    object-fit: contain; 
    object-position: center;
}

.profile-header { padding: 15px 25px; border-bottom: 1px solid #eee; background: white; }
.avg-stars { color: #f1c40f; font-size: 1.1em; margin-top: 5px; }

/* PADDING INFERIOR AÑADIDO */
.profile-body { padding: 25px; padding-bottom: 50px; max-height: 450px; overflow-y: auto; }

.desc { font-style: italic; color: #555; margin-bottom: 20px; line-height: 1.5; }
.mini-gallery { display: flex; gap: 12px; overflow-x: auto; padding: 10px 0; }
.mini-prod { flex: 0 0 90px; text-align: center; }
.mini-prod img { width: 90px; height: 70px; object-fit: cover; border-radius: 8px; border: 1px solid #eee; background: #f0f0f0; }
.mini-prod p { font-weight: bold; margin-top: 5px; color: #27ae60; font-size: 0.9em; }
.review-box { background: #f8fafc; padding: 15px; border-radius: 10px; margin-bottom: 12px; border-left: 5px solid #27ae60; }
.review-stars { color: #f1c40f; margin-bottom: 5px; }
.review-comment { font-size: 0.9em; color: #333; }
.close-btn { position: absolute; top: 15px; right: 15px; font-size: 30px; border: none; background: rgba(255,255,255,0.9); border-radius: 50%; width: 40px; height: 40px; cursor: pointer; z-index: 100; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
</style>