<template>
    <div id="map"></div>
</template>

<script setup>
    import { onMounted, nextTick } from 'vue'
    import L from 'leaflet'
    import 'leaflet/dist/leaflet.css';

    // FIX ICONOS
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
</style>