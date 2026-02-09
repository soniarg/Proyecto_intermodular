<script setup>
import { ref, onMounted, onUnmounted, nextTick } from 'vue';
import { useRoute } from 'vue-router';
import api from '../api/axios'; // Tu configuración de axios

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

<style scoped>

html, body, #app{
    height: 100%;
    margin: 0;
}

.chat-wrapper{
    display: flex;
    flex-direction: column;
    height: 100vh;
    background-color: white;
}

.chat-header{
    padding: 1rem;
    background-color: #007bff;
    color: white;
    font-weight: bold;
    text-align: center;
    flex-shrink: 0;
}

.messages-container{
    flex: 1;
    padding: 1rem;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    background-color: #f1f2f6;
}

.message-bubble{
    max-width: 70%;
    padding: 0.5rem 1rem;
    border-radius: 12px;
    background-color: #e0e0e0;
    align-self: flex-start;
}

.message-bubble.my-message{
    background-color: #007bff;
    color: white;
    align-self: flex-end;
}

.sender-name{
    font-weight: bold;
    font-size: 0.8rem;
}

.time{
    font-size: 0.7rem;
    color: #666;
}

.input-area{
    display: flex;
    padding: 0.5rem;
    border-top: 1px solid #ccc;
    background-color: white;
    flex-shrink: 0;
}

.input-area input{
    flex: 1;
    padding: 0.5rem;
    border-radius: 8px;
    border: 1px solid #ccc;
    margin-right: 0.5rem;
}

.input-area button{
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 8px;
    background-color: #007bff;
    color: white;
    cursor: pointer;
}

</style>