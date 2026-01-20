<!-- <template>
    <div id="map"></div>
</template>

<script setup>
    import { onMounted, nextTick } from 'vue'
    import L from 'leaflet'
    import 'leaflet/dist/leaflet.css';

    import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
    import markerIcon from 'leaflet/dist/images/marker-icon.png';
    import markerShadow from 'leaflet/dist/images/marker-shadow.png';

    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({
        iconRetinaUrl: markerIcon2x,
        iconUrl: markerIcon,
        shadowUrl: markerShadow,
    });

    onMounted(async() => {
        await nextTick(); 

        const map = L.map('map', {
        minZoom: 7,            
        maxZoom: 18,           
        maxBounds: [           
            [35.0, -15.0],     
            [45.0, 5.0]       
        ],
        maxBoundsViscosity: 1.0
    }).setView([39.4699, -0.3763], 13)

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; OpenStreetMap'
        }).addTo(map);

        try {
            const res = await fetch('http://localhost:8000/api/mapas') 
            if (!res.ok) throw new Error("Error API")
            
            const marcadores = await res.json()
            marcadores.forEach(m => {
                if (m.latitude && m.longitude) {
                    L.marker([parseFloat(m.latitude), parseFloat(m.longitude)])
                        .addTo(map)
                        .bindPopup(m.store_name)
                }
            })
        } catch (e) {
            console.error(e)
        }
    })
</script>

<style scoped>
    #map {
        height: 100vh;
        width: 90vw;
        display: block;
    }
</style> -->

<template>
    <div id="map"></div>
</template>

<script setup>
import { onMounted, nextTick, watch, ref } from 'vue' // Añadimos watch y ref
import L from 'leaflet'
import 'leaflet/dist/leaflet.css';

// Importar las imaágenes de los iconos: le decimos a vite como importar las imágenes de los marcadores
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

// Leaflet trata de acceder a una ruta por defecto donde están las imágenes de los marcadores, pero al hacer
// uso de Vue, estas rutas cambian, lo que puede dar lugar a problemas. Por ello, con este bloque de código
// le decimos a Leaflet que no use las rutas por defecto de los marcadores y en su lugar use las rutas que hemos
// definido
delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

// PROPS: Recibimos las coordenadas del usuario desde HomeView
const props = defineProps({
    userLocation: {
        type: Array, // Esperamos [lat, lng]
        default: null
    }
});

// Guardamos la instancia del mapa para poder moverlo luego
let mapInstance = null;

onMounted(async () => {
    await nextTick();

    // 1. Iniciamos el mapa (Por defecto Valencia si no hay ubicación)
    const initialCenter = props.userLocation || [39.4699, -0.3763];
    
    mapInstance = L.map('map', {
        minZoom: 7,
        maxZoom: 18,
        maxBounds: [[35.0, -15.0], [45.0, 5.0]],
        maxBoundsViscosity: 1.0
    }).setView(initialCenter, 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(mapInstance);

    // 2. Si ya viene ubicación del usuario, ponemos un marcador especial "Tú estás aquí"
    if (props.userLocation) {
        addUserMarker(props.userLocation);
    }

    // 3. Cargar tiendas (Tu código original)
    cargarTiendas();
});

// WATCH: Si HomeView nos manda nuevas coordenadas, movemos el mapa
watch(() => props.userLocation, (newCoords) => {
    if (newCoords && mapInstance) {
        mapInstance.flyTo(newCoords, 15); // flyTo hace una animación suave
        addUserMarker(newCoords);
    }
});

function addUserMarker(coords) {
    // Podrías poner un icono diferente para el usuario aquí
    L.circleMarker(coords, {
        color: 'blue',
        radius: 8,
        fillOpacity: 0.8
    }).addTo(mapInstance).bindPopup("Estás aquí").openPopup();
}

async function cargarTiendas() {
    try {
        // AQUÍ PODRÍAS CAMBIAR LA URL SI QUIERES FILTRAR POR CERCANÍA
        // Por ahora lo dejamos igual
        const res = await fetch('http://localhost:8000/api/mapas');
        if (!res.ok) throw new Error("Error API");
        const marcadores = await res.json();

        marcadores.forEach(m => {
            if (m.latitude && m.longitude) {
                L.marker([parseFloat(m.latitude), parseFloat(m.longitude)])
                    .addTo(mapInstance)
                    .bindPopup(`<b>${m.store_name}</b><br>${m.address || ''}`);
            }
        });
    } catch (e) {
        console.error(e);
    }
}
</script>

<style scoped>
#map {
    height: 100vh;
    width: 100%; /* Ajustado a 100% del contenedor padre */
    display: block;
    border-radius: 12px; /* Un poco de estética */
    z-index: 1;
}
</style>