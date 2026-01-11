- Esta es mi documentacion sobre todo lo que he hecho en cuanto a los mapas. Como lo he documentado todo en un documento .odt , hay ciertos bloques que hacen referencia a imagenes, y este archivo al ser de extensión markdown, no permite la visualizacion de imagenes, lo que puede resultar algo lioso, por lo que también adjuntaré el pdf, el cual se puede descargar (clic derecho sobre el archivo) para una mejor visualización de la documentación.

# Método utilizado
Hago uso del paquete larswiegers/laravel-maps, que soporta Leaflet y permite usar mapas gratuitos de OpenStreetMap (desarrollar más esta explicación)

# Instalación
Desde visual studio, desde una terminal abierta dentro del proyecto, luego de haber levantado los contenedores, se introduce el comando ./vendor/bin/sail composer require larswiegers/laravel-maps para la instalación del paquete.

# Cómo empezar
Para empezar, quiero mostrar un mapa de prueba con puntos de recogida de ejemplo para comprobar el funcionamiento básico del mapa.

## Modelos
Quiero mostrar en los puntos de recogida, el nombre de la empresa de cada vendedor, por lo que necesito un modelo para el perfil de los vendedores (seller_profiles) y un modelo para los puntos de recogida (pickup_points), y relacionar estos modelos entre ellos (un vendedor puede tener varios puntos de recogida pero un punto de recogida pertenece a un vendedor) para así poder hacer consultas SQL más sencillas y rápidas.


### SellerProfile

El modelo del perfil de los vendedores es básico, solamente tiene los datos sobre la tabla, la clave primaria (seller_id) y aquellos datos que se pueden rellenar mediante un formulario, y los que están ocultos. Para relacionar el perfil de los vendedores con los respectivos puntos de recogida, lo haré en el modelo de estos últimos.


### PickupPoint

El modelo de los puntos de recogida, a parte de los datos que también hay en todos los modelos, también incluye una función, ya que un modelo es también una clase, por lo que puede tener funciones como todas las clases. Esta función es la que permite relacionar cada objeto de punto de recogida con cada perfil de vendedor mediante el id (seller_id).
Controlador – MapController
Luego de tener los modelos listos, ahora es necesario un controlador para poder acceder a los datos de la base de datos y poder obtener una lista de todos los puntos de recogida asociados a cada vendedor y mostrarlos en el mapa.


## Controlador del mapa (MapController)

El controlador está compuesto de varios bloques:

### Bloque 1
Aquí, se define el array de los puntos de recogida (puntos), que contiene además de los datos de cada punto de recogida, un array asociado a cada punto con los datos del respectivo vendedor. Es como si se hiciera un SELECT * de los puntos de recogida más un JOIN para obtener los datos de los vendedores.

### Bloque 2
En este bloque, lo que se pretende es obtener las coordenadas de cada punto de recogida, más el nombre de la empresa del respectivo vendedor de cada punto, eliminando así, la información sobrante que no es útil para el mapa.

El array se almacena en la variable marcadores, y con la función map, se recorre todo el array de puntos obtenido en el bloque 1, y cada punto obtenido, se pasa a una función dentro de la función map, donde temporalmente se le llama punto a cada objeto del array, se devuelven las coordenadas del punto más la info del vendedor (el nombre de la empresa), y a medida que se obtienen los datos de cada punto, se guardan en un array con la función toArray (esto se hace porque la función map devuelve una colección, que es un objeto de Laravel que envuelve a un array para poder aplicar funciones sobre él, y el mapa de Leaflet no acepta colecciones para mostrar los marcadores, se necesitan arrays).

### Bloque 3
Finalmente, una vez se obtiene el array con los datos principales de los puntos de recogida, se pasa este array a la vista donde está el mapa para así poder mostrar los marcadores.


# Vista

## Código de la vista que muestra el mapa

Este es un código básico utilizado para mostrar un mapa de prueba con marcadores de ejemplo. Se emplea Tailwind CSS, que es una síntaxis CSS de Laravel que permite escribir CSS dentro del mismo archivo HTML para ahorrar trabajo. Aunque de normal, se sigue la norma de separar HTML, CSS y JavaScript en diferentes archivos, en frameworks como Laravel, se prefiere combinar CSS dentro del HTML para ahorrar problemas como sería borrar un código CSS que ya no utilizas pero que se te olvida quitarlo también en el HTML, etc
Además, también trae ciertas reglas de medida entre otras cosas que ahorran tiempo a la hora de decidir una medida precisa y permiten adaptar la página perfectamente al navegador.

