<template>
  <!-- Modal Backdrop -->
  <div class="fixed inset-0 z-[99999] overflow-y-auto" style="z-index: 99999 !important;">
    <!-- Backdrop overlay -->
    <div class="fixed inset-0 bg-black opacity-20 transition-opacity" @click="$emit('close')"></div>
    
    <!-- Modal container -->
    <div class="flex min-h-full items-center justify-center p-4 relative z-[99999]">
      <!-- Modal content -->
      <div class="relative bg-white dark:bg-zinc-900 rounded-xl shadow-2xl max-w-4xl w-full max-h-[85vh] overflow-hidden border border-gray-200 dark:border-zinc-700">
        
        <!-- Header fixo -->
        <div class="flex items-center justify-between p-6 border-b border-gray-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
          <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
            Central de Notificações
          </h3>
          <button
            @click="$emit('close')"
            class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100 p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-800 transition-colors"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Filtros fixos -->
        <div class="p-4 bg-gray-50 dark:bg-zinc-800 border-b border-gray-200 dark:border-zinc-700">
          <div class="flex items-center space-x-4">
            <select
              v-model="selectedCategory"
              @change="fetchNotifications"
              class="text-sm border border-gray-300 dark:border-gray-600 dark:bg-zinc-700 dark:text-white rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Todas as categorias</option>
              <option value="job">Jobs</option>
              <option value="system">Sistema</option>
              <option value="user_action">Ações do usuário</option>
              <option value="import">Importações</option>
            </select>
            
            <select
              v-model="selectedStatus"
              @change="fetchNotifications"
              class="text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-md px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            >
              <option value="">Todas</option>
              <option value="unread">Não lidas</option>
              <option value="read">Lidas</option>
            </select>
            
            <button
              @click="fetchNotifications"
              class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
            >
              Atualizar
            </button>
          </div>
        </div>

        <!-- Content scrollável -->
        <!-- Content scrollável -->
        <div class="p-6 overflow-y-auto max-h-[calc(85vh-200px)]">
          <!-- Loading state -->
          <div v-if="loading" class="text-center py-8">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto mb-4"></div>
            <p class="text-gray-600 dark:text-gray-400">Carregando notificações...</p>
          </div>
          
          <!-- Notifications list -->
          <div v-else-if="notifications.length > 0" class="space-y-4">
            <div
              v-for="notification in notifications"
              :key="notification.id"
              class="p-4 border border-gray-200 dark:border-gray-600 rounded-xl bg-white dark:bg-zinc-800 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-all duration-200 shadow-sm"
              :class="{ 'ring-2 ring-blue-300 dark:ring-blue-500 bg-blue-50 dark:bg-blue-900/20': !notification.is_read }"
            >
            <div class="flex items-start justify-between">
              <div class="flex items-start space-x-3">
                <!-- Icon by type -->
                <div class="flex-shrink-0 mt-1">
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
                
                <div class="flex-1">
                  <div class="flex items-center space-x-2">
                    <h4 class="font-medium text-gray-900 dark:text-white">{{ notification.title }}</h4>
                    <span
                      v-if="!notification.is_read"
                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 dark:bg-blue-800 text-blue-800 dark:text-blue-200"
                    >
                      Nova
                    </span>
                  </div>
                  <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">{{ notification.message }}</p>
                  <div class="text-xs text-gray-500 dark:text-gray-400 mt-2 flex items-center space-x-4">
                    <span>{{ notification.time_ago }}</span>
                    <span
                      v-if="notification.category"
                      class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-300"
                    >
                      {{ getCategoryLabel(notification.category) }}
                    </span>
                  </div>
                </div>
              </div>
              
              <!-- Actions -->
              <div class="flex items-center space-x-2 ml-4">
                <button
                  v-if="!notification.is_read"
                  @click="markAsRead(notification.id)"
                  class="p-2 text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 hover:bg-blue-100 dark:hover:bg-blue-900/20 rounded-md transition-colors"
                  title="Marcar como lida"
                >
                  <EyeOff/>
                </button>
                <button
                  @click="deleteNotification(notification.id)"
                  class="p-2 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/20 rounded-md transition-colors"
                  title="Excluir"
                >
                  <Trash2/>
                </button>
              </div>
            </div>
          </div>
        </div>
        
          <!-- Empty state -->
          <div v-else class="text-center py-12 text-gray-500 dark:text-gray-400">
            <Bell class="w-16 h-16 mx-auto mb-4 opacity-50" />
            <p class="text-lg font-medium">Nenhuma notificação encontrada</p>
            <p class="text-sm">Tente ajustar os filtros ou aguarde novas notificações.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { Eye, Trash2, Bell, CheckCircle, XCircle, AlertTriangle, Info, FileText, EyeOff } from 'lucide-vue-next'
