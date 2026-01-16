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

const form = reactive({
    name: '',
    surname: '',
    email: '',
    avatar: null
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
        console.error(error);
        router.push('/login');
    } finally {
        loading.value = false;
    }
};

const resetForm = () => {
    form.name = user.value.name;
    form.surname = user.value.surname;
    form.email = user.value.email;
    form.avatar = null;
    imagePreview.value = null;
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
        
        if (form.avatar) {
            formData.append('avatar', form.avatar);
        }

        const response = await api.post('/user/update', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        user.value = response.data.user; 
        isEditing.value = false; 
        alert("Perfil actualizado correctamente!");
    } catch (error) {
        console.error(error);
        alert("Error al guardar los cambios.");
    }
};

const handleLogout = async () => {
    try { await api.post('/logout'); } catch (e) {}
    localStorage.removeItem('auth_token');
    router.push('/login');
};

const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('ca-ES', { year: 'numeric', month: 'long', day: 'numeric' });
};

const triggerFileInput = () => fileInput.value.click();
</script>

<template>
  <div class="profile-container">
    <div v-if="loading" class="loading-state">Cargando...</div>

    <div v-else-if="user" class="profile-card">
      
      <div class="profile-header">
        
        <router-link to="/" class="back-home-btn" title="Volver al Inicio">
            ← Inicio
        </router-link>
        <div class="avatar-wrapper" :class="{ 'editable': isEditing }" @click="isEditing ? triggerFileInput() : null">
            <img v-if="imagePreview" :src="imagePreview" class="profile-avatar-img" />
            <img v-else-if="user.avatar" :src="BASE_URL + user.avatar" class="profile-avatar-img" />
            <div v-else class="profile-avatar-large">
                {{ user.name.charAt(0).toUpperCase() }}
            </div>

            <div v-if="isEditing" class="avatar-overlay">
                <span>📷</span>
            </div>
        </div>

        <input type="file" ref="fileInput" @change="handleFileChange" style="display: none" accept="image/*">

        <div v-if="!isEditing" class="text-center">
            <h2 class="profile-name">{{ user.name }} {{ user.surname }}</h2>
            <span class="profile-role">Miembro de ProxiMarkt</span>
        </div>
      </div>

      <div class="profile-body">
        
        <form v-if="isEditing" @submit.prevent="saveProfile" class="edit-form">
            <div class="form-group">
                <label>Nombre</label>
                <input v-model="form.name" type="text" required />
            </div>
            <div class="form-group">
                <label>Apellidos</label>
                <input v-model="form.surname" type="text" required />
            </div>
            <div class="form-group">
                <label>Email</label>
                <input v-model="form.email" type="email" required />
            </div>

            <div class="edit-actions">
                <button type="button" @click="toggleEdit" class="cancel-btn">Cancelar</button>
                <button type="submit" class="save-btn">Guardar Cambios</button>
            </div>
        </form>

        <div v-else>
            <div class="info-group">
                <label>Email</label>
                <p>{{ user.email }}</p>
            </div>
            
            <div class="info-group">
                <label>Miembro desde</label>
                <p>{{ formatDate(user.created_at) }}</p>
            </div>

            <hr class="divider">

            <button @click="toggleEdit" class="action-btn">✏️ Editar Perfil</button>
            <button @click="handleLogout" class="logout-btn">🚪 Cerrar Sessión</button>
        </div>

      </div>
    </div>
  </div>
</template>