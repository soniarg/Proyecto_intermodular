// Se importa axios del node-modules para hacer uso de las funciones sobre
// tokens y rutas de axios
import axios from 'axios';

// Se lee el archivo de configuración
const baseURL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000';

const api = axios.create({
    baseURL: `${baseURL}/api`, // Resultado final: http://127.0.0.1:8000/api
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    },
    withCredentials: true // Vital para Sanctum (cookies)
});

// 2. INTERCEPTOR: Inyección automática de Token
api.interceptors.request.use(config => {
    const token = localStorage.getItem('auth_token');
    
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

// 3. INTERCEPTOR DE ERRORES (Toque Pro Extra)
// Si el token caduca (Error 401), redirigimos al login automáticamente.
api.interceptors.response.use(
    response => response,
    error => {
        if (error.response && error.response.status === 401) {
            // Opcional: Borrar token caducado
            localStorage.removeItem('auth_token');
            // Opcional: Redirigir a login (si usas window.location es más bruto pero infalible)
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default api;