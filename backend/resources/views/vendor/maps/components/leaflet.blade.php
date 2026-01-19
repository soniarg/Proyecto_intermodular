<!-- Este enlace importa todas las configuraciones visuales para el mapa, sin esto, el mapa sería un desastre -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css"
      integrity="sha512-xodZBNTC5n17Xt2atTPuE1HxjVMSvLVW9ocqUKLsCC5CXdbqCmblAshOMAS6/keqq/sMZMZ19scR4PsZChSR7A=="
      crossorigin=""/>

<!--Aquí se añaden los estilos css al mapa que se hayan definido en el html. En caso
de no haberse definido ningún estilo en el html, se define por defecto la altura del mapa
que es la altura de la pantalla -->
<style>
    /* Aquí es donde se selecciona el id al que se le van a asignar los estilos
    Se usa la almohadilla '#' para hacer referencia a que se selecciona un id,
    y entre doble llave, se escoge el id que tendrá el mapa ($mapId). Esta variable
    genera ids aleatorios, para poder tener varios mapas en una misma página 
    Las dobles llaves sirven para imprimir valores de las variables. Sin ellas
    Blade buscaría un id que literalmente se llamara $mapId*/
    #{{$mapId}} {
        /* La lógica de programacion como ifs o bucles etc, empieza con @ */
        /* La función isset sirve para comprobar que una variable exista
        y que tenga un valor. Aquí, en caso de que la variable $attributes tenga un estilo,
        se usan los estilos para el mapa, en caso contrario, se define una altura
        por defecto. La variable attributes se crea al hacer uso del componente del mapa*/
    @if(! isset($attributes['style']))
        height: 100vh;
    @else
        {{ $attributes['style'] }}
    @endif
    }
</style>

/* Aquí se define el div donde se colocará el mapa. Aunque realmente donde se definen los divs 
es en el html, este div es necesario para poder colocar el mapa y aplicar toda la lógica de programacion 
y estilos. Sin este div, no se podría visualizar el mapa */

/* El id del mapa en caso de no ser asignado, se le asigna automáticamente uno gracias a Laravel
y la clase del componente */
<div id="{{$mapId}}" @if(isset($attributes['class']))
 class='{{ $attributes["class"] }}'
@endif
></div>

/* Este script carga el JavaScript de Leaflet para acceder a funciones exclusivas para editar el mapa */
<script src="{{'https://unpkg.com/leaflet@' . $leafletVersion . '/dist/leaflet.js'}}"
        crossorigin="">
</script>

/* En este script se define toda la lógica de programación acerca del mapa */
<script>
    var mymap = L.map('{{$mapId}}', {
        minZoom: 7,            
        maxZoom: 18,           
        maxBounds: [           
            [35.0, -15.0],     
            [45.0, 5.0]       
        ],
        maxBoundsViscosity: 1.0
    }).setView([{{$centerPoint['lat'] ?? $centerPoint[0]}}, {{$centerPoint['long'] ?? $centerPoint[1]}}], {{$zoomLevel}});

    @foreach($markers as $marker)
     @if(isset($marker['icon']))
       var icon = L.icon({
        iconUrl: '{{ $marker['icon'] }}',
        iconSize: [{{$marker['iconSizeX'] ?? 32}} , {{ $marker['iconSizeY'] ?? 32 }}],
       });
     @endif
    
    var marker = L.marker([{{$marker['lat'] ?? $marker[0]}}, {{$marker['long'] ?? $marker[1]}}]
    @if(isset($marker['icon']))
     , {icon: icon}
    @endif
    );

    marker.addTo(mymap);

    @if(isset($marker['info']))
    marker.bindPopup(@json($marker['info']));
    @endif

    @endforeach

    @if($tileHost === 'mapbox')
        let url{{$mapId}} = 'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={{config('maps.mapbox.access_token', null)}}';
    @elseif($tileHost === 'openstreetmap')
        let url{{$mapId}} = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
    @else
        let url{{$mapId}} = '{{$tileHost}}';
    @endif
    L.tileLayer(url{{$mapId}}, {
        maxZoom: {{$maxZoomLevel}},
        attribution: '{!! $attribution !!}',
        id: 'mapbox/streets-v11',
        tileSize: 512,
        zoomOffset: -1
    }).addTo(mymap);
</script>

