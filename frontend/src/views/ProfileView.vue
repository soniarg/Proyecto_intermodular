<script setup>
import { ref, onMounted, reactive } from 'vue';
import { useRouter } from 'vue-router';
import api from '@/axios';

const router = useRouter();
const user = ref(null);
const loading = ref(true);
const isEditing = ref(false); 
const imagePreview = ref(null);
const fileInput = ref(null); 

// Lógica para Vendedor
const showSellerModal = ref(false);
const sellerForm = reactive({
    store_name: '',
    nif: '',
    description: ''
});

const form = reactive({
    name: '',
    surname: '',
    email: '',
    avatar: null // Este es el archivo temporal, no importa el nombre aquí
});

const BASE_URL = 'http://localhost:8000/storage/';

onMounted(async () => {
    await fetchUser();
});

const fetchUser = async () => {
    try {
        const response = await api.get('/user');
        user.value = response.data;
        resetForm(); 
    } catch (error) {
        console.error("Error al obtener usuario:", error);
        router.push('/login');
    } finally {
        loading.value = false;
    }
};

const resetForm = () => {
    if (user.value) {
        form.name = user.value.name;
        form.surname = user.value.surname || ''; 
        form.email = user.value.email;
        form.avatar = null;
        imagePreview.value = null;
    }
};

const toggleEdit = () => {
    isEditing.value = !isEditing.value;
    if (!isEditing.value) resetForm(); 
};

const handleFileChange = (event) => {
    const file = event.target.files[0];
    if (file) {
        form.avatar = file;
        imagePreview.value = URL.createObjectURL(file);
    }
};

