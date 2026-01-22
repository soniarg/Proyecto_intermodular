<template>
    <div id="map"></div>
</template>

<script setup>
import { onMounted, nextTick, watch } from 'vue';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// --- CONFIGURACIÓN DE ICONOS DE LEAFLET ---
// Esto es necesario en Vue/Vite para que se vean los marcadores
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete L.Icon.Default.prototype._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});
// -------------------------------------------

// PROPS: Recibimos las coordenadas del padre (HomeView)
const props = defineProps({
    userLocation: {
        type: Array, // Esperamos [lat, lng] o null
        default: null
    }
});

let mapInstance = null;
let markersLayer = L.layerGroup(); // Grupo para gestionar marcadores fácilmente

onMounted(async () => {
    // Esperamos a que el DOM pinte el div id="map"
    await nextTick();
    
    initMap();
});

// WATCH: Observamos cambios en userLocation.
// El navegador tarda unos segundos en dar la ubicación. Cuando llegue, actualizamos el mapa.
watch(() => props.userLocation, (newCoords) => {
    if (newCoords && mapInstance) {
        console.log("🗺️ Mapa actualizado a nueva ubicación:", newCoords);
        
        // 1. Movemos el mapa suavemente
        mapInstance.flyTo(newCoords, 13);
        
        // 2. Añadimos el marcador "Tú estás aquí"
        addUserMarker(newCoords);

        // 3. Recargamos las tiendas enviando las nuevas coordenadas al backend
        cargarTiendas(newCoords[0], newCoords[1]);
    }
});

function initMap() {
    // Coordenadas por defecto (Valencia) si el usuario no ha dado permiso aún
    const initialCenter = props.userLocation || [39.4699, -0.3763];
    const initialZoom = props.userLocation ? 13 : 7;

    mapInstance = L.map('map').setView(initialCenter, initialZoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(mapInstance);

    // Añadimos la capa de marcadores al mapa
    markersLayer.addTo(mapInstance);

    // Si ya tenemos ubicación al iniciar (raro, pero posible), cargamos filtrado
    if (props.userLocation) {
        addUserMarker(props.userLocation);
        cargarTiendas(props.userLocation[0], props.userLocation[1]);
    } else {
        // Carga inicial sin filtros (todos los puntos o los 50 primeros)
        cargarTiendas();
    }
}

// Pone un círculo azul donde está el usuario
function addUserMarker(coords) {
    L.circleMarker(coords, {
        color: '#3b82f6',    // Borde azul
        fillColor: '#3b82f6', // Relleno azul
        fillOpacity: 1,
        radius: 8
    }).bindPopup("📍 <b>Tú estás aquí</b>").addTo(mapInstance);
}

// Función que pide datos a Laravel
async function cargarTiendas(lat = null, lng = null) {
    try {
        // Construimos la URL
        let url = 'http://localhost:8000/api/mapas'; // Asegúrate que esta ruta apunta a MapController@index
        
        // Si tenemos coordenadas, las añadimos como parámetros query
        if (lat && lng) {
            url += `?lat=${lat}&lng=${lng}`;
        }

        const res = await fetch(url);
        if (!res.ok) throw new Error("Error conectando con API mapas");
        
        const tiendas = await res.json();

        // 1. Limpiamos los marcadores antiguos (excepto el del usuario)
        markersLayer.clearLayers();

        // 2. Iteramos y creamos marcadores
        tiendas.forEach(point => {
            if (point.latitude && point.longitude) {
                const marker = L.marker([parseFloat(point.latitude), parseFloat(point.longitude)]);
                
                // Contenido del Popup
                let popupContent = `
                    <div style="text-align:center">
                        <h3 style="margin:0; color:#1e293b; font-size:1rem">${point.store_name}</h3>
                        <p style="margin:5px 0; color:#64748b; font-size:0.9rem">${point.address}</p>
                        ${point.city ? `<span style="background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:0.8rem">${point.city}</span>` : ''}
                `;

                // Si el backend calculó la distancia, la mostramos
                if (point.distance) {
                    popupContent += `<br><strong style="color:#10b981; display:block; margin-top:5px">📍 A ${point.distance} de ti</strong>`;
                }
                
                popupContent += `</div>`;

                marker.bindPopup(popupContent);
                markersLayer.addLayer(marker);
            }
        });

    } catch (e) {
        console.error("Error cargando tiendas:", e);
    }
}
</script>

<style scoped>
#map {
    height: 100%;       /* Ocupa el 100% del contenedor padre (.map-wrapper en Home) */
    width: 100%;
    min-height: 500px;  /* Altura mínima de seguridad */
    display: block;
    z-index: 1;
}
</style>