import api from '@/api/axios';
import { defineStore } from 'pinia';
import { ref } from 'vue';

export const useJuegosStore = defineStore('juegos', () => {
    const juegos = ref([]);
    const cargando = ref(false);
    const error = ref('');

    const cargarJuegos = async () => {
    // Esta variable sirve para hacer una pequeña carga antes de mostrar los juegos.
    // Se inicializa en true para hacer la carga
        cargando.value = true;
        error.value = '';

        try{
            const getJuegos = await api.get('/juegos');
            juegos.value = getJuegos.data;
        }catch(e){
            console.log(e);
            error.value = "Ha habido un problema cargando los juegos, inténtelo más tarde";
        }finally{
            cargando.value = false;
        }
    }

    return { juegos, cargando, error, cargarJuegos }
});