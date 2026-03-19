<script setup>
import { ref, onMounted, onUnmounted, nextTick, watch } from 'vue'
import axios from 'axios'
import { Bot, X, Send, Minus, Sparkles } from 'lucide-vue-next'

// ── Constants ──
const WIDGET_CONV_KEY = 'growthzone_widget_conversation_id'

// ── State ──
const isOpen         = ref(false)
const isMinimized    = ref(false)
const messages       = ref([])
const newMessage     = ref('')
const isLoading      = ref(false)
const conversationId = ref(null)
const unreadCount    = ref(0)

// ── Initialize — load existing or create new conversation ──
const initConversation = async () => {
    const savedId = localStorage.getItem(WIDGET_CONV_KEY)

    if (savedId) {
        try {
            const { data } = await axios.get(`/ai/conversations/${savedId}`)
            conversationId.value = data.id
            // Load last 8 messages only to keep the widget lightweight
            messages.value = (data.messages ?? []).slice(-8)
            return
        } catch (e) {
            // Conversation deleted or not found — clear and fall through to create new
            localStorage.removeItem(WIDGET_CONV_KEY)
            conversationId.value = null
        }
    }

    // Create a fresh widget conversation
    try {
        const { data } = await axios.post('/ai/conversations', { title: 'Quick Chat' })
        conversationId.value = data.id
        localStorage.setItem(WIDGET_CONV_KEY, data.id)
        messages.value = []
    } catch (e) {
        console.error('Could not create AI conversation', e)
    }
}

// ── Open widget ──
const openWidget = async () => {
    isOpen.value      = true
    isMinimized.value = false
    unreadCount.value = 0

    if (!conversationId.value) {
        await initConversation()
    }
    await nextTick()
    scrollToBottom()
}

// ── Close widget ──
const closeWidget = () => {
    isOpen.value = false
}

// ── Toggle ──
const toggleWidget = () => {
    if (isMinimized.value) {
        isMinimized.value = false
        isOpen.value      = true
        return
    }
    if (isOpen.value) {
        closeWidget()
    } else {
        openWidget()
    }
}

// ── Send message ──
const sendMessage = async () => {
    if (!newMessage.value.trim() || isLoading.value) return

    // Ensure we have a conversation before sending
    if (!conversationId.value) await initConversation()
    if (!conversationId.value) return // Still null after init — abort

    const userMessage = {
        role:       'user',
        content:    newMessage.value.trim(),
        created_at: new Date().toISOString(),
    }

    messages.value.push(userMessage)
    const text   = newMessage.value.trim()
    newMessage.value = ''
    isLoading.value  = true

    await nextTick()
    scrollToBottom()

    try {
        const { data } = await axios.post(
            `/ai/conversations/${conversationId.value}/chat`,
            { message: text }
        )

        messages.value.push({
            role:       'assistant',
            content:    data.response ?? '⚠️ Something went wrong. Please try again.',
            created_at: new Date().toISOString(),
        })

        // Keep only the last 8 messages displayed in the widget
        if (messages.value.length > 8) {
            messages.value = messages.value.slice(-8)
        }

        // Increment badge if the user has the widget closed
        if (!isOpen.value) unreadCount.value++

    } catch (e) {
        console.error('Widget chat failed:', e)
        messages.value.push({
            role:    'assistant',
            content: '⚠️ Something went wrong. Please try again.',
        })
    } finally {
        isLoading.value = false
        await nextTick()
        scrollToBottom()
    }
}

// ── Scroll helper ──
const scrollToBottom = () => {
    const el = document.getElementById('widget-chat-viewport')
    if (el) el.scrollTop = el.scrollHeight
}

// ── Lifecycle ──
onMounted(() => {
    // Pre-load conversation so reopening the widget is instant
    initConversation()
    window.addEventListener('toggle-ai-widget', toggleWidget)
})

onUnmounted(() => {
    window.removeEventListener('toggle-ai-widget', toggleWidget)
})

watch(isOpen, (val) => {
    if (val) scrollToBottom()
})
</script>

