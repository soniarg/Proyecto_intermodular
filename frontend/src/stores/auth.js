// A la hora de importar objetos de otros archivos, se puede hacer:
// - {} -> Si el archivo exporta varios objetos y quieres exportar unos en concreto,
// tienes que hacerles referencia por su nombre, no puedes usar el nombre que quieras

// - No poner llaves -> si el archivo desde el cuál haces la importación solo exporta
// un objeto, no hace falta el uso de llaves y además puedes ponerle el nombre que quieras
// al objeto

import { defineStore } from "pinia"; // Importar función store de pinia
import { ref } from 'vue'; // Importar reactividad de Vue
import router from '@/router'; // Importar el router para enlazar entre páginas
import api from '@/api/axios' // Importar el api de axios para acceder al backend

// Se crea la constante la cuál va a almacenar toda la lógica de sesión
export const useAuthStore = defineStore('auth', () => {

    // Variable para almacenar los datos del usuario
    const user = ref(null); 

    // Variable para mostrar mensajes de error
    const error = ref(''); 

    // Variable para almacenar el token. Se inicializa recogiendo el token del localStorage
    // (en caso de que haya uno) para que cada vez que se recargue la página, no se haya que logear
    // cada vez
    const token = ref(localStorage.getItem('auth_token')); 

    // Función de login
    const login = async (credentials) => {
        error.value = '';

        try{
            // Hacemos el login y guardamos los datos
            const data = await api.post('/login', credentials);

            // Guardamos los datos del usuario
            user.value = data.data.user;

            // Guardamos el token
            token.value = data.data.access_token;

            // Guardamos el token en el almacenamiento local del usuario
            localStorage.setItem('auth_token', token.value);

            // Redirigir a la página de Inicio, que en este caso, es el componente Padre
            router.push('/prueba/componente');
        }catch(e){
            // El código de error 422 se relaciona con datos incorrectos, por eso el mensaje de error
            // es sobre credenciales incorrectas           
            if(e.response && e.response.status === 422){
                error.value = "Las credenciales no son correctas";

            // Si no se ha producido un error de credenciales, será un error de conexión
            }else{
                error.value = "Error de conexión, inténtalo de nuevo";
            }
        }
    };

    // Función para obtener los datos del usuario. Cada vez que se recarga 
    // la página, la variable con los datos del usuario se vuelve a crear null,
    // por lo que si el usuario ya ha iniciado sesión, los datos del usuario
    // estarán ahora en el token, por lo que hay que acceder a él
    const getUser = async () => {
        // Si la variable token no tiene valor, significa que no se ha iniciado sesión
        // o el token ha caducado
        if(!token.value){
            return;
        }

        try{
            // Guardar los datos del usuario 
            const data = await api.get('/user');
            user.value = data.data;
        }catch(e){
            // En caso de que falle, quitar los datos del token y usuario y
            // quitar el token del almacenamiento local
            console.warn("Sesión no válida, pasando a modo invitado");
            token.value = null;
            user.value = null;
            localStorage.removeItem('auth_token');
        }
    };

    // Función para cerrar sesión
    const logout = async () => {
        try{
            // Se ejecuta la función del backend
            await api.post('/logout');
        }catch(e){
            // En caso de que falle por algún motivo, mostrar un mensaje por consola
            console.warn("Fallo de red al hacer logout, cerrando sesión manualmente");
        }finally{
            // Si o sí, quitar los datos del token y usuario, quitar el token del almacenamiento
            // local y devolver al usuario a la página de Inicio
            token.value = null;
            user.value = null;
            localStorage.removeItem('auth_token');
            router.push('/prueba/componente');
        }
    };

    return{
        token,
        user,
        error,
        login,
        getUser,
        logout
    }
});