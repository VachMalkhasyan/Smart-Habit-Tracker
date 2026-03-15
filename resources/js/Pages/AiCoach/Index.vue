<script setup>
import { ref, computed, onMounted, nextTick } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import axios from 'axios'
import { marked } from 'marked'
import { v4 as uuidv4 } from 'uuid'
import { useUnsavedChanges } from '@/composables/useUnsavedChanges'
import { useToast } from '@/composables/useToast'
import { 
    Bot, Send, Plus, Trash2, Menu, X, Battery, Edit2, 
    Check, Sparkles, TrendingDown, Target, BarChart2 
} from 'lucide-vue-next'

const conversations = ref([])
const activeConversation = ref(null)
const messages = ref([])
const input = ref('')
const isLoading = ref(false)
const isSidebarOpen = ref(false)
const isEditingTitle = ref(false)
const editableTitle = ref('')

const messagesContainer = ref(null)
const { addToast } = useToast()

const isDirty = computed(() => input.value.trim().length > 0)
useUnsavedChanges(isDirty)

const totalTokensUsing = computed(() => {
    return conversations.value.reduce((sum, conv) => sum + (conv.tokens_used || 0), 0)
})

const starterCards = [
    { title: "What habit should I focus on this week? 🎯", icon: Target },
    { title: "Why am I struggling with my streaks? 🔥", icon: TrendingDown },
    { title: "Give me a personalized morning routine ☀️", icon: Sparkles },
    { title: "Analyze my progress this week 📊", icon: BarChart2 }
]

const fetchConversations = async () => {
    try {
        const { data } = await axios.get('/ai/conversations')
        conversations.value = data
    } catch (e) {
        addToast({ type: 'error', message: 'Failed to load conversations.' })
    }
}

const loadConversation = async (conv) => {
    isSidebarOpen.value = false
    activeConversation.value = conv
    messages.value = []
    
    try {
        const { data } = await axios.get(`/ai/conversations/${conv.id}`)
        activeConversation.value = data
        messages.value = data.messages || []
        scrollToBottom()
    } catch (e) {
        addToast({ type: 'error', message: 'Failed to load messages.' })
    }
}

const startNewChat = async () => {
    try {
        const { data } = await axios.post('/ai/conversations', { title: 'New Conversation' })
        conversations.value.unshift(data)
        await loadConversation(data)
    } catch (e) {
        addToast({ type: 'error', message: 'Failed to create chat.' })
    }
}

const selectStarter = async (cardTitle) => {
    await startNewChat()
    input.value = cardTitle
    await sendMessage()
}

const deleteConversation = async (convId) => {
    if (!confirm('Are you sure you want to delete this conversation?')) return
    
    try {
        await axios.delete(`/ai/conversations/${convId}`)
        conversations.value = conversations.value.filter(c => c.id !== convId)
        if (activeConversation.value?.id === convId) {
            activeConversation.value = null
            messages.value = []
        }
        addToast({ type: 'success', message: 'Conversation deleted.' })
    } catch (e) {
        addToast({ type: 'error', message: 'Failed to delete conversation.' })
    }
}

const updateTitle = async () => {
    if (!editableTitle.value.trim() || !activeConversation.value) return
    isEditingTitle.value = false
    
    try {
        const payload = { title: editableTitle.value.trim() }
        activeConversation.value.title = payload.title
        const idx = conversations.value.findIndex(c => c.id === activeConversation.value.id)
        if (idx !== -1) conversations.value[idx].title = payload.title
        
        await axios.patch(`/ai/conversations/${activeConversation.value.id}`, payload)
    } catch (e) {
        addToast({ type: 'error', message: 'Failed to rename conversation.' })
    }
}

const enableTitleEditing = () => {
    isEditingTitle.value = true
    editableTitle.value = activeConversation.value.title
}

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTo({
                top: messagesContainer.value.scrollHeight,
                behavior: 'smooth'
            })
        }
    })
}

const autoGrow = (e) => {
    const el = e.target
    el.style.height = 'auto'
    const newHeight = Math.min(el.scrollHeight, 120) // max 4 lines
    el.style.height = newHeight + 'px'
}

