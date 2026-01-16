<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import api from '../axios'; // Tu configuración de axios

const route = useRoute();
const orderId = route.params.id;

const messages = ref([]);
const newMessage = ref('');
const myUserId = ref(null); // Para saber pintar mis burbujas a la derecha
const chatBox = ref(null); // Referencia para hacer scroll automático
let pollingInterval = null;

// 1. Función para saber quién soy yo (para pintar colores de las burbujas del chat, estilo whatsapp)
const getCurrentUser = async () => {
    try {
        const response = await api.get('/user');
        myUserId.value = response.data.id;
    } catch (error) {
        console.error("Error obteniendo usuario", error);
    }
};

// 2. Función para bajar el scroll al final automáticamente
const scrollToBottom = () => {
    nextTick(() => {
        if (chatBox.value) {
            chatBox.value.scrollTop = chatBox.value.scrollHeight;
        }
    });
};

// 3. Cargar mensajes (Esta es la función que se repite)
const loadMessages = async () => {
    try {
        const response = await api.get(`/orders/${orderId}/messages`);
        
        // Solo hacemos scroll si han llegado mensajes nuevos
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

// 4. Enviar mensaje
const sendMessage = async () => {
    if (!newMessage.value.trim()) return; // No enviar vacíos

    try {
        // Guardamos el mensaje temporalmente para enviarlo
        const texto = newMessage.value;
        newMessage.value = ''; // Limpiamos input rápido para efecto visual

        await api.post(`/orders/${orderId}/messages`, {
            content: texto
        });
        
        // Recargamos mensajes inmediamente
        loadMessages();
    } catch (error) {
        console.error("Error enviando mensaje", error);
        alert("Error al enviar el mensaje");
    }
};

// Al entrar en la página
onMounted(async () => {
    await getCurrentUser();
    await loadMessages();
    
    // Ejecuta 'loadMessages' cada 3000 milisegundos (3 segundos)
    pollingInterval = setInterval(loadMessages, 3000);
});

onUnmounted(() => {
    clearInterval(pollingInterval);
});

</script>

<template>
    <div class="chat-wrapper">
        <div class="chat-header">
            <h3>Chat del Pedido #{{ orderId }}</h3>
        </div>

        <div class="messages-container" ref="chatBox">
            <div 
                v-for="msg in messages" 
                :key="msg.id" 
                class="message-bubble"
                :class="msg.sender_id === myUserId ? 'my-message' : 'other-message'"
            >
                <span class="sender-name">{{ msg.sender.name }}</span>
                <p>{{ msg.content }}</p>
                <small class="time">{{ new Date(msg.created_at).toLocaleTimeString() }}</small>
            </div>
        </div>

        <form @submit.prevent="sendMessage" class="input-area">
            <input 
                v-model="newMessage" 
                type="text" 
                placeholder="Escribe un mensaje..." 
                required
            />
            <button type="submit">Enviar</button>
        </form>
    </div>
</template>