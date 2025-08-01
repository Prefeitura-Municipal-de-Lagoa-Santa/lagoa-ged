<template>
  <div class="relative" ref="notificationRef">
    <!-- Botão do Sininho -->
    <button
      @click="toggleDropdown"
      class="relative inline-flex items-center p-2 text-sm font-medium text-center text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white rounded-lg focus:ring-4 focus:outline-none focus:ring-gray-200 dark:focus:ring-gray-700"
    >
      <Bell class="w-5 h-5" />
      
      <!-- Badge de contagem -->
      <span
        v-if="unreadCount > 0"
        class="absolute inline-flex items-center justify-center w-5 h-5 text-xs font-bold text-white bg-red-500 border-2 border-white rounded-full -top-1 -right-1 dark:border-gray-900"
      >
        {{ unreadCount > 99 ? '99+' : unreadCount }}
      </span>
    </button>

    <!-- Dropdown de Notificações -->
    <div
      v-if="isOpen"
      class="absolute right-0 z-[9999] w-80 mt-2 bg-white border border-gray-200 rounded-lg shadow-lg dark:bg-zinc-800 dark:border-zinc-700"
      style="z-index: 9999 !important;"
    >
      <!-- Header -->
      <div class="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
          Notificações
        </h3>
        <button
          v-if="notifications.length > 0"
          @click="markAllAsRead"
          class="text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
        >
          Marcar todas como lidas
        </button>
      </div>

      <!-- Lista de Notificações -->
      <div class="max-h-96 overflow-y-auto">
        <template v-if="notifications.length > 0">
          <div
            v-for="notification in notifications"
            :key="notification.id"
            class="p-4 border-b border-zinc-100 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700 cursor-pointer"
            @click="markAsRead(notification.id)"
          >
            <div class="flex items-start space-x-3">
              <!-- Ícone por tipo -->
              <div class="flex-shrink-0">
                <div
                  :class="getNotificationIconClass(notification.type)"
                  class="w-8 h-8 rounded-full flex items-center justify-center"
                >
                  <component
                    :is="getNotificationIcon(notification.type)"
                    class="w-4 h-4"
                  />
                </div>
              </div>
              
              <!-- Conteúdo -->
              <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-gray-900 dark:text-white">
                  {{ notification.title }}
                </p>
                <p
                  v-if="notification.message"
                  class="text-sm text-gray-500 dark:text-gray-400 mt-1"
                  v-html="notification.message"
                ></p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                  {{ notification.time_ago }}
                </p>
              </div>
              
              <!-- Indicador de não lida -->
              <div
                v-if="!notification.is_read"
                class="w-2 h-2 bg-blue-600 rounded-full"
              ></div>
            </div>
          </div>
        </template>
        
        <!-- Estado vazio -->
        <div
          v-else
          class="p-8 text-center text-gray-500 dark:text-gray-400"
        >
          <Bell class="w-12 h-12 mx-auto mb-4 opacity-50" />
          <p>Nenhuma notificação recente</p>
        </div>
      </div>

      <!-- Footer -->
      <div
        v-if="notifications.length > 0"
        class="p-4 border-t border-gray-200 dark:border-gray-700"
      >
        <button
          @click="openNotificationCenter"
          class="w-full text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-center"
        >
          Ver todas as notificações
        </button>
      </div>
    </div>

    <!-- Modal da Central de Notificações -->
    <NotificationCenter
      v-if="showNotificationCenter"
      @close="showNotificationCenter = false"
      @notification-updated="refreshNotifications"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Bell, CheckCircle, XCircle, AlertTriangle, Info } from 'lucide-vue-next'
import axios from 'axios'
import NotificationCenter from './NotificationCenter.vue'

// Types
interface Notification {
  id: string
  title: string
  message?: string
  type: string
  category?: string
  is_read: boolean
  created_at: string
  time_ago: string
}

// Refs
const notificationRef = ref<HTMLElement>()
const isOpen = ref(false)
const notifications = ref<Notification[]>([])
const unreadCount = ref(0)
const showNotificationCenter = ref(false)

// Polling
let pollingInterval: number | null = null

// Métodos
const toggleDropdown = () => {
  isOpen.value = !isOpen.value
  if (isOpen.value) {
    fetchNotifications()
  }
}

const fetchNotifications = async () => {
  try {
    const response = await axios.get('/documents/notifications')
    if (response.data.success) {
      notifications.value = response.data.notifications || []
      unreadCount.value = response.data.unread_count || 0
    }
  } catch (error) {
    console.error('Erro ao buscar notificações:', error)
  }
}

const markAsRead = async (notificationId: string) => {
  try {
    await axios.post(`/api/notifications/${notificationId}/read`)
    // Atualizar localmente
    const notification = notifications.value.find(n => n.id === notificationId)
    if (notification) {
      notification.is_read = true
      unreadCount.value = Math.max(0, unreadCount.value - 1)
    }
  } catch (error) {
    console.error('Erro ao marcar notificação como lida:', error)
  }
}

const markAllAsRead = async () => {
  try {
    await axios.post('/api/notifications/read-all')
    notifications.value.forEach(n => n.is_read = true)
    unreadCount.value = 0
  } catch (error) {
    console.error('Erro ao marcar todas as notificações como lidas:', error)
  }
}

const openNotificationCenter = () => {
  showNotificationCenter.value = true
  isOpen.value = false
}

const refreshNotifications = () => {
  fetchNotifications()
}

// Helpers
const getNotificationIcon = (type: string) => {
  switch (type) {
    case 'success': return CheckCircle
    case 'error': return XCircle
    case 'warning': return AlertTriangle
    default: return Info
  }
}

const getNotificationIconClass = (type: string) => {
  switch (type) {
    case 'success': return 'bg-green-100 text-green-600 dark:bg-green-900 dark:text-green-400'
    case 'error': return 'bg-red-100 text-red-600 dark:bg-red-900 dark:text-red-400'
    case 'warning': return 'bg-yellow-100 text-yellow-600 dark:bg-yellow-900 dark:text-yellow-400'
    default: return 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-400'
  }
}

// Click outside
const handleClickOutside = (event: MouseEvent) => {
  if (notificationRef.value && !notificationRef.value.contains(event.target as Node)) {
    isOpen.value = false
  }
}

// Polling
const startPolling = () => {
  if (pollingInterval) return
  pollingInterval = setInterval(fetchNotifications, 5000)
}

const stopPolling = () => {
  if (pollingInterval) {
    clearInterval(pollingInterval)
    pollingInterval = null
  }
}

// Lifecycle
onMounted(() => {
  document.addEventListener('click', handleClickOutside)
  fetchNotifications()
  startPolling()
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  stopPolling()
})
</script>
