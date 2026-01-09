<div class="w-full h-30 rounded-lg overflow-hidden shadow-lg border border-gray-200">
    <x-maps-leaflet 
        :centerPoint="['lat' => 40.416, 'long' => -3.703]" 
        :zoomLevel="12" 
        :options="[
            'minZoom' => 5,
            'maxBounds' => [
                [30.0, -20.0],  // Esquina inferior izquierda (Cerca de Canarias/África)
                [50.0, 10.0]    // Esquina superior derecha (Europa central)
            ],
            'maxBoundsViscosity' => 1.0 // Hace que el borde sea sólido, no elástico
        ]"
        :markers="$marcadores"
        class="w-full h-full"
    ></x-maps-leaflet>
</div>