<template>
    <AppLayout title="Friends" subtitle="Stay accountable with your friends">
        <!-- Locked State for Free Users -->
        <div v-if="!can('networking')" class="max-w-5xl mx-auto py-8 space-y-12">
            <UpgradePrompt 
                feature="Friends & Social Accountability"
                message="Connect with friends, track each other's streaks, and send cheers to stay motivated together. Join the community by upgrading to Pro."
                requiredPlan="pro"
            />

            <!-- Blurred Preview of Friends Dashboard -->
            <div class="relative rounded-3xl overflow-hidden border border-gray-100 dark:border-gray-800 opacity-30 grayscale pointer-events-none select-none group">
                <div class="absolute inset-0 bg-gradient-to-b from-transparent via-white/50 to-white dark:via-gray-900/50 dark:to-gray-900 z-10"></div>
                <div class="p-8 filter blur-[6px]">
                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                        <div class="xl:col-span-2 space-y-6">
                            <div class="h-32 bg-gray-100 dark:bg-gray-800 rounded-2xl"></div>
                            <div class="h-64 bg-gray-100 dark:bg-gray-800 rounded-2xl"></div>
                        </div>
                        <div class="h-96 bg-gray-100 dark:bg-gray-800 rounded-2xl"></div>
                    </div>
                </div>
                <div class="absolute inset-x-0 bottom-20 flex flex-col items-center justify-center z-20">
                    <div class="p-6 rounded-2xl bg-white/90 dark:bg-gray-900/90 shadow-2xl border border-indigo-500/20 text-center scale-110">
                        <Users class="w-12 h-12 text-indigo-500 mx-auto mb-4" />
                        <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Social Features are Premium</h3>
                        <p class="text-sm text-gray-500 max-w-xs mx-auto">Upgrade to Pro to join 1,200+ users living their best lives together.</p>
                    </div>
                </div>
            </div>
        </div>

        <div v-else class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <!-- Left: Friend Activity -->
            <div class="xl:col-span-2 space-y-5">

                <!-- Search -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-5">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-3">Find Friends</h2>
                    <div class="relative">
                        <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
                        <input v-model="searchQuery" type="text"
                               placeholder="Search by name, username or email..."
                               class="w-full pl-9 pr-4 py-2.5 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                    </div>

                    <!-- Search Results -->
                    <div v-if="searchResults.length > 0" class="mt-3 space-y-2">
                        <div v-for="user in searchResults" :key="user.id"
                             class="flex items-center gap-3 p-3 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800 transition-colors">
                            <img :src="user.profile_photo_url" :alt="user.name"
                                 class="w-9 h-9 rounded-full object-cover" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ user.name }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ user.username ? '@' + user.username : user.email }}
                                </p>
                            </div>
                            <div class="shrink-0">
                                <Button v-if="!user.friendship_status"
                                        @click="sendRequest(user)"
                                        size="sm"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs gap-1">
                                    <UserPlus class="w-3.5 h-3.5" /> Add
                                </Button>
                                <span v-else-if="user.friendship_status === 'pending'"
                                      class="text-xs text-gray-400 dark:text-gray-500 px-3 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg">
                                    {{ user.friendship_direction === 'sent' ? 'Pending' : 'Accept?' }}
                                </span>
                                <span v-else-if="user.friendship_status === 'accepted'"
                                      class="text-xs text-green-500 flex items-center gap-1">
                                    <Check class="w-3.5 h-3.5" /> Friends
                                </span>
                            </div>
                        </div>
                    </div>

                    <p v-else-if="searchQuery.length >= 2 && !searching"
                       class="text-sm text-gray-400 dark:text-gray-500 mt-3 text-center py-2">
                        No users found
                    </p>
                </div>

                <!-- Today's Friend Activity -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                        <h2 class="font-semibold text-gray-800 dark:text-gray-100">Today's Activity</h2>
                    </div>

                    <div v-if="friends.length === 0"
                         class="px-6 py-12 text-center">
                        <Users class="w-10 h-10 mx-auto mb-3 text-gray-300 dark:text-gray-600" />
                        <p class="text-sm text-gray-500 dark:text-gray-400 font-medium">No friends yet</p>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Search for friends above to get started</p>
                    </div>

                    <div v-else class="divide-y divide-gray-50 dark:divide-gray-800">
                        <div v-for="friend in friends" :key="friend.id" class="p-5">
                            <!-- Friend header -->
                            <div class="flex items-center gap-3 mb-3">
                                <Link :href="route('friends.profile', friend.id)">
                                    <img :src="friend.profile_photo_url" :alt="friend.name"
                                         class="w-10 h-10 rounded-full object-cover hover:ring-2 hover:ring-indigo-400 transition-all" />
                                </Link>
                                <div class="flex-1 min-w-0">
                                    <Link :href="route('friends.profile', friend.id)"
                                          class="text-sm font-semibold text-gray-800 dark:text-gray-100 hover:text-indigo-500 transition-colors">
                                        {{ friend.name }}
                                    </Link>
                                    <p class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ friend.username ? '@' + friend.username : '' }}
                                    </p>
                                </div>
                                <div class="flex items-center gap-1 text-orange-500">
                                    <Flame class="w-4 h-4" />
                                    <span class="text-sm font-bold">{{ friend.longest_streak }}</span>
                                    <span class="text-xs text-gray-400">best</span>
                                </div>
                            </div>

                            <!-- Today's completions -->
                            <div v-if="friend.today_completions.length > 0" class="space-y-2">
                                <div v-for="completion in friend.today_completions" :key="completion.id"
                                     class="flex items-center justify-between bg-gray-50 dark:bg-gray-800 rounded-xl px-3 py-2">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 bg-green-100 dark:bg-green-900 rounded-full flex items-center justify-center">
                                            <Check class="w-3.5 h-3.5 text-green-600" />
                                        </div>
                                        <span class="text-xs font-medium text-gray-700 dark:text-gray-300">
                                            {{ completion.habit?.name }}
                                        </span>
                                    </div>

                                    <!-- Cheer reactions -->
                                    <div class="flex items-center gap-1">
                                        <div v-if="completion.cheers?.length > 0"
                                             class="flex items-center gap-0.5 text-xs text-gray-400 dark:text-gray-500 mr-1">
                                            <span v-for="cheer in completion.cheers.slice(0, 3)" :key="cheer.id">
                                                {{ cheer.emoji }}
                                            </span>
                                            <span v-if="completion.cheers.length > 0" class="ml-0.5">
                                                {{ completion.cheers.length }}
                                            </span>
                                        </div>

                                        <!-- Emoji picker -->
                                        <div class="relative">
                                            <button @click="toggleEmojiPicker(completion.id)"
                                                    class="text-xs px-2 py-1 rounded-lg border border-gray-200 dark:border-gray-700 hover:bg-white dark:hover:bg-gray-700 transition-colors">
                                                👏
                                            </button>
                                            <div v-if="activeEmojiPicker === completion.id"
                                                 class="absolute right-0 bottom-8 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-2 flex gap-1 z-10">
                                                <button v-for="emoji in cheerEmojis" :key="emoji"
                                                        @click="sendCheer(completion, emoji)"
                                                        class="text-lg hover:scale-125 transition-transform p-1">
                                                    {{ emoji }}
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <p v-else class="text-xs text-gray-400 dark:text-gray-500 text-center py-2">
                                No activity yet today
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Pending requests + Friends list -->
            <div class="space-y-5">

                <!-- Pending Requests -->
                <div v-if="pendingRequests.length > 0"
                     class="bg-white dark:bg-gray-900 rounded-2xl border border-indigo-100 dark:border-indigo-900 shadow-sm">
                    <div class="px-5 py-4 border-b border-indigo-50 dark:border-indigo-900 flex items-center gap-2">
                        <div class="w-2 h-2 rounded-full bg-indigo-500 animate-pulse"></div>
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100">Friend Requests</h3>
                        <span class="ml-auto text-xs bg-indigo-100 dark:bg-indigo-900 text-indigo-600 dark:text-indigo-400 px-2 py-0.5 rounded-full">
                            {{ pendingRequests.length }}
                        </span>
                    </div>
                    <div class="divide-y divide-gray-50 dark:divide-gray-800">
                        <div v-for="req in pendingRequests" :key="req.id"
                             class="flex items-center gap-3 p-4">
                            <img :src="req.sender.profile_photo_url" :alt="req.sender.name"
                                 class="w-9 h-9 rounded-full object-cover" />
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">
                                    {{ req.sender.name }}
                                </p>
                            </div>
                            <div class="flex gap-1.5">
                                <button @click="acceptRequest(req)"
                                        class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950 hover:bg-indigo-100 text-indigo-600 flex items-center justify-center transition-colors">
                                    <Check class="w-4 h-4" />
                                </button>
                                <button @click="declineRequest(req)"
                                        class="w-8 h-8 rounded-lg bg-gray-50 dark:bg-gray-800 hover:bg-red-50 text-gray-400 hover:text-red-500 flex items-center justify-center transition-colors">
                                    <X class="w-4 h-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Friends List -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm">
                    <div class="px-5 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800 dark:text-gray-100">My Friends</h3>
                        <span class="text-xs text-gray-400 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-full">
                            {{ friends.length }}
                        </span>
                    </div>
                    <div v-if="friends.length === 0"
                         class="px-5 py-8 text-center text-sm text-gray-400 dark:text-gray-500">
                        No friends yet
                    </div>
                    <div v-else class="divide-y divide-gray-50 dark:divide-gray-800">
                        <div v-for="friend in friends" :key="friend.id"
                             class="flex items-center gap-3 px-5 py-3 group">
                            <Link :href="route('friends.profile', friend.id)">
                                <img :src="friend.profile_photo_url" :alt="friend.name"
                                     class="w-8 h-8 rounded-full object-cover" />
                            </Link>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-800 dark:text-gray-100 truncate">
                                    {{ friend.name }}
                                </p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ friend.active_habits }} active habits
                                </p>
                            </div>
                            <button @click="removeFriend(friend)"
                                    class="opacity-0 group-hover:opacity-100 p-1.5 rounded-lg hover:bg-red-50 dark:hover:bg-red-950 text-gray-300 hover:text-red-500 transition-all">
                                <UserMinus class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </AppLayout>