const sendMessage = async () => {
    if (!input.value.trim() || isLoading.value) return

    const userMessage = input.value.trim()
    input.value = ''
    
    // Auto-grow reset
    const textarea = document.getElementById('chat-input')
    if (textarea) textarea.style.height = 'auto'

    messages.value.push({
        id: uuidv4(),
        role: 'user',
        content: userMessage,
        created_at: new Date().toISOString()
    })

    isLoading.value = true
    scrollToBottom()

    try {
        const { data } = await axios.post(`/ai/conversations/${activeConversation.value.id}/chat`, { 
            message: userMessage 
        })
        
        messages.value.push({
            id: uuidv4(),
            role: 'assistant',
            content: data.response,
            created_at: new Date().toISOString()
        })
        
        // Auto-name
        if (data.conversation_title) {
            activeConversation.value.title = data.conversation_title
            const conv = conversations.value.find(c => c.id === activeConversation.value.id)
            if (conv) conv.title = data.conversation_title
        }
        
        // Update token stat locally
        if (data.tokens_used && activeConversation.value) {
            activeConversation.value.tokens_used += data.tokens_used
            const idx = conversations.value.findIndex(c => c.id === activeConversation.value.id)
            if (idx !== -1) conversations.value[idx].tokens_used += data.tokens_used
        }
    } catch (e) {
        messages.value.push({
            id: uuidv4(),
            role: 'assistant',
            content: '⚠️ Something went wrong. Please try again.',
            created_at: new Date().toISOString()
        })
    } finally {
        isLoading.value = false
        scrollToBottom()
    }
}

const renderMarkdown = (text) => {
    if (!text) return ''
    return marked.parse(text, { breaks: true })
}

const formatTime = (isoString) => {
    if (!isoString) return ''
    const date = new Date(isoString)
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })
}

const handleKeydown = (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault()
        sendMessage()
    }
}

onMounted(() => {
    fetchConversations()
})
</script>

