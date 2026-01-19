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

    // --- LOGIN ---
    {
      path: '/login',
      name: 'login',
      component: () => import('../views/LoginView.vue')
    },

    {
      path: '/perfil',
      name: 'profile',
      component: () => import('../views/ProfileView.vue')
    },

    // --- REGISTRO (¡NUEVA RUTA AÑADIDA!) ---
    {
      path: '/register',
      name: 'register',
      component: () => import('../views/RegisterView.vue')
    },

    // --- USUARIOS ---
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

    // --- INVENTARIO VENDEDOR ---
    {
      path: '/seller/inventory',
      name: 'seller',
      component: () => import('../views/InventoryView.vue')
    },

    // --- PUNTOS DE RECOGIDA ---
    {
      path: '/seller/pickup-points',
      name: 'pickup-points',
      component: () => import('../views/PickupPointView.vue')
    },

    // --- CHAT ---
    {
      path: '/chat/:id',
      name: 'chat',
      component: () => import('../views/ChatView.vue')
    }
  ]
})

export default router