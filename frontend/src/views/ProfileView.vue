<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/api/axios';
import StarRating from '@/components/StarRating.vue';
import { useToast } from 'vue-toastification';

const router = useRouter();
const toast = useToast();
const BASE_URL = 'http://localhost:8000/storage/';

const user = ref(null);
const loading = ref(true);
const isEditing = ref(false);
const showSellerModal = ref(false);
const showReviewsModal = ref(false);
const imagePreview = ref(null);
const fileInput = ref(null);

const form = ref({
    name: '',
    surname: '',
    email: '',
    avatar_file: null
});

const sellerForm = ref({
    store_name: '',
    nif: '',
    description: ''
});

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
};

const resetForm = () => {
    if (user.value) {
        form.value.name = user.value.name;
        form.value.surname = user.value.surname || '';
        form.value.email = user.value.email;
        form.value.avatar_file = null;
        imagePreview.value = null;
    }
};

const triggerFileInput = () => fileInput.value.click();
const canShowRating = (role) => ['seller', 'vendedor', 'buyer', 'comprador', 'admin'].includes(role);

const fetchUser = async () => {
    try {
        loading.value = true;
        const response = await api.get('/user');
        user.value = response.data;
        
        user.value.reviews = []; 
        user.value.reviews_count = 0;
        user.value.average_rating = 0;
        user.value.total_orders = 0;     
        user.value.completed_orders = 0; 

        if (user.value.id) {
            try {
                const reviewsRes = await api.get(`/users/${user.value.id}/reviews`);
                const reviewsData = reviewsRes.data.data || reviewsRes.data || [];
                if (Array.isArray(reviewsData)) user.value.reviews = reviewsData;
            } catch (err) {}
        }

        try {
            const endpoint = ['seller', 'vendedor'].includes(user.value.role) ? '/seller/orders/history' : '/my-orders';
            const ordersRes = await api.get(endpoint);
            const orders = ordersRes.data.data || ordersRes.data || [];
            user.value.total_orders = orders.length;
            user.value.completed_orders = orders.filter(o => o.status === 'completed').length;
        } catch (err) {}

        if (user.value.reviews.length > 0) {
            user.value.reviews_count = user.value.reviews.length;
            const sum = user.value.reviews.reduce((acc, r) => acc + Number(r.rating), 0);
            const avg = sum / user.value.reviews.length;
            user.value.average_rating = avg.toFixed(1);
        }

        resetForm(); 
    } catch (error) {
        console.error("Error cargando perfil:", error);
    } finally {
        loading.value = false;
    }
};

const saveProfile = async () => {
    try {
        const formData = new FormData();
        formData.append('name', form.value.name);
        formData.append('surname', form.value.surname);
        formData.append('email', form.value.email);
        
        if (form.value.avatar_file instanceof File) {
            formData.append('avatar_url', form.value.avatar_file);
        }

        const response = await api.post('/user/update', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });
        
        const tempReviews = user.value.reviews;
        const tempRating = user.value.average_rating;
        const tempCount = user.value.reviews_count;
        const tempTotal = user.value.total_orders;
        const tempCompleted = user.value.completed_orders;

        user.value = response.data.user || response.data; 
        
        user.value.reviews = tempReviews;
        user.value.average_rating = tempRating;
        user.value.reviews_count = tempCount;
        user.value.total_orders = tempTotal;
        user.value.completed_orders = tempCompleted;

        isEditing.value = false; 
        
        if (imagePreview.value) {
            URL.revokeObjectURL(imagePreview.value);
            imagePreview.value = null;
        }
        toast.success("¡Perfil actualizado correctamente!");

    } catch (error) {
        toast.error("Error al guardar.");
    }
};

const becomeSeller = async () => {
    try {
        const response = await api.post('/user/become-seller', sellerForm.value);
        user.value = response.data.user; 
        showSellerModal.value = false;
        toast.success("¡Felicidades! Tu tienda ha sido creada.");
    } catch (error) {
        toast.error("Error al crear la tienda.");
    }
};

const handleLogout = async () => {
    try { await api.post('/logout'); } catch (e) {}
    localStorage.removeItem('auth_token');
    router.push('/login');
};

const toggleEdit = () => {
    isEditing.value = !isEditing.value;
    if (!isEditing.value) resetForm(); 
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.value.avatar_file = file;
        if (imagePreview.value) URL.revokeObjectURL(imagePreview.value);
        imagePreview.value = URL.createObjectURL(file);
    }
};

