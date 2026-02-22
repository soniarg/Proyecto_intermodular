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

// --- BLOQUEAR SCROLL ---
const isAnyModalOpen = computed(() => showModal.value || showSellerModal.value);

watch(isAnyModalOpen, (isOpen) => {
    if (isOpen) {
        document.body.style.overflow = 'hidden';
        document.documentElement.style.overflow = 'hidden';
    } else {
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';
    }
});

onUnmounted(() => {
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
});

// --- HELPERS ---
const getAvatarUrl = (sellerData) => {
    if (!sellerData) return null;
    let avatar = sellerData.injected_avatar_url || sellerData.avatar_url || sellerData.user?.avatar_url;
    if (avatar) {
        return avatar.startsWith('http') ? avatar : BASE_URL + avatar;
    }
    return null;
};

const filteredProducts = computed(() => {
    return products.value.filter(product => {
        const prodTitle = product.title ? product.title.toLowerCase() : '';
        const matchesName = prodTitle.includes(searchQuery.value.toLowerCase());
        const matchesPrice = parseFloat(product.price) <= maxPrice.value;
        let matchesCity = true;
        if (searchCity.value.trim() !== '') {
            const busqueda = searchCity.value.toLowerCase();
            const puntos = product.seller?.pickup_points || [];
            matchesCity = puntos.some(punto => punto.city && punto.city.toLowerCase().includes(busqueda));
        }
        return matchesName && matchesPrice && matchesCity;
    });
});

// --- LÓGICA VENDEDOR ---
const openSellerProfile = async (sellerId) => {
    loadingSeller.value = true;
    showSellerModal.value = true;
    const prod = products.value.find(p => p.seller_id === sellerId);
    let basicSellerInfo = prod ? prod.seller : {};
    try {
        const userResponse = await api.get(`/users/${sellerId}`);
        sellerInfo.value = { ...basicSellerInfo, avatar_url: userResponse.data.avatar_url, seller_id: sellerId };
        const reviewsResponse = await api.get(`/users/${sellerId}/reviews`);
        sellerReviews.value = reviewsResponse.data.data || reviewsResponse.data;
    } catch (error) {
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
        } catch (e) { }
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
            // Pre-cargar puntos de recogida (Opcional, pero lo mantengo de la versión anterior para búsquedas por ciudad)
            const sellerIds = [...new Set(products.value.map(p => p.seller_id))];
            for (const sellerId of sellerIds) {
                try {
                    const pointsResponse = await api.get(`/seller/pickup-points/${sellerId}`);
                    products.value.forEach(product => {
                        if (product.seller_id === sellerId && product.seller) {
                            product.seller.pickup_points = pointsResponse.data.data || pointsResponse.data;
                        }
                    });
                } catch (err) { }
            }
        }
    } finally { loading.value = false; }
});

// --- RECUPERADA: LÓGICA DE COMPRA ---
const openPurchaseModal = async (product) => {
    const token = localStorage.getItem('auth_token');
    if (!token) {
        if (confirm("Necesitas iniciar sesión para comprar. ¿Ir al Login?")) router.push('/login');
        return;
    }

    selectedProduct.value = product;
    selectedQuantity.value = product.selectedQuantity || 1; // Usamos la cantidad seleccionada en la tarjeta
    selectedPickupId.value = null;
    showModal.value = true;
    loadingPoints.value = true;
    pickupPoints.value = [];

    try {
        const sellerId = product.seller_id || product.seller?.id;
        const response = await api.get(`/seller/pickup-points/${sellerId}`);
        pickupPoints.value = response.data.data || response.data;
    } catch (error) {
        console.error("Error cargando puntos:", error);
        toast.error("Error al cargar los puntos de recogida.");
    } finally {
        loadingPoints.value = false;
    }
};

const closeModal = () => { 
    showModal.value = false; 
    selectedProduct.value = null; 
};

