<template>
    <div id="map"></div>
</template>

<script setup>
    import { onMounted } from 'vue'
    import L from 'leaflet'

    onMounted(async() => {
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
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        try {
            const res = await fetch('http://localhost/api/mapas') 
            
            if (!res.ok) throw new Error("Error conectando con Laravel")

            const marcadores = await res.json()

            marcadores.forEach(marcador => {

                if (marcador.latitude && marcador.longitude) {
                    L.marker([marcador.latitude, marcador.longitude])
                        .addTo(map)
                        .bindPopup(marcador.store_name)
                }
            })
        } catch (e) {
            console.error("Error cargando mapa:", e)
            L.marker([39.4699, -0.3763])
                .addTo(map)
                .bindPopup('⚠️ Error: No se pudo conectar con el Backend')
        }
    })
</script>

<style scoped>
    #map {
        height: 100vh;
        width: 100%;
    }
</style>