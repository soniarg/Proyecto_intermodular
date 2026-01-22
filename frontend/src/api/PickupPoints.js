import api from '../axios';

export const getPickupPoints = async () => {
    const res = await api.get('/pickup-points');
    return res.data;
}

export const createPickupPoint = async (data) => {
    const res = await api.post('/pickup-points', data);
    return res.data;
}

export const updatePickupPoint = async (id, data) => {
    const res = await api.put(`/pickup-points/${id}`, data);
    return res.data;
}

export const deletePickupPoint = async (id) => {
    const res = await api.delete(`/pickup-points/${id}`);
    return res.data;
}