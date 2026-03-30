import { createRouter, createWebHistory } from 'vue-router'

import Home from '../pages/Home.vue'
import TransactionDetailPage from '../pages/TransactionDetailPage.vue'

export default createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: Home },
    { path: '/transactions/:id', name: 'transactionDetail', component: TransactionDetailPage, props: true },
  ],
})