<template>
    <div class="fixed bottom-6 right-6 z-[60] flex flex-col items-end">
        <!-- Chat Window -->
        <Transition
            enter-active-class="transition duration-300 ease-out"
            enter-from-class="translate-y-10 opacity-0 scale-95"
            enter-to-class="translate-y-0 opacity-100 scale-100"
            leave-active-class="transition duration-200 ease-in"
            leave-from-class="translate-y-0 opacity-100 scale-100"
            leave-to-class="translate-y-10 opacity-0 scale-95"
        >
            <div v-if="isOpen && !isMinimized" 
                 class="mb-4 w-[350px] h-[500px] bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 flex flex-col overflow-hidden">
                
                <!-- Window Header -->
                <div class="p-4 bg-indigo-600 text-white flex items-center justify-between shrink-0">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center">
                            <Bot class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="font-bold text-sm">AI Assistant</h3>
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                                <span class="text-[10px] text-white/70 uppercase font-medium tracking-wider">Online</span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-1">
                        <button @click="isMinimized = true" class="p-1.5 hover:bg-white/10 rounded-lg transition-colors">
                            <Minus class="w-4 h-4" />
                        </button>
                        <button @click="isOpen = false" class="p-1.5 hover:bg-white/10 rounded-lg transition-colors">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <!-- Message Area -->
                <div id="widget-chat-viewport" class="flex-1 overflow-y-auto p-4 custom-scrollbar">
                    <div class="space-y-4">
                        <div v-if="messages.length === 0" class="text-center py-8">
                            <div class="w-12 h-12 bg-indigo-50 dark:bg-indigo-900/40 rounded-full flex items-center justify-center mx-auto mb-3">
                                <Sparkles class="w-6 h-6 text-indigo-500" />
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 font-medium px-6">
                                Hi! I'm your AI Coach. Ask me anything about your habits or streaks!
                            </p>
                        </div>

                        <div v-for="msg in messages" :key="msg.id" 
                             :class="['flex', msg.role === 'user' ? 'justify-end' : 'justify-start']">
                            <div :class="[
                                'max-w-[85%] rounded-2xl px-3 py-2 text-sm leading-relaxed',
                                msg.role === 'user' 
                                    ? 'bg-indigo-600 text-white rounded-br-none' 
                                    : 'bg-gray-100 dark:bg-gray-800 text-gray-800 dark:text-gray-200 rounded-bl-none border border-gray-200 dark:border-gray-700'
                            ]">
                                <p class="whitespace-pre-wrap">{{ msg.content }}</p>
                            </div>
                        </div>

                        <div v-if="isLoading" class="flex justify-start">
                            <div class="bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl rounded-bl-none px-4 py-3">
                                <div class="flex gap-1">
                                    <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce"></span>
                                    <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce [animation-delay:0.2s]"></span>
                                    <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce [animation-delay:0.4s]"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Input Area -->
                <div class="p-3 border-t border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/50">
                    <div class="relative flex items-center">
                        <input 
                            v-model="newMessage"
                            @keydown.enter.prevent="sendMessage"
                            placeholder="Type a message..."
                            class="w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl pl-4 pr-12 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent dark:text-white"
                        />
                        <button 
                            @click="sendMessage"
                            :disabled="!newMessage.trim() || isLoading"
                            class="absolute right-1.5 w-8 h-8 flex items-center justify-center bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <Send class="w-4 h-4" />
                        </button>
                    </div>
                    <p class="text-[10px] text-gray-400 text-center mt-2 font-medium">
                        Press Enter to send · Shortcut: 'a' to toggle
                    </p>
                </div>
            </div>
        </Transition>

        <!-- Floating Button -->
        <button
            @click="toggleWidget"
            :class="[
                'relative w-14 h-14 rounded-full shadow-2xl flex items-center justify-center transition-all duration-300 transform',
                isOpen && !isMinimized ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-gray-900 text-indigo-600 dark:text-indigo-400 hover:scale-110 active:scale-95 border border-indigo-50 dark:border-gray-800'
            ]"
        >
            <X v-if="isOpen && !isMinimized" class="w-6 h-6" />
            <Bot v-else class="w-7 h-7" />

            <!-- Unread badge -->
            <span v-if="!isOpen && unreadCount > 0"
                class="absolute -top-1 -right-1 min-w-[18px] h-[18px] bg-red-500 border-2 border-white dark:border-gray-900 rounded-full text-white text-[10px] font-bold flex items-center justify-center px-0.5">
                {{ unreadCount }}
            </span>
            <!-- Dot indicator when conversation exists but no unread -->
            <span v-else-if="!isOpen && conversationId"
                class="absolute top-0 right-0 w-3 h-3 bg-indigo-500 border-2 border-white dark:border-gray-900 rounded-full">
            </span>
        </button>
    </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: transparent;
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #e2e8f0;
    border-radius: 10px;
}
.dark .custom-scrollbar::-webkit-scrollbar-thumb {
    background: #334155;
}
.custom-scrollbar {
    scrollbar-width: thin;
    scrollbar-color: #e2e8f0 transparent;
}
.dark .custom-scrollbar {
    scrollbar-color: #334155 transparent;
}
</style>
