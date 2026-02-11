<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useRouter } from 'vue-router';
import api from '../api/axios'; 

const notifications = ref([]);
const showDropdown = ref(false);
const router = useRouter();
let pollingInterval = null;

const fetchNotifications = async () => {
    const token = localStorage.getItem('auth_token');
    if (!token) return; 

    try {
        const response = await api.get('/notifications');
        notifications.value = response.data;
    } catch (e) {
        if (e.response && e.response.status === 401) {
            console.warn("Sesión caducada o no válida. Deteniendo notificaciones.");
            if (pollingInterval) clearInterval(pollingInterval);
        } else {
            console.error("Error cargando notificaciones:", e);
        }
    }
};

const handleNotificationClick = async (notification) => {
    try {
        await api.put(`/notifications/${notification.id}/read`);
        
        notifications.value = notifications.value.filter(n => n.id !== notification.id);
        
        showDropdown.value = false;

        if (notification.data.url) {
            router.push(notification.data.url);
        }
    } catch (e) {
        console.error(e);
    }
};

onMounted(() => {
    fetchNotifications();
    pollingInterval = setInterval(fetchNotifications, 30000);
});

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});
</script>

<template>
    <div class="notification-wrapper">
        <button @click="showDropdown = !showDropdown" class="bell-btn">
            🔔
            <span v-if="notifications.length > 0" class="badge">
                {{ notifications.length }}
            </span>
        </button>

        <div v-if="showDropdown" class="dropdown">
            <div class="dropdown-header">Notificaciones</div>
            
            <div v-if="notifications.length === 0" class="empty">
                🎉 Estás al día. Sin novedades.
            </div>
            
            <div v-else class="list">
                <div v-for="note in notifications" 
                     :key="note.id" 
                     class="item"
                     @click="handleNotificationClick(note)">
                    
                    <p class="msg">{{ note.data.message }}</p>
                    
                    <small class="time">
                        {{ new Date(note.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) }}
                    </small>
                </div>
            </div>
        </div>
        
        <div v-if="showDropdown" class="backdrop" @click="showDropdown = false"></div>
    </div>
</template>

<style scoped>
.notification-wrapper { position: relative; display: inline-block; }

.bell-btn { 
    background: none; 
    border: none; 
    font-size: 1.5rem; 
    cursor: pointer; 
    position: relative; 
    padding: 5px;
    transition: transform 0.2s;
}
.bell-btn:hover { transform: scale(1.1); }

.badge { 
    position: absolute; top: 0; right: 0; 
    background: #ef4444; color: white; 
    font-size: 0.7rem; padding: 2px 6px; 
    border-radius: 10px; font-weight: bold;
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
}

.dropdown {
    position: absolute; right: -10px; top: 50px;
    background: white; border: 1px solid #e2e8f0;
    width: 320px; border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.15);
    z-index: 1001; overflow: hidden;
    animation: fadeIn 0.2s ease-out;
}

.dropdown-header {
    padding: 12px 15px;
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-weight: bold;
    color: #475569;
    font-size: 0.9rem;
}

.empty { padding: 30px; text-align: center; color: #94a3b8; font-size: 0.9rem; font-style: italic; }

.list { max-height: 300px; overflow-y: auto; }

.item { 
    padding: 15px; 
    border-bottom: 1px solid #f1f5f9; 
    cursor: pointer; 
    text-align: left;
    transition: background 0.1s;
}
.item:hover { background: #f0f9ff; }
.item:last-child { border-bottom: none; }

.msg { margin: 0; font-size: 0.9rem; color: #334155; line-height: 1.4; }
.time { color: #94a3b8; font-size: 0.75rem; display: block; margin-top: 6px; }

.backdrop { position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 1000; cursor: default; }

@keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>