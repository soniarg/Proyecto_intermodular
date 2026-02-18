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

const openPurchaseModal = (product) => {
    if (!localStorage.getItem('auth_token')) {
        if (confirm("Necesitas iniciar sesión. ¿Ir al Login?")) router.push('/login');
        return;
    }
    selectedProduct.value = product;
    showModal.value = true;
};
const closeModal = () => { showModal.value = false; selectedProduct.value = null; };
</script>

<template>
    <div class="marketplace-container">
        <h2 class="title">Mercado de Proximidad</h2>
        <div class="filters-wrapper">
            <input v-model="searchQuery" type="text" placeholder="🔍 Buscar" class="form-control" />
            <input v-model="searchCity" type="text" placeholder="📍 Ciudad..." class="form-control" />
            <div class="price-filter">
                <label>Precio máx: {{ maxPrice }}€</label>
                <input type="range" v-model.number="maxPrice" :min="0" :max="maxPriceLimit" step="0.5" class="range-slider" />
            </div>
        </div>

        <div v-if="loading" class="loading">Cargando...</div>
        <div v-else class="products-grid">
            <div v-for="product in filteredProducts" :key="product.id" class="product-card">
                <div class="image-container" @click="openPurchaseModal(product)">
                    <img :src="product.image_url ? (product.image_url.startsWith('http') ? product.image_url : BASE_URL + product.image_url) : 'https://via.placeholder.com/300'" class="product-img">
                </div>
                <div class="card-body">
                    <h3>{{ product.title }}</h3>
                    <div class="seller-compact" @click.stop="openSellerProfile(product.seller_id)">
                        <img :src="getAvatarUrl(product.seller) || appLogo" class="mini-avatar">
                        <span class="seller-link">{{ product.seller?.store_name || 'Vendedor' }}</span>
                    </div>
                    <p class="price">{{ product.price }}€</p>
                    <button @click="openPurchaseModal(product)" class="btn-buy" :disabled="product.stock <= 0">Comprar</button>
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
                        <h2>{{ sellerInfo?.store_name }}</h2>
                        <div class="avg-stars">⭐ {{ averageRating }} / 5</div>
                    </div>

                    <div class="profile-scroll-area">
                        <p class="desc">{{ sellerInfo?.description || 'Productor local de confianza.' }}</p>
                        <hr>
                        <h4>Otros productos:</h4>
                        <div class="mini-gallery">
                            <div v-for="p in sellerProducts" :key="p.id" class="mini-prod">
                                <img :src="p.image_url ? (p.image_url.startsWith('http') ? p.image_url : BASE_URL + p.image_url) : 'https://via.placeholder.com/80'">
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
.marketplace-container { padding: 20px; max-width: 1200px; margin: 0 auto; }
.filters-wrapper { display: flex; gap: 15px; margin-bottom: 30px; flex-wrap: wrap; background: #f4f4f4; padding: 15px; border-radius: 8px; }
.form-control { padding: 10px; border-radius: 5px; border: 1px solid #ddd; flex: 1; }

.products-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); gap: 20px; }
.product-card { border: 1px solid #eee; border-radius: 10px; overflow: hidden; background: white; transition: 0.3s; }
.product-card:hover { transform: translateY(-5px); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
.image-container { height: 200px; cursor: pointer; }
.product-img { width: 100%; height: 100%; object-fit: cover; }
.card-body { padding: 15px; }
.price { font-size: 1.2rem; font-weight: bold; color: #27ae60; }
.btn-buy { width: 100%; padding: 10px; background: #27ae60; color: white; border: none; border-radius: 5px; cursor: pointer; }

/* --- MODAL VENDEDOR --- */
.modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.8); display: flex; justify-content: center; align-items: center; z-index: 9999;
}

.profile-modal {
    background: white; width: 95%; max-width: 500px; height: 85vh;
    border-radius: 15px; overflow: hidden; position: relative;
    display: flex; flex-direction: column;
}

.profile-content-wrapper { display: flex; flex-direction: column; height: 100%; }

/* --- ESTILO TIPO FOTO DE PERFIL CIRCULAR --- */
.profile-avatar-wrapper { 
    width: 100%; 
    height: 180px; 
    flex-shrink: 0; 
    background: #fdfdfd;      /* Fondo neutro */
    display: flex; 
    justify-content: center; 
    align-items: center;
}

.avatar-circle {
    width: 140px;
    height: 140px;
    border-radius: 50%;
    overflow: hidden;
    border: 4px solid #27ae60; /* Borde verde característico */
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    background: white;
    display: flex;
    justify-content: center;
    align-items: center;
}

.profile-banner { 
    width: 100%;
    height: 100%;
    object-fit: cover; /* Asegura que la foto llene el círculo */
}
/* ------------------------------------------- */

.profile-header { padding: 15px 20px; border-bottom: 1px solid #eee; flex-shrink: 0; text-align: center; }
.profile-header h2 { margin: 0; font-size: 1.4rem; }
.avg-stars { color: #f1c40f; font-weight: bold; margin-top: 5px; }

.profile-scroll-area { 
    flex: 1; overflow-y: auto !important; padding: 20px;
    -webkit-overflow-scrolling: touch; 
}

.mini-gallery { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; }
.mini-prod img { width: 80px; height: 80px; object-fit: cover; border-radius: 5px; }

.review-box { background: #f9f9f9; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #27ae60; }
.comment { font-style: italic; margin-top: 5px; word-break: break-word; }

.close-btn {
    position: absolute; top: 10px; right: 10px; width: 35px; height: 35px;
    background: white; border-radius: 50%; border: none; font-size: 20px;
    cursor: pointer; z-index: 100; box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.seller-compact { display: flex; align-items: center; gap: 10px; cursor: pointer; margin-bottom: 10px; }
.mini-avatar { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; }
.seller-link { font-weight: bold; color: #27ae60; text-decoration: underline; }

@media (max-width: 600px) {
    .profile-modal { height: 90vh; }
    .profile-avatar-wrapper { height: 150px; }
    .avatar-circle { width: 110px; height: 110px; }
}
</style>