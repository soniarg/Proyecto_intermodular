### Documentación Gestión de Inventario y Puntos de Entrega de Vendedores:

### 1 Backend (Arquitectura y Datos):

### Controladores y Lógica:

Creación de los controllers (ProductController, PickupPointController) de las tablas 'products' y 'pickup_points' que están asociados a un vendedor (user_id) y un usuario autenticado.

### Rutas API:

Se ha implementado el CRUD de productos y puntos de entrega bajo un middleware de autenticación, permitiendo este CRUD.

### Datos de prueba:

Las factories de productos y puntos de entrega se han creado con el objetivo de simular datos de prueba y rellenar la base de datos. 

En cuanto a los seeders, se han creado puntos de entrega en la ciudad de Valencia y algunos aleatorios para testear, así como un usuario vendedor, su perfil y productos asociados a ese vendedor (también de prueba).


### 2 Frontend (Vistas, Componentes y Rutas):


### Gestión de Rutas:

Se han añadido dos rutas nuevas al archivo index.js localizado en frontend/router que se corresponden con las páginas del inventario de productos y los puntos de entrega llamadas seller/inventory y seller/pickup-points.

### CRUD:

En la ubicación frontend/api aparecen los archivos PickupPoints.js y Products.js referidos al CRUD de productos y puntos de entrega.

### Vistas:

Asociados a esas rutas, se han creado las vistas de InventoryView.vue y PickupPointView.vue que muestra el listado de productos, edición y eliminación de productos del inventario y, en su caso, puntos de entrega con sus estilos css correspondientes localizados en la carpeta frontend/views.


### Componentes:

Por otro lado, se encuentran los componentes PickupPointForm.vue y ProductForm.vue en los que aparece un formulario para la creación y edición de productos y puntos de entrega ubicados en la carpeta frontend/components.