# Documentación Backend - ProxiMarkt

### 1. Base de Datos y Modelos (Eloquent)

- **Concepto:** Se sustituyen las consultas SQL manuales por el uso de **Eloquent ORM** para una gestión más eficiente y segura de la base de datos.
- **Estructura:** Creación y conexión de los modelos principales del sistema: `User`, `SellerProfile`, `Product`, `Order` y `OrderLine`.
- **Relaciones:** Definición de la interacción entre modelos.
    - *Ejemplo:* Un `User` tiene un `SellerProfile`; un Vendedor tiene muchos `Product`.

### 2. API y Rutas

- **Instalación:** Ejecución del comando `install:api` para habilitar el archivo `routes/api.php` y configurar `bootstrap/app.php` (necesario en versiones recientes de Laravel).
- **Routing:** Implementación de `Route::apiResource('users', ...)` para generar automáticamente las 5 rutas estándar del CRUD y adaptar el código a los requisitos del proyecto.
- **Endpoint User:** Creación de una ruta específica para recuperar los datos del usuario autenticado (dueño del token actual).

### 3. Lógica de Usuarios (CRUD)

- **Validación:** Implementación de reglas estrictas directamente en el controlador: `required`, `email`, `unique`.
- **Excepciones:** Manejo de la validación de "unicidad" durante la edición, asegurando que se ignore el ID del propio usuario que se está editando para evitar conflictos.

### 4. Seguridad y Autenticación (Sanctum)

- **Login:** El `AuthController` verifica las credenciales (email y contraseña) utilizando `Hash::check`.
- **Tokens:** Uso del trait `HasApiTokens` en el modelo `User` para generar y entregar tokens de acceso mediante el método `createToken`.
- **Protección:** Implementación de `ValidationException` para detener el proceso de login si las credenciales son incorrectas, manteniendo un formato de respuesta de error estándar.

### 5. Herramientas y Correcciones

- **Postman:** Uso de esta herramienta para realizar pruebas exhaustivas (testing) de todos los endpoints y funcionalidades desarrolladas.

---

> **Resumen del Sprint 2:**
> Completando estos puntos, disponemos de una API funcional que permite una **gestión total de usuarios**, sentando las bases para el desarrollo del comercio.