const confirmPurchase = async () => {
    if (!selectedPickupId.value) {
        toast.warning("Por favor, selecciona un punto de entrega.");
        return;
    }

    // 🛑 NUEVO: BARRERA VISUAL PARA EL STOCK
    if (selectedQuantity.value > selectedProduct.value.stock) {
        toast.error(`¡Vaya! Solo quedan ${selectedProduct.value.stock} unidades de este producto.`);
        // Autocorregimos el valor para ayudar al usuario
        selectedQuantity.value = selectedProduct.value.stock;
        return; 
    }

    submitting.value = true;

    try {
        await api.post(`/orders`, {
            product_id: selectedProduct.value.id,
            quantity: selectedQuantity.value,
            pickup_id: selectedPickupId.value 
        });

        toast.success("¡Pedido realizado con éxito! 🎉");
        closeModal();
        router.push('/my-purchases'); 

    } catch (error) {
        console.error(error);
        toast.error(error.response?.data?.message || "Error al realizar el pedido.");
    } finally {
        submitting.value = false;
    }
};

</script>

<template>
    <div class="marketplace-container">
        <h2 class="title">🍏 Mercado de Proximidad</h2>
        <p class="subtitle" style="text-align: center; color: #666; margin-bottom: 20px;">Productos frescos directos del agricultor a tu mesa.</p>

        <div class="filters-wrapper">
            <input v-model="searchQuery" type="text" placeholder="🔍 Buscar (ej: Miel, Tomates...)" class="form-control" />
            <input v-model="searchCity" type="text" placeholder="📍 Ciudad..." class="form-control" />
            <div class="price-filter">
                <label>Precio máx: <strong>{{ maxPrice }}€</strong></label>
                <input type="range" v-model.number="maxPrice" :min="0" :max="maxPriceLimit" step="0.5" class="range-slider" />
            </div>
            <button @click="resetFilters" class="btn-clear" style="padding: 10px; border-radius: 5px; border: 1px solid #ccc; cursor: pointer;">🔄 Limpiar</button>
        </div>

        <div v-if="loading" class="loading" style="text-align: center; padding: 40px; font-size: 1.2rem;">Cargando productos frescos...</div>
        
        <div v-else>
            <div v-if="filteredProducts.length === 0" style="text-align: center; padding: 40px;">
                <p>No hay productos disponibles con esos filtros.</p>
            </div>

            <div v-else class="products-grid">
                <div v-for="product in filteredProducts" :key="product.id" class="product-card">
                    <div class="image-container" @click="openPurchaseModal(product)">
                        <img :src="product.image_url ? (product.image_url.startsWith('http') ? product.image_url : BASE_URL + product.image_url) : 'public/no-disponible.jpg'" class="product-img">
                        <span class="stock-badge" v-if="product.stock > 0">Stock: {{ product.stock }}</span>
                        <span class="stock-badge no-stock" v-else>Agotado</span>
                    </div>
                    <div class="card-body">
                        <h3>{{ product.title }}</h3>
                        <div class="seller-compact" @click.stop="openSellerProfile(product.seller_id)">
                            <img :src="getAvatarUrl(product.seller) || appLogo" class="mini-avatar">
                            <span class="seller-link">{{ product.seller?.store_name || product.seller?.name || 'Vendedor' }}</span>
                        </div>
                        
                        <div class="price-row" style="display: flex; align-items: baseline; margin-bottom: 10px;">
                            <span class="price">{{ product.price }}€</span>
                            <span class="unit" style="color: #666; margin-left: 5px;">/ {{ product.unit || 'ud' }}</span>
                        </div>

                        <div v-if="product.stock > 0" class="add-to-cart-wrapper">
                            <input 
                                type="number"
                                v-model="product.selectedQuantity"
                                min="1"
                                :max="product.stock"
                                placeholder="1"
                                class="qty-inline-input"
                            >
                            <button @click="openPurchaseModal(product)" class="btn-buy">Reservar</button>
                        </div>
                        <button v-else class="btn-buy" disabled>Agotado</button>
                    </div>
                </div>
            </div>
        </div>

        <div v-if="showModal" class="modal-overlay" @click.self="closeModal">
            <div class="modal-content profile-modal" style="height: auto; max-height: 90vh; overflow-y: auto;">
                <button class="close-btn" @click="closeModal">×</button>
                
                <h3 style="margin-top: 10px;">📍 Elige dónde recogerlo</h3>
                <p>Estás comprando: <strong>{{ selectedProduct?.title }}</strong></p>

                <div class="quantity-section">
                    <label>Cantidad ({{ selectedProduct?.unit || 'ud' }}):</label>
                    <div class="qty-control">
                        <input type="number" v-model="selectedQuantity" min="1" :max="selectedProduct?.stock" class="qty-input">
                    </div>
                    <p class="total-price">
                        Total estimado: <strong style="color: #27ae60; font-size: 1.2em;">{{ (selectedProduct?.price * selectedQuantity).toFixed(2) }}€</strong>
                    </p>
                </div>

                <div class="pickup-section">
                    <h4 style="margin-bottom:10px; font-size:1em;">Punto de entrega:</h4>
                    
                    <div v-if="loadingPoints" class="loading-spinner" style="text-align: center; padding: 20px;">Cargando puntos...</div>

                    <div v-else-if="pickupPoints.length === 0" class="no-points">
                        <p>⚠️ Este vendedor no tiene puntos configurados.</p>
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

                <div class="modal-actions" style="margin-bottom: 20px;">
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

        <div v-if="showSellerModal" class="modal-overlay" @click.self="showSellerModal = false">
            <div class="profile-modal">
                <button class="close-btn" @click="showSellerModal = false">×</button>
                
                <div class="profile-content-wrapper">
                    <div class="profile-avatar-wrapper">
                        <div class="avatar-circle">
                            <img :src="getAvatarUrl(sellerInfo) || appLogo" class="profile-banner">
                        </div>
                    </div>

                    <div class="profile-header">
                        <h2>{{ sellerInfo?.store_name || sellerInfo?.name }}</h2>
                        <div class="avg-stars">⭐ {{ averageRating }} / 5</div>
                    </div>

                    <div class="profile-scroll-area">
                        <p class="desc">{{ sellerInfo?.description || 'Productor local de confianza.' }}</p>
                        <hr>
                        <h4>Otros productos:</h4>
                        <div class="mini-gallery">
                            <div v-for="p in sellerProducts" :key="p.id" class="mini-prod">
                                <img :src="p.image_url ? (p.image_url.startsWith('http') ? p.image_url : BASE_URL + p.image_url) : 'https://placehold.co/80'">
                                <p>{{ p.price }}€</p>
                            </div>
                        </div>
                        <hr>
                        <h4>Valoraciones:</h4>
                        <div v-if="sellerReviews.length === 0">Sin reseñas aún.</div>
                        <div v-for="rev in sellerReviews" :key="rev.id" class="review-box">
                            <div class="stars">{{ '⭐'.repeat(rev.rating) }}</div>
                            <p class="comment">"{{ rev.comment }}"</p>
                        </div>
                        <div style="height: 50px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* --- ESTILOS UNIFICADOS --- */