const goToInventory = () => router.push('/seller/inventory');
const goToPickupPoints = () => router.push('/seller/pickup-points');

onMounted(async () => {
    await fetchUser();
});
</script>

<template>
  <div class="profile-wrapper">
    <div v-if="loading" class="loading-state"><div class="spinner"></div> Cargando perfil...</div>

    <div v-else-if="user" class="profile-card">
      
      <div class="profile-header">
        <router-link to="/" class="back-home-btn">← Inicio</router-link>

        <div class="avatar-container" :class="{ 'editable': isEditing }" @click="isEditing ? triggerFileInput() : null">
            
            <img v-if="imagePreview" :src="imagePreview" class="avatar-img" />
            
            <img v-else-if="user.avatar_url" 
                 :src="user.avatar_url.startsWith('http') ? user.avatar_url : BASE_URL + user.avatar_url" 
                 class="avatar-img" />
            
            <div v-else class="avatar-placeholder">
                {{ user.name.charAt(0).toUpperCase() }}
            </div>

            <div v-if="isEditing" class="avatar-overlay">
                <span>📷 Cambiar</span>
            </div>
        </div>
        <input type="file" ref="fileInput" @change="handleFileChange" style="display: none" accept="image/*">

        <div v-if="!isEditing" class="header-info">
            <h2 class="user-name">{{ user.name }} {{ user.surname }}</h2>
            <div class="role-badge-container">
                <span v-if="['seller', 'vendedor'].includes(user.role)" class="badge seller">Vendedor Verificado</span>
                <span v-else class="badge member">Usuario Comprador</span>
            </div>

            <div v-if="canShowRating(user.role)" class="reputation-summary">
                <div v-if="user.reviews_count > 0" class="rating-badge">
                    <span class="rating-number">{{ user.average_rating }}</span>
                    <div class="rating-details">
                        <StarRating :rating="parseFloat(user.average_rating)" :readOnly="true" />
                        <span class="review-link" @click="showReviewsModal = true">
                            Ver las {{ user.reviews_count }} opiniones
                        </span>
                    </div>
                </div>
                <div v-else class="no-rating-badge">
                    <span class="text-muted">⭐ Sin valoraciones aún</span>
                </div>
            </div>
        </div>
      </div>

      <div class="profile-body">
        
        <form v-if="isEditing" @submit.prevent="saveProfile" class="edit-form">
            <div class="form-group"><label>Nombre</label><input v-model="form.name" type="text" required class="input-field" /></div>
            <div class="form-group"><label>Apellidos</label><input v-model="form.surname" type="text" required class="input-field" /></div>
            <div class="form-group"><label>Email</label><input v-model="form.email" type="email" required class="input-field" /></div>
            <div class="action-buttons">
                <button type="button" @click="toggleEdit" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>

        <div v-else class="info-view">
            
            <div class="dashboard-container">
                <div class="metrics-grid">
                    <div class="metric-card">
                        <p class="metric-label">Historial Pedidos</p>
                        <p class="metric-value">{{ user.total_orders || 0 }}</p>
                    </div>
                    <div class="metric-card">
                        <p class="metric-label">Tratos exitosos</p>
                        <p class="metric-value">{{ user.completed_orders || 0 }}</p>
                    </div>
                </div>
            </div>

            <div class="info-row"><label>Email</label><p>{{ user.email }}</p></div>

            <div v-if="user.seller" class="store-box">
                <div class="store-icon"></div>
                <div>
                    <p class="store-name">{{ user.seller.store_name }}</p>
                    <small class="store-nif">NIF: {{ user.seller.nif }}</small>
                </div>
            </div>
            
            <div class="info-row"><label>Miembro desde</label><p>{{ formatDate(user.created_at) }}</p></div>

            <hr class="divider">

            <div class="main-actions">
                <button @click="toggleEdit" class="btn btn-outline"> Editar Perfil</button>
                
                <template v-if="['seller', 'vendedor'].includes(user.role)">
                    <button @click="goToInventory" class="btn btn-inventory"> Gestionar Inventario</button>
                    <button @click="goToPickupPoints" class="btn btn-pickup"> Gestionar Puntos de Recogida</button>
                </template>
                
                <button v-if="!['seller', 'vendedor'].includes(user.role)" @click="showSellerModal = true" class="btn btn-become-seller"> ¡Quiero Vender!</button>
                <button @click="handleLogout" class="btn btn-danger"> Cerrar Sesión</button>
            </div>
        </div>
      </div>
    </div>

    <div v-if="showSellerModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header"><h3>✨ Crea tu Tienda</h3><p>Rellena estos datos para empezar a vender.</p></div>
            <form @submit.prevent="becomeSeller" class="modal-body">
                <div class="form-group"><label>Nombre</label><input v-model="sellerForm.store_name" type="text" required class="input-field"></div>
                <div class="form-group"><label>NIF</label><input v-model="sellerForm.nif" type="text" required class="input-field"></div>
                <div class="form-group"><label>Descripción</label><textarea v-model="sellerForm.description" rows="3" class="input-field"></textarea></div>
                <div class="modal-actions"><button type="button" @click="showSellerModal = false" class="btn btn-secondary">Cancelar</button><button type="submit" class="btn btn-success">Crear Tienda</button></div>
            </form>
        </div>
    </div>

    <div v-if="showReviewsModal" class="modal-overlay" @click.self="showReviewsModal = false">
        <div class="modal-content reviews-modal-content">
            <div class="modal-header">
                <h3>⭐ Opiniones recibidas</h3>
                <button class="close-btn" @click="showReviewsModal = false">✖️</button>
            </div>
            
            <div class="reviews-scroll-container">
                <div v-if="user.reviews.length === 0" class="empty-reviews">
                    No hay opiniones disponibles.
                </div>
                
                <div v-for="review in user.reviews" :key="review.id" class="review-item">
                    <div class="review-header">
                        <span class="review-author">
                            👤 {{ review.author ? review.author.name : 'Usuario' }}
                        </span>
                        <span class="review-date">{{ formatDate(review.created_at) }}</span>
                    </div>
                    <div class="review-stars">
                        <StarRating :rating="Number(review.rating)" :readOnly="true" />
                    </div>
                    <p class="review-comment">{{ review.comment }}</p>
                </div>
            </div>

            <div class="modal-actions" style="justify-content: center; margin-bottom: 25px;">
                <button @click="showReviewsModal = false" class="btn btn-secondary" style="width: auto;">Cerrar</button>
            </div>
        </div>
    </div>

  </div>
