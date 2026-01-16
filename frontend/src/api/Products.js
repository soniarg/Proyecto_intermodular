import axios from '../axios';

//Obtener todos los productos
export const getProducts = () => {
    return axios.get('/products');
}

//Crear producto
export const createProduct = (data) => {
    return axios.post('/products', data);
}

//Actualizar producto
export const updateProduct = (id, data) => {
    return axios.put(`/products/${id}`, data);
}

//Eliminar producto
export const deleteProduct = (id) => {
    return axios.delete(`/products/${id}`);
}