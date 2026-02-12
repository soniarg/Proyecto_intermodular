// Se importa axios del node-modules para hacer uso de las funciones sobre
// tokens y rutas de axios
import axios from 'axios';

// Se lee el archivo de variables de entorno. Si se está en producción,
// se escoge la URL de dicho archivo. En caso contrario, se utiliza
// localhost
// const baseURL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000';

// Si estamos en producción usa la ruta relativa '', si es local usa localhost
const baseURL = import.meta.env.PROD ? '' : 'http://127.0.0.1:8000';

// Se crea nuestro "mensajero" llamado 'api', con sus reglas de envío:
const api = axios.create({
    // Todas las direcciones empezarán por nuestra URL seguida de /api
    baseURL: `${baseURL}/api`,
    // Estas son las etiquetas que llevará el sobre de cada mensaje:
    headers: {
        // Se le indica a Laravel que la información que se envía viaja en
        // formato JSON
        'Content-Type': 'application/json',
        // Se le indica a Laravel que en caso de haber un error, lo envie
        // en formato JSON
        'Accept': 'application/json'
    },
    // Activamos el uso de cookies. Esto es como darle permiso al mensajero para 
    // llevar y traer las llaves de seguridad que Laravel Sanctum guarda en el navegador.
    withCredentials: true
});

// INTERCEPTOR DE PETICIONES: Se ejecuta justo antes de que el mensaje salga.
api.interceptors.request.use(config => {
    // Buscamos si hay un token guardado en el "cajón" del navegador (localStorage)
    // bajo el nombre 'auth_token'.
    const token = localStorage.getItem('auth_token');
    
    // Si encontramos el token, se lo pegamos al mensaje.
    // Usamos 'Bearer' para decirle al servidor: 
    // "El que lleva este código tiene permiso" Dicho de otra forma, el propietario
    // del token tiene permiso, y si otra persona tratara de usar este mismo token
    // no tendría permiso para navegar por la web.
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    // Devolvemos la configuración para que el mensaje pueda finalmente salir.
    return config;
});

// INTERCEPTOR DE RESPUESTAS: Se ejecuta cuando el servidor nos contesta.
api.interceptors.response.use(
    // Si el servidor dice que todo está OK, dejamos pasar la respuesta tal cual.
    response => response,
    // Si el servidor nos devuelve un error:
    error => {
        // Si el error es el código 401 (significa que nuestra llave/token ya no vale)
        if (error.response && error.response.status === 401) {
            // Limpiamos el token viejo del navegador
            localStorage.removeItem('auth_token');
            // Mandamos al usuario a la página de inicio de sesión
            window.location.href = '/login';
        }
        // Enviamos el error de vuelta a nuestra web para poder mostrar un aviso al usuario.
        return Promise.reject(error);
    }
);

// Hacemos que nuestro mensajero 'api' esté disponible para usarlo en otras partes.
export default api;