Se define un bloque div en el que irá el mapa, definiendo también unos estilos CSS.
Luego, se define el bloque asociado al mapa de leaflet. En él, se definen:
centerPoint: donde se ubica el mapa al inicio de cargar la página.
zoomLevel: la cantidad de zoom que hace el mapa al inicio de cargar la página.
markers: los marcadores que aparecen en el mapa (el array con los puntos de recogida)
class: reglas CSS para el mapa (en este caso, el tamaño que tendrá, el cuál será ocupar todo el espacio disponible del div)

Las propiedades centerPoint, zoomLevel, y markers, tienen  el signo “:” delante. Esto se usa para indicar que son variables PHP y que Laravel las tiene que tratar como tal. En el caso de class, al no tener “:”, es tratado como un atributo HTML.


Este es el archivo que enlaza los endpoints con cada función de los controladores. En este caso, cuando carga la página principal, se enlaza con la función index del MapController, que es la que contiene la lógica para devolver el mapa con los marcadores.


## Añadir un marcador de inicio
También es interesante hacer saber la ubicación de la que partes, no solo los puntos de recogida. Para indicarlo, hay que integrar un marcador más al array de los marcadores.


Como los arrays no tienen funciones, hay que mantener la variable de marcadores como una colección para poder añadir el marcador inicial, y luego convertirlo a un array al pasarlo a la vista.


## Mapa con marcador de inicio

Personalizar el mapa
Si quisiéramos añadir configuraciones personalizadas que las configuraciones básicas no permiten, hay que acceder a los archivos de configuración y editarlos para adaptarlos a lo que queremos.
Para acceder a dichos archivos, dentro de la terminal introducimos el comando ./vendor/bin/sail artisan vendor:publish –provider="Larswiegers\LaravelMaps\LaravelMapsServiceProvider"
Luego de hacer esto, se podrán ver dos archivos:


### Archivos de configuración

Hay un archivo de configuración de mapas Leaflet y otro de Google. He optado por hacer uso de leaflet para evitar problemas de pago con google maps.

Este es el archivo de configuración que viene por defecto luego de ejecutar el comando. De forma resumida, el archivo tiene el enlace para importar los estilos del mapa, el div donde cargará el mapa, un script para importar todos los comandos de leaflet relacionado con los mapas y otro script con la configuración del mapa.
Nos vamos a centrar en esta línea de código: 

### Código de creación del mapa

Esta línea de código es la que se encarga de crear un mapa en el div con el id asignado (mapId) y establecer la vista (el centrado, donde se posicionará geográficamente el mapa al cargar y el zoom inicial con el que enfoca el mapa).

En cuanto a la variable centerPoint, nos fijamos en que se le hace referencia de dos maneras. O bien se accede a la propiedad latitud mediante $centerPoint[‘lat’] o bien, como esta variable contiene un array, la latitud está en la posición 0 y la longitud en la posición 1, por lo que para acceder a la latitud en este caso, se hace con $centerPoint[0]. Los signos de puntuación ‘??’ se usan como un if. Si dentro de la variable centerPoint existe una propiedad llamada ‘lat’, entonces accede a su valor, si no, accede al valor de la posición 0 del array.

Ahora, en caso de que queramos personalizar el mapa (por ejemplo, lo que he querido hacer era limitar el mapa para que no puedieras arrastrar más allá de España y también no poder sobrepasar unos ciertos niveles de zoom, para no poder ver el mapamundi entero), existe un objeto llamado options, que como el nombre indica, contiene todas las personalizaciones que le añadas para ajustar el mapa a tus necesidades.


### Personalización del mapa
Ahora, voy a detallar qué hace cada propiedad:
minZoom: hace referencia al zoom mínimo que se puede hacer. De manera más sencilla, el zoom permite valores del 0 al 18, siendo 0 el zoom más pequeño del mundo (permite ver el mapamundi) y 18 el zoom más grande (permitiendo ver las calles de una ciudad al detalle). Entonces con el minZoom, podemos restringir el zoom para que no se pueda ir más allá de ver un país (que es el ajuste que he aplicado). De esta manera, no se puede ver el mapamundi por mucho que intentes minimizar el mapa.

- maxZoom: lo contrario al minZoom, es el zoom máximo que se puede hacer. Como el valor más grande es 18, permitiendo ver las calles al detalle, puedes poner un valor como 15, para así, que el usuario no pueda maximizar el mapa más allá de este valor. Por ahora, lo he dejado en 18, pero si más adelante no nos parece bien este ajuste, siempre se puede cambiar.

