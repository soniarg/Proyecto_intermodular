import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    // --- PÁGINA PRINCIPAL ---
    {
      path: '/',
      name: 'home',
      component: HomeView
    },

    // --- LOGIN & REGISTRO ---
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue')
    },
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/RegisterView.vue')
    },

    // --- PERFIL DE USUARIO ---
    {
      path: '/perfil',
      name: 'profile',
      component: () => import('../views/ProfileView.vue'),
      meta: { requiresAuth: true } // Recomendado: Proteger ruta
    },

    // --- USUARIOS (ADMIN O LISTADO) ---
    {
      path: '/users',
      name: 'users',
      component: () => import('../views/UsersView.vue')
    },

    // --- MAPAS ---
    {
      path: '/mapas',
      name: 'Map',
      component: () => import('../views/MapView.vue')
    },

    // --- 🛒 MERCADO (SPRINT 4 - TUS CAMBIOS) --- 
    {
      path: '/marketplace',
      name: 'marketplace',
      component: () => import('../views/MarketplaceView.vue')
    },
    
    // --- 📦 MIS PEDIDOS (SPRINT 4 - TUS CAMBIOS) ---
    {
      path: '/my-purchases',
      name: 'my-purchases',
      component: () => import('../views/MyPurchasesView.vue'),
      meta: { requiresAuth: true }
    },

    {
      path: '/my-sales',
      name: 'my-sales',
      component: () => import('../views/MySalesView.vue'),
      meta: { requiresAuth: true }
    },

    {
      path: '/seller/orders/:id',  // :id es el parámetro dinámico (ej: 3, 4, 5)
      name: 'seller-order-details',
      component: () => import('../views/SellerOrderDetailsView.vue'),
      meta: { requiresAuth: true }
    },

    // --- 🏪 GESTIÓN VENDEDOR ---
    {
      path: '/seller/inventory',
      name: 'inventory',
      component: () => import('../views/InventoryView.vue'),
      meta: { requiresAuth: true }
    },
    {
      path: '/seller/pickup-points',
      name: 'pickup-points',
      // NOTA: Asegúrate de que el archivo se llame 'PickupPointsView.vue' (plural) 
      // como te pasé en el código anterior.
      component: () => import('../views/PickupPointView.vue'),
      meta: { requiresAuth: true }
    },

    // --- CHAT ---
    {
      path: '/chat/:id',
      name: 'chat',
      component: () => import('../views/ChatView.vue'),
      meta: { requiresAuth: true }
    },

    // --- ZONA DE PRUEBAS (FUSIONADO: TUYO Y DE TU COMPAÑERO) ---
    {
      path: '/prueba',
      name: 'prueba',
      component: () => import('../views/PruebaView.vue')
    },
    {
      path: '/prueba/componente',
      name: 'prueba-componente',
      component: () => import('../views/PruebaComponentePadre.vue')
    },
    {
      path: '/prueba/carrito',
      name: 'prueba-carrito',
      component: () => import('../views/PruebaCarritoView.vue')
    },
    {
      path: '/prueba/login',
      name: 'prueba-login',
      component: () => import('../views/PruebaLoginView.vue')
    },
    {
      path: '/practica',
      name: 'practica',
      component: () => import('../views/PracticaView.vue')
    }
  ]
})

// GUARDIA GLOBAL (Opcional, pero recomendado para evitar errores 401)
router.beforeEach((to, from, next) => {
    const isAuthenticated = localStorage.getItem('auth_token');
    if (to.meta.requiresAuth && !isAuthenticated) {
        next('/login');
    } else {
        next();
    }
});

export default router