const saveProfile = async () => {
    try {
        const formData = new FormData();
        
        formData.append('name', form.name);
        formData.append('surname', form.surname);
        formData.append('email', form.email);
        
        if (form.avatar instanceof File) {
            // ✅ CORREGIDO: Enviamos como 'avatar_url' para que el backend lo reconozca
            formData.append('avatar_url', form.avatar);
        }

        const response = await api.post('/user/update', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        user.value = response.data.user || response.data; 

        isEditing.value = false; 
        // Actualizamos el preview o limpiamos para asegurar que se ve la nueva imagen
        imagePreview.value = null; 
        alert("¡Perfil actualizado correctamente!");

    } catch (error) {
        console.error("Error al guardar:", error);
        alert(error.response?.data?.message || "Error al guardar los cambios.");
    }
};

// --- LÓGICA DE VENDEDOR ---
const becomeSeller = async () => {
    try {
        const response = await api.post('/user/become-seller', sellerForm);
        user.value = response.data.user; // Actualizamos usuario con rol nuevo
        showSellerModal.value = false;
        alert("¡Felicidades! Tu tienda ha sido creada.");
    } catch (error) {
        console.error(error);
        const msg = error.response?.data?.message || "Error al crear la tienda.";
        alert("Error: " + msg);
    }
};

const goToInventory = () => {
    router.push('/seller/inventory');
};
// --------------------------

const handleLogout = async () => {
    try { await api.post('/logout'); } catch (e) {}
    localStorage.removeItem('auth_token');
    router.push('/login');
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('es-ES', { year: 'numeric', month: 'long', day: 'numeric' });
};

const triggerFileInput = () => fileInput.value.click();
</script>

<template>

  <div class="profile-wrapper">
    <div v-if="loading" class="loading-state">
        <div class="spinner"></div> Cargando perfil...
    </div>


    <div v-else-if="user" class="profile-card">
      
      <div class="profile-header">
        <router-link to="/" class="back-home-btn" title="Volver al Inicio">
            ← Inicio
        </router-link>

        <div class="avatar-container" :class="{ 'editable': isEditing }" @click="isEditing ? triggerFileInput() : null">
            <img v-if="imagePreview" :src="imagePreview" class="avatar-img" />
            <img v-else-if="user.avatar" :src="BASE_URL + user.avatar" class="avatar-img" />
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
                <span v-if="user.role === 'seller' || user.role === 'vendedor'" class="badge seller">
                    ✅ Vendedor Verificado
                </span>
                <span v-else class="badge member">Miembro de ProxiMarkt</span>
            </div>
        </div>
      </div>

      <div class="profile-body">
        
        <form v-if="isEditing" @submit.prevent="saveProfile" class="edit-form">
            <div class="form-group">
                <label>Nombre</label>
                <input v-model="form.name" type="text" required class="input-field" />
            </div>
            <div class="form-group">
                <label>Apellidos</label>
                <input v-model="form.surname" type="text" required class="input-field" />
            </div>
            <div class="form-group">
                <label>Email</label>
                <input v-model="form.email" type="email" required class="input-field" />
            </div>

            <div class="action-buttons">
                <button type="button" @click="toggleEdit" class="btn btn-secondary">Cancelar</button>
                <button type="submit" class="btn btn-primary">Guardar Cambios</button>
            </div>
        </form>

        <div v-else class="info-view">
            <div class="info-row">
                <label>Email</label>
                <p>{{ user.email }}</p>
            </div>

            <div v-if="user.seller_profile" class="store-box">
                <div class="store-icon">🏪</div>
                <div>
                    <p class="store-name">{{ user.seller_profile.store_name }}</p>
                    <small class="store-nif">NIF: {{ user.seller_profile.nif }}</small>
                </div>
            </div>
            
            <div class="info-row">
                <label>Miembro desde</label>
                <p>{{ formatDate(user.created_at) }}</p>
            </div>

            <hr class="divider">

            <div class="main-actions">
                <button @click="toggleEdit" class="btn btn-outline">✏️ Editar Perfil</button>
                
                <button v-if="user.role === 'seller' || user.role === 'vendedor'" 
                        @click="goToInventory" 
                        class="btn btn-inventory">
                    📦 Gestionar Inventario
                </button>

                <button v-if="user.role !== 'seller' && user.role !== 'vendedor'" 
                        @click="showSellerModal = true" 
                        class="btn btn-become-seller">
                    🚀 ¡Quiero Vender!
                </button>

                <button @click="handleLogout" class="btn btn-danger">🚪 Cerrar Sesión</button>
            </div>
        </div>
      </div>
    </div>

    <div v-if="showSellerModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3>✨ Crea tu Tienda</h3>
                <p>Rellena estos datos para empezar a vender.</p>
            </div>
            <form @submit.prevent="becomeSeller" class="modal-body">
                <div class="form-group">
                    <label>Nombre de la Tienda</label>
                    <input v-model="sellerForm.store_name" type="text" required placeholder="Ej: Huerto de Juan" class="input-field">
                </div>
                <div class="form-group">
                    <label>NIF / DNI</label>
                    <input v-model="sellerForm.nif" type="text" required placeholder="12345678X" class="input-field">
                </div>
                <div class="form-group">
                    <label>Descripción</label>
                    <textarea v-model="sellerForm.description" rows="3" placeholder="Vendo frutas ecológicas..." class="input-field"></textarea>
                </div>
                <div class="modal-actions">
                    <button type="button" @click="showSellerModal = false" class="btn btn-secondary">Cancelar</button>
                    <button type="submit" class="btn btn-success">Crear Tienda</button>
                </div>
            </form>
        </div>
    </div>

  </div>
</template>

<style scoped>
/* Estructura General */
.profile-wrapper {
    display: flex; justify-content: center; padding: 40px 20px;
    background-color: #f8fafc; min-height: 90vh; font-family: 'Segoe UI', sans-serif;
}

.profile-card {
    background: white; width: 100%; max-width: 500px;
    border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    overflow: hidden; display: flex; flex-direction: column;
}

/* Cabecera */
.profile-header {
    background: linear-gradient(to right, #f1f5f9, #e2e8f0);
    padding: 30px 20px; text-align: center; position: relative;
}
.back-home-btn {
    position: absolute; top: 15px; left: 15px; text-decoration: none;
    color: #64748b; font-weight: 600; font-size: 0.9rem;
}
.back-home-btn:hover { color: #334155; }

/* Avatar */
.avatar-container {
    width: 100px; height: 100px; margin: 0 auto 15px;
    border-radius: 50%; border: 4px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
    position: relative; overflow: hidden; background: #cbd5e1;
}
.avatar-container.editable { cursor: pointer; }
.avatar-img { width: 100%; height: 100%; object-fit: cover; }
.avatar-placeholder {
    width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
    font-size: 2.5rem; font-weight: bold; color: white; background-color: #3b82f6;
}
.avatar-overlay {
    position: absolute; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center;
    color: white; font-weight: bold; opacity: 0; transition: opacity 0.2s;
}
.avatar-container:hover .avatar-overlay { opacity: 1; }

.user-name { margin: 0; color: #1e293b; font-size: 1.4rem; }
.role-badge-container { margin-top: 8px; }
.badge { padding: 4px 12px; border-radius: 20px; font-size: 0.8rem; font-weight: 600; }
.badge.seller { background-color: #dcfce7; color: #166534; }
.badge.member { background-color: #f1f5f9; color: #64748b; }

/* Cuerpo */
.profile-body { padding: 25px 30px; }
.info-row { margin-bottom: 15px; }
.info-row label { display: block; font-size: 0.85rem; color: #94a3b8; font-weight: 600; margin-bottom: 2px; }
.info-row p { margin: 0; font-size: 1rem; color: #334155; font-weight: 500; }

/* Caja de Tienda */
.store-box {
    background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 10px;
    padding: 12px; display: flex; align-items: center; gap: 12px; margin-bottom: 20px;
}
.store-icon { font-size: 1.5rem; }
.store-name { margin: 0; font-weight: bold; color: #166534; }
.store-nif { color: #15803d; }

.divider { border: 0; border-top: 1px solid #f1f5f9; margin: 20px 0; }

/* Botones y Estilos */
.main-actions { display: flex; flex-direction: column; gap: 10px; }

.btn {
    width: 100%; padding: 12px; border: none; border-radius: 8px;
    font-size: 1rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
    display: flex; justify-content: center; align-items: center; gap: 8px;
}
.btn:hover { transform: translateY(-2px); }

.btn-primary { background-color: #3b82f6; color: white; }
.btn-primary:hover { background-color: #2563eb; }

.btn-secondary { background-color: #e2e8f0; color: #475569; }
.btn-secondary:hover { background-color: #cbd5e1; }

.btn-outline { background-color: white; border: 1px solid #cbd5e1; color: #475569; }
.btn-outline:hover { background-color: #f8fafc; border-color: #94a3b8; }

.btn-inventory { background-color: #e0f2fe; color: #0284c7; }
.btn-inventory:hover { background-color: #bae6fd; }

/* ESTILO DEL BOTÓN QUIERO VENDER (Destacado) */
.btn-become-seller {
    background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    color: white; box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
}
.btn-become-seller:hover {
    box-shadow: 0 6px 12px rgba(16, 185, 129, 0.3);
}

.btn-success { background-color: #10b981; color: white; }
.btn-danger { background-color: #fee2e2; color: #dc2626; margin-top: 10px; }
.btn-danger:hover { background-color: #fecaca; }

/* Formularios */
.form-group { margin-bottom: 15px; }
.input-field {
    width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 8px;
    font-size: 0.95rem; color: #1e293b;
}
.input-field:focus { outline: none; border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1); }
.action-buttons { display: flex; gap: 10px; margin-top: 20px; }

/* Modal */
.modal-overlay {
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center;
    backdrop-filter: blur(2px);
}
.modal-content {
    background: white; width: 90%; max-width: 400px; border-radius: 12px;
    box-shadow: 0 20px 25px rgba(0,0,0,0.1); overflow: hidden;
}
.modal-header { padding: 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; }
.modal-header h3 { margin: 0; color: #1e293b; }
.modal-header p { margin: 5px 0 0; color: #64748b; font-size: 0.9rem; }
.modal-body { padding: 20px; }
.modal-actions { display: flex; gap: 10px; margin-top: 10px; }

/* Loading Spinner */
.loading-state { display: flex; flex-direction: column; align-items: center; justify-content: center; height: 50vh; color: #64748b; }
.spinner {
    width: 30px; height: 30px; border: 3px solid #f3f3f3; border-top: 3px solid #3b82f6;
    border-radius: 50%; animation: spin 1s linear infinite; margin-bottom: 10px;
}
@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
</style>