<template>
    <AppLayout title="AI Coach">
        <div class="flex h-[calc(100vh-4rem)] overflow-hidden bg-gray-50 dark:bg-gray-900 absolute left-0 right-0 top-16 md:static md:h-[calc(100vh-theme('spacing.16'))]">
            
            <!-- Mobile Sidebar Backdrop -->
            <div v-if="isSidebarOpen" 
                 class="fixed inset-0 bg-gray-900/50 z-40 md:hidden"
                 @click="isSidebarOpen = false"></div>

            <!-- Left Sidebar -->
            <aside :class="[
                'fixed md:static inset-y-0 left-0 z-50 w-72 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 flex flex-col transition-transform duration-300 ease-in-out md:translate-x-0',
                isSidebarOpen ? 'translate-x-0' : '-translate-x-full'
            ]">
                <!-- Sidebar Header -->
                <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <div class="flex items-center space-x-2 text-indigo-600 dark:text-indigo-400">
                        <Bot class="w-6 h-6" />
                        <h2 class="text-lg font-bold">AI Coach</h2>
                    </div>
                    <button @click="isSidebarOpen = false" class="md:hidden text-gray-500 hover:text-gray-700 dark:hover:text-gray-300">
                        <X class="w-5 h-5" />
                    </button>
                </div>
                
                <div class="p-4">
                    <button @click="startNewChat" 
                            class="w-full flex items-center justify-center space-x-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg transition-colors shadow-sm font-medium">
                        <Plus class="w-5 h-5" />
                        <span>New Chat</span>
                    </button>
                </div>

                <!-- Conversation List -->
                <div class="flex-1 overflow-y-auto px-3 space-y-1">
                    <div v-if="conversations.length === 0" class="text-center p-6 text-gray-400 dark:text-gray-500 text-sm">
                        No conversations yet. Start one! 👋
                    </div>
                    
                    <button v-for="conv in conversations" :key="conv.id"
                            @click="loadConversation(conv)"
                            class="group w-full flex items-center justify-between text-left p-3 rounded-lg transition-colors border border-transparent"
                            :class="[
                                activeConversation?.id === conv.id 
                                    ? 'bg-indigo-50 dark:bg-indigo-900/20 text-indigo-700 dark:text-indigo-300 border-indigo-100 dark:border-indigo-800/30' 
                                    : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700/50'
                            ]">
                        <div class="truncate pr-2">
                            <div class="text-sm font-medium truncate">{{ conv.title }}</div>
                            <div class="text-xs text-gray-400 mt-0.5 truncate">{{ formatTime(conv.updated_at) }}</div>
                        </div>
                        <button @click.stop="deleteConversation(conv.id)" 
                                class="opacity-0 group-hover:opacity-100 p-1 text-gray-400 hover:text-red-500 transition-opacity flex-shrink-0 focus:opacity-100">
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </button>
                </div>

                <!-- Sidebar Footer -->
                <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/80">
                    <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-md p-2">
                        <Battery class="w-4 h-4 mr-2 flex-shrink-0 text-emerald-500" />
                        <span class="truncate">{{ totalTokensUsing.toLocaleString() }} tokens used total</span>
                    </div>
                </div>
            </aside>

            <!-- Main Chat Area -->
            <main class="flex-1 flex flex-col min-w-0 bg-white dark:bg-gray-900 relative">
                
                <!-- Mobile Top Bar -->
                <div class="md:hidden flex items-center p-4 border-b border-gray-200 dark:border-gray-800 sticky top-0 bg-white/90 dark:bg-gray-900/90 backdrop-blur z-10">
                    <button @click="isSidebarOpen = true" class="mr-3 text-gray-600 dark:text-gray-300">
                        <Menu class="w-6 h-6" />
                    </button>
                    <div class="font-medium text-gray-900 dark:text-gray-100 truncate">
                        {{ activeConversation?.title || 'AI Coach' }}
                    </div>
                </div>

                <!-- No Conversation Selected (Welcome Screen) -->
                <div v-if="!activeConversation" class="flex-1 flex flex-col items-center justify-center p-6 overflow-y-auto">
                    <div class="text-center max-w-2xl w-full">
                        <div class="inline-flex items-center justify-center w-20 h-20 bg-indigo-50 dark:bg-indigo-900/20 rounded-full mb-6 text-4xl shadow-sm border border-indigo-100 dark:border-indigo-800/30">
                            🤖
                        </div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-900 dark:text-white mb-2">
                            Your Personal Habit Coach
                        </h1>
                        <p class="text-gray-500 dark:text-gray-400 mb-10 max-w-md mx-auto text-sm md:text-base">
                            I know your habits, streaks, goals, and mood. Ask me anything to get personalized guidance and support.
                        </p>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-left">
                            <button v-for="(card, i) in starterCards" :key="i"
                                    @click="selectStarter(card.title)"
                                    class="group flex flex-col bg-white dark:bg-gray-800 p-5 rounded-xl border border-gray-200 dark:border-gray-700 hover:border-indigo-500 hover:shadow-md transition-all">
                                <component :is="card.icon" class="w-6 h-6 text-indigo-500 mb-3" />
                                <span class="text-gray-700 dark:text-gray-200 font-medium leading-snug group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                                    {{ card.title }}
                                </span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Active Conversation Header (Desktop) -->
                <div v-else class="hidden md:flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-800 bg-white/80 dark:bg-gray-900/80 backdrop-blur z-10">
                    <div class="flex items-center flex-1 max-w-full min-w-0 pr-4">
                        <div v-if="!isEditingTitle" @click="enableTitleEditing" class="group flex items-center cursor-pointer min-w-0 max-w-xl">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-gray-100 truncate">
                                {{ activeConversation.title }}
                            </h2>
                            <Edit2 class="w-4 h-4 ml-2 opacity-0 group-hover:opacity-100 text-gray-400 transition-opacity flex-shrink-0" />
                        </div>
                        <div v-else class="flex items-center w-full max-w-xl space-x-2">
                            <input type="text" 
                                   v-model="editableTitle" 
                                   @blur="updateTitle"
                                   @keyup.enter="updateTitle"
                                   class="flex-1 bg-gray-50 dark:bg-gray-800 border border-indigo-300 dark:border-indigo-600 rounded-md px-3 py-1.5 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white text-sm"
                                   autofocus>
                            <button @click="updateTitle" class="p-1.5 bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-md hover:bg-indigo-200 dark:hover:bg-indigo-800/50">
                                <Check class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                    
                    <div class="flex items-center text-xs font-medium text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-800 px-3 py-1.5 rounded-full whitespace-nowrap border border-gray-200 dark:border-gray-700">
                        <Battery class="w-3.5 h-3.5 mr-1.5 text-indigo-500" />
                        {{ (activeConversation.tokens_used || 0).toLocaleString() }} tokens
                    </div>
                </div>

                <!-- Messages Stream -->
                <div v-if="activeConversation" ref="messagesContainer" class="flex-1 overflow-y-auto p-4 md:p-6 space-y-6">
                    <div v-for="msg in messages" :key="msg.id" 
                         :class="['flex w-full', msg.role === 'user' ? 'justify-end' : 'justify-start']">
                         
                        <!-- Assistant Avatar -->
                        <div v-if="msg.role === 'assistant'" class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center mr-3 flex-shrink-0 mt-1 border border-indigo-200 dark:border-indigo-800/30">
                            🤖
                        </div>

                        <!-- Chat Bubble -->
                        <div :class="[
                            'max-w-[85%] md:max-w-[75%] px-5 py-3.5 flex flex-col shadow-sm',
                            msg.role === 'user' 
                                ? 'bg-indigo-600 text-white rounded-2xl rounded-br-sm' 
                                : 'bg-white dark:bg-gray-800 text-gray-800 dark:text-gray-200 rounded-2xl rounded-bl-sm border border-gray-200 dark:border-gray-700'
                        ]">
                            <div v-if="msg.role === 'assistant'" 
                                 class="prose prose-sm dark:prose-invert max-w-none break-words prose-p:my-1 prose-pre:bg-gray-100 dark:prose-pre:bg-gray-900 prose-pre:border prose-pre:border-gray-200 dark:prose-pre:border-gray-700 prose-pre:text-gray-800 dark:prose-pre:text-gray-200 prose-code:text-indigo-600 dark:prose-code:text-indigo-400 prose-code:bg-indigo-50 dark:prose-code:bg-indigo-900/20 prose-code:px-1 prose-code:py-0.5 prose-code:rounded prose-code:before:content-none prose-code:after:content-none"
                                 v-html="renderMarkdown(msg.content)">
                            </div>
                            <div v-else class="whitespace-pre-wrap break-words text-[15px] leading-relaxed">
                                {{ msg.content }}
                            </div>
                            
                            <div :class="['text-[10px] mt-2 select-none', msg.role === 'user' ? 'text-indigo-200' : 'text-gray-400 dark:text-gray-500']">
                                {{ formatTime(msg.created_at) }}
                            </div>
                        </div>
                    </div>

                    <!-- Typing Indicator -->
                    <div v-if="isLoading" class="flex w-full justify-start animate-fade-in-up">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center mr-3 flex-shrink-0 mt-1 border border-indigo-200 dark:border-indigo-800/30">
                            🤖
                        </div>
                        <div class="bg-white dark:bg-gray-800 px-5 py-4 rounded-2xl rounded-bl-sm border border-gray-200 dark:border-gray-700 shadow-sm flex items-center space-x-1">
                            <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce [animation-delay:-0.3s]"></div>
                            <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce [animation-delay:-0.15s]"></div>
                            <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce"></div>
                        </div>
                    </div>
                </div>

                <!-- Input Area (Fixed Bottom) -->
                <div v-if="activeConversation" class="bg-white dark:bg-gray-900 p-4 border-t border-gray-200 dark:border-gray-800 z-10 w-full mb-0 md:bg-white/80 md:dark:bg-gray-900/80 md:backdrop-blur relative pb-safe">
                    <div class="max-w-4xl mx-auto relative flex items-end bg-gray-100 dark:bg-gray-800 rounded-2xl border border-gray-200 dark:border-gray-700 shadow-sm transition-colors focus-within:border-indigo-400 focus-within:ring-1 focus-within:ring-indigo-400 p-1">
                        
                        <textarea id="chat-input"
                                  v-model="input"
                                  @input="autoGrow"
                                  @keydown="handleKeydown"
                                  maxlength="1000"
                                  placeholder="Ask your coach anything... (Shift+Enter for new line)"
                                  class="w-full bg-transparent border-none focus:ring-0 resize-none py-3 px-4 text-gray-800 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 min-h-[44px] max-h-[120px] scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600"
                                  rows="1"
                                  :disabled="isLoading"></textarea>
                                  
                        <div class="flex flex-col items-center justify-end px-2 pb-2 flex-shrink-0">
                            <span v-if="input.length > 800" class="text-xs text-gray-400 mb-1 font-mono absolute -top-5 right-2">
                                {{ input.length }}/1000
                            </span>
                            
                            <button @click="sendMessage" 
                                    :disabled="!input.trim() || isLoading"
                                    class="p-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white transition-all shadow-sm">
                                <Send v-if="!isLoading" class="w-5 h-5 ml-0.5" />
                                <div v-else class="w-5 h-5 flex items-center justify-center">
                                    <div class="w-4 h-4 rounded-full border-2 border-white/30 border-t-white animate-spin"></div>
                                </div>
                            </button>
                        </div>
                    </div>
                    <div class="text-center mt-2 px-4 max-w-4xl mx-auto">
                        <p class="text-[11px] text-gray-400 dark:text-gray-500">
                            AI Coach can make mistakes. Consider verifying important information.
                        </p>
                    </div>
                </div>

            </main>
        </div>
    </AppLayout>
</template>

<style scoped>
/* Ensure bottom padding accounts for mobile notches/home indicators */
.pb-safe {
    padding-bottom: env(safe-area-inset-bottom, 1rem);
}
</style>