.marketplace-container { padding: 20px; max-width: 1200px; margin: 0 auto; }
.filters-wrapper { display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap; background: #f4f4f4; padding: 15px; border-radius: 8px; align-items: center; }
.form-control { padding: 10px; border-radius: 5px; border: 1px solid #ddd; flex: 1; min-width: 200px; }
.price-filter { flex: 0 0 250px; display: flex; flex-direction: column; }
.range-slider { width: 100%; cursor: pointer; accent-color: #27ae60; margin-top: 5px; }

.products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
.product-card { border: 1px solid #eee; border-radius: 10px; overflow: hidden; background: white; transition: 0.3s; position: relative; }
.product-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
.image-container { height: 200px; cursor: pointer; position: relative; }
.product-img { width: 100%; height: 100%; object-fit: cover; }
.stock-badge { position: absolute; top: 10px; right: 10px; background: rgba(0,0,0,0.7); color: white; padding: 4px 8px; border-radius: 4px; font-size: 0.8em; z-index: 10; }
.no-stock { background: #e74c3c; }

.card-body { padding: 15px; }
.price { font-size: 1.4em; font-weight: bold; color: #27ae60; }
.seller-compact { display: flex; align-items: center; gap: 10px; cursor: pointer; margin: 10px 0; }
.mini-avatar { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; }
.seller-link { font-weight: bold; color: #27ae60; text-decoration: underline; font-size: 0.9em; }

/* Carrito en línea */
.add-to-cart-wrapper { display: flex; gap: 10px; margin-top: 15px; }
.qty-inline-input { width: 60px; text-align: center; border: 1px solid #ddd; border-radius: 5px; font-size: 1.1em; }
.btn-buy { flex: 1; padding: 10px; background: #27ae60; color: white; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
.btn-buy:disabled { background: #95a5a6; cursor: not-allowed; width: 100%; margin-top: 15px; }

/* --- MODAL ESTILOS --- */
.modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.8); display: flex; justify-content: center; align-items: center; z-index: 9999;
}
.profile-modal { background: white; width: 95%; max-width: 500px; height: 85vh; border-radius: 15px; overflow: hidden; position: relative; display: flex; flex-direction: column; }
.modal-content { padding: 25px; }
.close-btn { position: absolute; top: 10px; right: 10px; width: 35px; height: 35px; background: white; border-radius: 50%; border: none; font-size: 20px; cursor: pointer; z-index: 100; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }

/* --- MODAL DE COMPRA ESPECÍFICO --- */
.quantity-section { background: #f9f9f9; padding: 15px; border-radius: 8px; margin: 15px 0; text-align: center; border: 1px solid #eee; }
.qty-input { padding: 8px; width: 80px; text-align: center; font-size: 1.2em; border: 1px solid #ddd; border-radius: 5px; margin-top: 5px; }
.pickup-section { margin-bottom: 20px; }
.points-list { max-height: 200px; overflow-y: auto; border: 1px solid #eee; border-radius: 5px; }
.point-option { display: flex; align-items: center; padding: 10px; border-bottom: 1px solid #eee; cursor: pointer; }
.point-option:hover { background: #f0fdf4; }
.point-option input { margin-right: 10px; }
.point-details { display: flex; flex-direction: column; text-align: left; }
.point-name { font-weight: bold; font-size: 0.95em; }
.point-city { font-size: 0.85em; color: #666; }
.no-points { padding: 15px; background: #fff3cd; color: #856404; border-radius: 5px; text-align: center; }
.modal-actions { display: flex; justify-content: space-between; margin-top: 20px; gap: 10px; }
.btn-cancel { background: #e74c3c; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; flex: 1; }
.btn-confirm { background: #3490dc; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer; flex: 1; font-weight: bold; }
.btn-confirm:disabled { background: #a0aec0; cursor: not-allowed; }

/* --- PERFIL VENDEDOR ESPECÍFICO --- */
.profile-content-wrapper { display: flex; flex-direction: column; height: 100%; }
.profile-avatar-wrapper { width: 100%; height: 180px; flex-shrink: 0; background: #fdfdfd; display: flex; justify-content: center; align-items: center; }
.avatar-circle { width: 140px; height: 140px; border-radius: 50%; overflow: hidden; border: 4px solid #27ae60; box-shadow: 0 4px 12px rgba(0,0,0,0.15); background: white; display: flex; justify-content: center; align-items: center; }
.profile-banner { width: 100%; height: 100%; object-fit: cover; }
.profile-header { padding: 15px 20px; border-bottom: 1px solid #eee; flex-shrink: 0; text-align: center; }
.profile-header h2 { margin: 0; font-size: 1.4rem; }
.avg-stars { color: #f1c40f; font-weight: bold; margin-top: 5px; }
.profile-scroll-area { flex: 1; overflow-y: auto !important; padding: 20px; -webkit-overflow-scrolling: touch; }
.mini-gallery { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; }
.mini-prod img { width: 80px; height: 80px; object-fit: cover; border-radius: 5px; }
.review-box { background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #27ae60; }
.comment { font-style: italic; margin-top: 5px; word-break: break-word; }

@media (max-width: 600px) {
    .profile-modal { height: 90vh; }
    .profile-avatar-wrapper { height: 150px; }
    .avatar-circle { width: 110px; height: 110px; }
}
</style>