import axios from 'axios'

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

// Emits
const emit = defineEmits<{
  close: []
  'notification-updated': []
}>()

// Refs
const notifications = ref<Notification[]>([])
const totalNotifications = ref(0)
const loading = ref(false)
const selectedCategory = ref('')
const selectedStatus = ref('')

// Methods
const fetchNotifications = async () => {
  loading.value = true
  try {
    console.log('Fetching notifications...') // Debug
    
    const params = new URLSearchParams({
      page: '1',
      per_page: '20',
    })
    
    if (selectedCategory.value) {
      params.append('category', selectedCategory.value)
    }
    
    if (selectedStatus.value) {
      params.append('status', selectedStatus.value)
    }
    
    const response = await axios.get(`/api/notifications?${params.toString()}`)
    
    console.log('Notifications response:', response.data) // Debug
    
    if (response.data.success) {
      notifications.value = response.data.data
      totalNotifications.value = response.data.total
    }
  } catch (error) {
    console.error('Erro ao buscar notificações:', error)
  } finally {
    loading.value = false
  }
}

const markAsRead = async (notificationId: string) => {
  try {
    await axios.post(`/api/notifications/${notificationId}/read`)
    const notification = notifications.value.find(n => n.id === notificationId)
    if (notification) {
      notification.is_read = true
    }
    emit('notification-updated')
  } catch (error) {
    console.error('Erro ao marcar notificação como lida:', error)
  }
}

const deleteNotification = async (notificationId: string) => {
  try {
    await axios.delete(`/api/notifications/${notificationId}`)
    notifications.value = notifications.value.filter(n => n.id !== notificationId)
    totalNotifications.value = notifications.value.length
    emit('notification-updated')
  } catch (error) {
    console.error('Erro ao excluir notificação:', error)
  }
}

const getCategoryLabel = (category: string) => {
  const labels: Record<string, string> = {
    job: 'Job',
    system: 'Sistema',
    user_action: 'Ação do usuário',
    import: 'Importação',
    permission: 'Permissão'
  }
  return labels[category] || category
}

const getNotificationIcon = (type: string) => {
  switch (type) {
    case 'success': return CheckCircle
    case 'error': return XCircle
    case 'warning': return AlertTriangle
    case 'import': return FileText
    default: return Info
  }
}

const getNotificationIconClass = (type: string) => {
  switch (type) {
    case 'success': return 'bg-green-100 dark:bg-green-900/20 text-green-600 dark:text-green-400'
    case 'error': return 'bg-red-100 dark:bg-red-900/20 text-red-600 dark:text-red-400'
    case 'warning': return 'bg-yellow-100 dark:bg-yellow-900/20 text-yellow-600 dark:text-yellow-400'
    case 'import': return 'bg-purple-100 dark:bg-purple-900/20 text-purple-600 dark:text-purple-400'
    default: return 'bg-blue-100 dark:bg-blue-900/20 text-blue-600 dark:text-blue-400'
  }
}

// Handle ESC key
const handleKeyDown = (event: KeyboardEvent) => {
  if (event.key === 'Escape') {
    emit('close')
  }
}

// Lifecycle
onMounted(async () => {
  console.log('NotificationCenter mounted') // Debug
  document.addEventListener('keydown', handleKeyDown)
  await fetchNotifications()
})

onBeforeUnmount(() => {
  document.removeEventListener('keydown', handleKeyDown)
})
</script>