</template>

<style scoped>
.profile-wrapper { display: flex; justify-content: center; padding: 40px 20px; background-color: #f8fafc; min-height: 90vh; font-family: 'Segoe UI', sans-serif; }
.profile-card { background: white; width: 100%; max-width: 500px; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); overflow: hidden; display: flex; flex-direction: column; }
.profile-header { background: linear-gradient(to right, #f1f5f9, #e2e8f0); padding: 30px 20px; text-align: center; position: relative; }
.back-home-btn { position: absolute; top: 15px; left: 15px; text-decoration: none; color: #64748b; font-weight: 600; font-size: 0.9rem; }
.avatar-container { width: 100px; height: 100px; margin: 0 auto 15px; border-radius: 50%; border: 4px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); position: relative; overflow: hidden; background: #cbd5e1; }
.avatar-container.editable { cursor: pointer; }
.avatar-img { width: 100%; height: 100%; object-fit: cover; }
.avatar-placeholder { width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; font-weight: bold; color: white; background-color: #3b82f6; }
.avatar-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; opacity: 0; transition: opacity 0.2s; }
.avatar-container:hover .avatar-overlay { opacity: 1; }
.user-name { margin: 0; color: #1e293b; font-size: 1.4rem; }
.role-badge-container { margin-top: 8px; }
.badge { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
.badge.seller { background-color: #dcfce7; color: #166534; }
.badge.member { background-color: #f1f5f9; color: #64748b; }
.profile-body { padding: 25px 30px; }
.info-row { margin-bottom: 15px; }
.info-row label { display: block; font-size: 0.85rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px; }
.info-row p { margin: 0; font-size: 1rem; color: #334155; font-weight: 500; }
.dashboard-container { background: #f8f9fa; border: 1px solid #e9ecef; border-radius: 12px; padding: 24px; margin: 20px 0; }
.metrics-grid { display: flex; justify-content: space-between; gap: 15px; margin-bottom: 30px; }
.metric-card { background: white; flex: 1; padding: 15px; border-radius: 8px; text-align: center; }
.metric-label { font-size: 0.8rem; color: #6c757d; margin-bottom: 5px; }
.metric-value { font-size: 1.4rem; font-weight: bold; color: #212529; }
.store-box { background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px; padding: 12px; display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
.store-icon { font-size: 1.5rem; }
.store-name { margin: 0; font-weight: bold; color: #166534; }
.store-nif { color: #15803d; }
.divider { border: 0; border-top: 1px solid #f1f5f9; margin: 20px 0; }
.main-actions { display: flex; flex-direction: column; gap: 10px; }
.btn { width: 100%; padding: 12px; border: none; border-radius: 8px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s; display: flex; justify-content: center; align-items: center; gap: 8px; }
.btn:hover { transform: translateY(-2px); }
.btn-primary { background-color: #3b82f6; color: white; }
.btn-primary:hover { background-color: #2563eb; }
.btn-secondary { background-color: #e2e8f0; color: #475569; }
.btn-secondary:hover { background-color: #cbd5e1; }
.btn-outline { background-color: white; border: 1px solid #cbd5e1; color: #475569; }
.btn-outline:hover { background-color: #f8fafc; border-color: #94a3b8; }
.btn-inventory { background-color: #e0f2fe; color: #0284c7; }
.btn-inventory:hover { background-color: #bae6fd; }
.btn-pickup { background-color: #fef3c7; color: #d97706; }
.btn-pickup:hover { background-color: #fde68a; }
.btn-become-seller { background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2); }
.btn-become-seller:hover { box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3); }
.btn-success { background-color: #10b981; color: white; }
.btn-danger { background-color: #fee2e2; color: #dc2626; margin-top: 10px; }
.btn-danger:hover { background-color: #fecaca; }
.form-group { margin-bottom: 15px; }
.input-field { width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; color: #1e293b; }
.input-field:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.action-buttons { display: flex; gap: 10px; margin-top: 20px; }
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center; backdrop-filter: blur(2px); }
.modal-content { background: white; width: 90%; max-width: 400px; border-radius: 12px; box-shadow: 0 20px 25px rgba(0,0,0,0.1); overflow: hidden; }
.modal-header { padding: 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
.modal-header h3 { margin: 0; color: #1e293b; }
.modal-header p { margin: 5px 0 0; color: #64748b; font-size: 0.9rem; }
.modal-body { padding: 20px; }
.modal-actions { display: flex; gap: 10px; margin-top: 20px; margin-bottom: 25px; justify-content: center; }
.loading-state { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 50vh; color: #64748b; }
.spinner { width: 30px; height: 30px; border: 3px solid #f3f3f3; border-top: 3px solid #3b82f6; border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 10px; }
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }

.rating-badge { background-color: #ffffff; border: 1px solid #e2e8f0; padding: 8px 20px; border-radius: 12px; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
.rating-number { font-size: 2rem; font-weight: 800; color: #1e293b; line-height: 1; }
.rating-details { display: flex; flex-direction: column; align-items: flex-start; gap: 2px; }
.review-link { font-size: 0.85rem; color: #3b82f6; text-decoration: underline; cursor: pointer; transition: color 0.2s; font-weight: 600; }
.review-link:hover { color: #2563eb; }
.no-rating-badge { background: #f8fafc; padding: 8px 16px; border-radius: 20px; border: 1px dashed #cbd5e1; }
.text-muted { color: #64748b; font-size: 0.85rem; }

.reviews-modal-content { max-height: 80vh; display: flex; flex-direction: column; }
.reviews-scroll-container { flex-grow: 1; overflow-y: auto; padding: 15px; background: #f9fafb; }
.review-item { background: white; border: 1px solid #e5e7eb; padding: 15px; border-radius: 8px; margin-bottom: 10px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
.review-header { display: flex; justify-content: space-between; font-size: 0.85rem; color: #64748b; margin-bottom: 5px; }
.review-author { font-weight: 600; color: #334155; }
.review-comment { color: #475569; font-size: 0.95rem; margin-top: 8px; line-height: 1.4; }
.close-btn { background: none; border: none; font-size: 1.2rem; cursor: pointer; color: #94a3b8; }
.close-btn:hover { color: #dc2626; }
.empty-reviews { color: #94a3b8; font-style: italic; text-align: center; margin-top: 20px; }
</style>