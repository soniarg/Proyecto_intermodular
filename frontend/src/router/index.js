import { createRouter, createWebHistory } from 'vue-router'
import LoginView from '../views/LoginView.vue'
import UsersView from '../views/UsersView.vue'
import MapView from '../views/MapView.vue'
import InventoryView from '../views/InventoryView.vue'
import PickupPointView from '../views/PickupPointView.vue'

// Puerto de Vue: 5174

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      name: 'login',
      component: LoginView
    },
    {
      path: '/users',
      name: 'users',
      component: () => import('../views/UsersView.vue')
    },
    {
    path: '/mapas',
    name: 'Map',
    component: MapView
    },
    {
      path: '/seller/inventory',
      name: 'seller',
      component: InventoryView
    },
    {
      path: '/seller/pickup-points',
      name: 'pickup-points',
      component: PickupPointView,
      component: () => import('../views/MapView.vue')
    },
    {
      path: '/chat/:id',
      name: 'chat',
      component: () => import('../views/ChatView.vue')
    }
  ]
})

export default router