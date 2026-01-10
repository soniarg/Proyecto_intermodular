# Sprint 2 - Gestión de Usuarios, Login y Mapas

## Rama: `sprint2-Sonia`

---

## Objetivo
Implementar la parte del **frontend del mapa** utilizando Vue.js y Leaflet, integrado en la estructura de la aplicación sin depender del backend todavía.

---

## Qué se ha implementado

1. **Mapa interactivo**:
   - Se usa Leaflet para mostrar un mapa centrado en Tavernes (de momento es fijo para testear).
   - Marcador de prueba incluido en caso de que el backend no responda.
   - Preparado para consumir datos reales desde el endpoint `/pickup_points` cuando esté disponible.

2. **Frontend Vue**:
   - Vista `MapView.vue` creada.
   - Estilos mínimos aplicados para que el mapa se vea correctamente.

3. **Conexión segura con backend**:
   - Fetch con `try/catch` para evitar errores si el backend no está listo.
   - Fallback con marcador de prueba.
   - Preparado para integración futura con datos reales de usuarios o puntos de recogida.

---

## Cómo probarlo

1. Instalar dependencias del frontend:

```bash
cd frontend
npm install
npm run dev
