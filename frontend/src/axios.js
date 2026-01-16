import axios from 'axios';

// Asumimos que Laravel corre en el puerto 8000
const api = axios.create({
    baseURL: 'http://localhost:8000/api',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

// Interceptor: Si hay token guardado, lo inyecta en la petición
api.interceptors.request.use(config => {
    // CORRECCIÓN AQUÍ: Usamos 'auth_token' que es como lo guardamos en el Login
    const token = localStorage.getItem('auth_token'); 
    
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

export default api;