</template>

<script setup>
import { ref, watch } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import {
    Search, Users, UserPlus, UserMinus,
    Flame, Check, X
} from 'lucide-vue-next'
import { useRealtime } from '@/composables/useRealtime'
import { usePlan } from '@/composables/usePlan'
import UpgradePrompt from '@/Components/UpgradePrompt.vue'

const { can } = usePlan()



const props = defineProps({
    friends:         Array,
    pendingRequests: Array,
})

// Search
const searchQuery   = ref('')
const searchResults = ref([])
const searching     = ref(false)
let   searchTimer   = null

watch(searchQuery, (val) => {
    clearTimeout(searchTimer)
    if (val.length < 2) { searchResults.value = []; return }
    searching.value = true
    searchTimer = setTimeout(async () => {
        const res = await fetch(
            route('friends.search') + '?q=' + encodeURIComponent(val),
            {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest', // ← tells Laravel it's ajax
                }
            }
        )
        searchResults.value = await res.json()
        searching.value = false
    }, 300)
})

const activityFeed = ref(props.activityFeed ?? [])

const { on } = useRealtime()

on('onFriendActivity', (e) => {
    activityFeed.value.unshift({
        friend_name:   e.friend_name,
        friend_avatar: e.friend_avatar,
        habit_name:    e.habit_name,
        is_done:       e.is_done,
        timestamp:     e.timestamp,
    })

    // Keep feed at max 20 items
    if (activityFeed.value.length > 20) {
        activityFeed.value = activityFeed.value.slice(0, 20)
    }
})
// Friend actions
const sendRequest = (user) => {
    router.post(route('friends.request', user.id), {}, {
        preserveScroll: true,
        onSuccess: () => {
            // Update status in search results directly
            const found = searchResults.value.find(u => u.id === user.id)
            if (found) {
                found.friendship_status    = 'pending'
                found.friendship_direction = 'sent'
            }
        }
    })
}

const acceptRequest = (req) => {
    router.post(route('friends.accept', req.id), {}, { preserveScroll: true })
}

const declineRequest = (req) => {
    router.post(route('friends.decline', req.id), {}, { preserveScroll: true })
}

const removeFriend = (friend) => {
    router.delete(route('friends.remove', friend.id), { preserveScroll: true })
}

// Cheers
const cheerEmojis      = ['🔥', '💪', '👏', '⭐', '🎉', '❤️']
const activeEmojiPicker = ref(null)

const toggleEmojiPicker = (id) => {
    activeEmojiPicker.value = activeEmojiPicker.value === id ? null : id
}

const sendCheer = (completion, emoji) => {
    router.post(route('cheers.store', completion.id), { emoji }, {
        preserveScroll: true,
        onSuccess: () => { activeEmojiPicker.value = null }
    })
}
</script>
