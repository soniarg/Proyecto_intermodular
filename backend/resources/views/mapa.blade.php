<div class="w-full h-30 rounded-lg overflow-hidden shadow-lg border border-gray-200">
    <x-maps-leaflet 
        :centerPoint="['lat' => 40.416, 'long' => -3.703]" 
        :zoomLevel="12" 
        :markers="$marcadores"
        class="w-full h-full"
    ></x-maps-leaflet>
</div>