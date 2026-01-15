import axios from '../axios';


//Obtener todos los puntos de entrega
export const getPickupPoints = () => {
    return axios.get('/pickup-points');
}

//Crear puntos de entrega
export const createPickupPoint = (data) => {
   return axios.post('/pickup-points', data);
}

//Actualizar puntos de entrega
export const updatePickupPoint = (id, data) => {
   return axios.put(`/pickup-points/${id}`, data);
}

//Eliminar puntos de entrega
export const deletePickupPoint = (id) => {
    return axios.delete(`/pickup-points/${id}`);
}