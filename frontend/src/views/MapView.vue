<template>
    <div id="map"></div>
</template>

<script setup>
    import { onMounted } from 'vue'
    import L from 'leaflet'

    onMounted(async() => {
        // 1. Iniciamos el mapa (centrado en Valencia aprox)
        const map = L.map('map').setView([39.4699, -0.3763], 13)

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        }).addTo(map);

        try {
            // 2. CAMBIO IMPORTANTE: Usamos la URL real de tu Backend
            // Si en tu navegador viste el JSON en localhost/api/mapas, pon esa.
            const res = await fetch('http://localhost/api/mapas') 
            
            if (!res.ok) throw new Error("Error conectando con Laravel")

            const puntos = await res.json()

            // 3. CAMBIO IMPORTANTE: Usamos las propiedades reales de tu Base de Datos
            puntos.forEach(punto => {
                // El JSON devuelve "latitude" y "longitude" (no "lat" ni "lng")
                if (punto.latitude && punto.longitude) {
                    L.marker([punto.latitude, punto.longitude])
                        .addTo(map)
                        .bindPopup(punto.address) // Usamos 'address' porque 'name' ya no existe
                }
            })
        } catch (e) {
            console.error("Error cargando mapa:", e)
            // Marcador de error por si falla la conexión
            L.marker([39.4699, -0.3763])
                .addTo(map)
                .bindPopup('⚠️ Error: No se pudo conectar con el Backend')
        }
    })
</script>

<style scoped>
    #map {
        height: 50vh;
        width: 100%; /* Le he puesto 100% para que se vea mejor */
    }
</style>