- maxBounds: restringe hasta cuánto el usuario puede arrastrar el mapa. Un ejemplo claro es este código, dónde he limitado el movimiento del mapa para que no puedas salir de España (falta ajustarlo un poco más) y así no puedas irte al continente americano. Como esta aplicación está hecha para Tavernes, no tiene sentido permitir al usuario irse a otro país o continente. 
Sobre cómo funciona esta propiedad, como ya he dicho, restringe el movimiento, y lo hace en forma de cuadrado. De manera que, se le pueden pasar dos objetos o dos arrays. Uno de ellos hace referencia a la esquina inferior izquierda, y el otro a la esquina superior derecha.

- maxBoundsViscosity: esta propiedad acompaña a la propiedad maxBounds, y es la que permite que el usuario no se salga de los límites establecidos. Acepta valores de entre 0.0 y 1.0, de manera que si no se pone esta propiedad es como si tuviera valor 0.0 . Su funcionamiento es el siguiente: cuando tiene un valor pequeño (pongamos 0.0) lo que hace es permitir al usuario salirse un poco de los límites establecidos por maxBounds, pero al momento en que se suelte el ratón, devolverá al usuario al rango permitido por maxBounds. Cuanto más alto sea el valor, más difícil será salirse de los límites, de manera que si se deja en 1.0, el usuario no podrá salirse ni un milímetro de los límites establecidos.


# Perfeccionar la fusion del frontend y backend

## Importar cambios de github a proyecto local

- git fetch origin →  para recibir los datos sobre todas las ramas que hay en github y así poder incorporar los cambios

- git checkout <rama> →  cambiarse a la rama sobre la que se desea incorporar los cambios. Si se quisiera crear una nueva rama a partir de una existente a modo de copia de seguridad, nos posicionamos en esta rama y se ejecuta git checkout -b <nombre-rama> para crear una rama nueva y cambiarse a ella.

- git merge origin/nombre-rama → fusiona los cambios realizados en la rama de github que se haya escrito, sobre la rama en la que se está posicionado en el momento de la ejecución del comando.

## Instalar dependencias en el frontend
Dentro de visual studio, se abre una terminal y se accede a la carpeta de frontend. Para poder instalar las dependencias, es necesario ejecutar el comando npm install, pero para asegurarse de que se va a ejecutar el comando con la versión de Ubuntu y no con la de Windows, es necesario asegurarse primero con el comando which npm.
    • Si la respuesta es: /usr/bin/npm o /home/<usuario>/.nvm/... → Está todo correcto, se puede proseguir con la instalación de dependencias.
    • Si la respuesta es: /mnt/c/Program Files/... →  ¡Alto! Estás usando el Node de Windows dentro de Linux. Tienes que instalar el de Linux con este comando antes de seguir: sudo apt update && sudo apt install nodejs npm

Ahora que nos hemos asegurado de tener la versión correcta de npm, podemos instalar las dependencias con npm install, y luego de instalar las dependencias, arrancar el frontend con npm run dev

## Errores personales

Al instalar npm de Ubuntu, se me ha instalado una versión más antigua que la del proyecto de Vue. Para instalar la versión 20 de node, hay que hacer lo siguiente:

### Descargar el instalador de la versión 20
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -

### Instalar la nueva versión
sudo apt-get install -y nodejs

### Verificar que se actualizó
Escribe este comando y asegúrate de que el número que sale empiece por v20...:
node -v

Ahora, para instalar las dependencias que faltan (vue-router) ejecuto el comando npm install vue-router (primero hay que parar el frontend para la instalación, y luego se vuelve a levantar con npm run dev)

## Configuración del mapa
Debido a que ahora se muestra el mapa por medio de Vue y no se muestra directamente desde una vista del backend, hay que mover la configuración del backend al frontend, y hacer unos arreglos en el controlador para poder transferir variables a Vue.

### Controlador

El código de antes a mi cambio, era muy básico, y simplemente recogía los puntos de recogida y se los pasaba a Vue, faltaba por recoger los vendedores relacionados a esos puntos para así mostrar el nombre de la empresa del vendedor en los marcadores del mapa.

En la primera imagen, he añadido las opciones de configuración para aplicar las delimitaciones del zoom y del movimiento del usuario.
En la segunda imagen, al cambiar nombres de variables en el controlador y además recoger los vendedores, he modificado los nombres de las variables asociadas a los marcadores (anteriormente llamada ‘puntos’) y también he incorporado el nombre de la empresa del vendedor en los marcadores.
