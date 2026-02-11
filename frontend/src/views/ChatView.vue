<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import api from '../api/axios';

const route = useRoute();
const router = useRouter();
const orderId = route.params.id;

const messages = ref([]);
const newMessage = ref('');
const myUserId = ref(null);
const chatBox = ref(null);
let pollingInterval = null;

// --- Funciones del Chat ---

const getCurrentUser = async () => {
    try {
        const response = await api.get('/user');
        myUserId.value = response.data.id;
    } catch (error) {
        console.error("Error obteniendo usuario", error);
    }
};

const scrollToBottom = () => {
    nextTick(() => {
        if (chatBox.value) {
            chatBox.value.scrollTo({
                top: chatBox.value.scrollHeight,
                behavior: 'smooth'
            });
        }
    });
};

const loadMessages = async () => {
    try {
        const response = await api.get(`/orders/${orderId}/messages`);
        if (response.data.length > messages.value.length) {
            messages.value = response.data;
            scrollToBottom();
        } else {
            messages.value = response.data;
        }
    } catch (error) {
        console.error("Error cargando chat", error);
    }
};

const sendMessage = async () => {
    if (!newMessage.value.trim()) return;
    try {
        const texto = newMessage.value;
        newMessage.value = '';
        await api.post(`/orders/${orderId}/messages`, { content: texto });
        loadMessages();
    } catch (error) {
        console.error("Error enviando mensaje", error);
    }
};

const goBack = () => {
    router.back();
};

// --- CICLO DE VIDA (AQUÍ ESTÁ LA MAGIA) ---

onMounted(async () => {
    // 🔒 BLOQUEAR SCROLL AL ENTRAR
    document.body.style.overflow = 'hidden';
    document.documentElement.style.overflow = 'hidden';

    await getCurrentUser();
    await loadMessages();
    pollingInterval = setInterval(loadMessages, 3000);
});

onUnmounted(() => {
    // 🔓 DESBLOQUEAR SCROLL AL SALIR (Esto faltaba antes)
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';

    // Limpiar intervalo
    if (pollingInterval) clearInterval(pollingInterval);
});
</script>

<template>
    <div class="chat-container">
        <div class="chat-info-bar">
            <button @click="goBack" class="back-btn">←</button>
            <h3>Pedido #{{ orderId }}</h3>
            <div class="placeholder"></div>
        </div>

        <div class="messages-container" ref="chatBox">
            <div v-for="msg in messages" :key="msg.id" class="message-bubble"
                :class="msg.sender_id === myUserId ? 'my-message' : 'other-message'">
                <span class="sender-name">
                    {{ msg.sender_id === myUserId ? 'Tú' : msg.sender.name }}
                </span>

                <p class="message-text">{{ msg.content }}</p>

                <small class="time">
                    {{ new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }) }}
                </small>
            </div>
        </div>

        <form @submit.prevent="sendMessage" class="input-area">
            <input v-model="newMessage" type="text" placeholder="Escribe un mensaje..." required />
            <button type="submit" :disabled="!newMessage.trim()">
                <span class="send-icon">➤</span>
            </button>
        </form>
    </div>
</template>

<style scoped>
/* --- CONTENEDOR PRINCIPAL --- */
.chat-container {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    top: 70px; /* Ajuste estándar PC */
    
    display: flex;
    flex-direction: column;
    background-color: #f1f2f6;
    
    /* Bloqueo de scroll y rebote */
    overflow: hidden;
    overscroll-behavior: none;
    touch-action: none;
    
    z-index: 90;
}

/* --- BARRA SUPERIOR --- */
.chat-info-bar {
    flex: 0 0 auto;
    height: 60px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 15px;
    background-color: #067550;
    color: white;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    z-index: 10;
    touch-action: none;
}

.chat-info-bar h3 { margin: 0; font-size: 1.1rem; }
.back-btn { background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0 10px; }
.placeholder { width: 30px; }

/* --- ZONA DE MENSAJES --- */
.messages-container {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 1rem;
    display: flex;
    flex-direction: column;
    gap: 12px;
    scroll-behavior: smooth;
    overscroll-behavior: contain;
    touch-action: pan-y; /* Permitir scroll aquí */
    background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7z' fill='%239C92AC' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
}

/* --- ZONA INPUT --- */
.input-area {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    
    /* Padding generoso para evitar toques fantasma */
    padding: 15px;
    
    background-color: white;
    border-top: 1px solid #ddd;
    
    /* 🔥 AJUSTE SEGURO PARA MÓVILES + ESPACIO EXTRA 🔥 */
    padding-bottom: calc(25px + env(safe-area-inset-bottom));
    
    touch-action: none;
}

.input-area input {
    flex: 1;
    padding: 12px 15px;
    border-radius: 25px;
    border: 1px solid #ccc;
    margin-right: 10px;
    outline: none;
    font-size: 1rem;
    touch-action: manipulation;
}

.input-area button {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    border: none;
    background: #067550;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.3rem;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    transition: transform 0.1s;
}

.input-area button:active {
    transform: scale(0.95);
}

/* --- BURBUJAS --- */
.message-bubble {
    max-width: 85%;
    padding: 10px 16px;
    border-radius: 12px;
    position: relative;
    word-wrap: break-word;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.my-message { align-self: flex-end; background-color: #1e293b; color: white; border-bottom-right-radius: 2px; }
.other-message { align-self: flex-start; background-color: white; color: #333; border-bottom-left-radius: 2px; }

.sender-name { font-size: 1rem; font-weight: bold; display: block; margin-bottom: 4px; opacity: 0.9; }
.other-message .sender-name { color: #067550; }
.my-message .sender-name { color: #94a3b8; text-align: right; }

.message-text { margin: 0; line-height: 1.4; font-size: 1.3rem; }
.time { display: block; font-size: 0.7rem; text-align: right; margin-top: 4px; opacity: 0.7; }

/* --- AJUSTE MÓVIL --- */
@media (max-width: 1100px) {
    .chat-container {
        top: 177px;
    }
}
</style>