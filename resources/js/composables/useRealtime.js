import { onMounted, onUnmounted } from 'vue'
import { usePage }     from '@inertiajs/vue3'
import { toast }       from 'vue-sonner'

let initialized = false;
let globalHandlers = {
    onHabitCompleted:       new Set(),
    onXpAwarded:            new Set(),
    onFriendActivity:       new Set(),
    onNotificationReceived: new Set(),
}

export function useRealtime() {
    const page   = usePage()
    const userId = page.props.auth?.user?.id

    const componentHandlers = {
        onHabitCompleted:       null,
        onXpAwarded:            null,
        onFriendActivity:       null,
        onNotificationReceived: null,
    }

    onMounted(() => {
        if (!userId || !window.Echo) return;

        // Register this component's handlers globally
        if (componentHandlers.onHabitCompleted)       globalHandlers.onHabitCompleted.add(componentHandlers.onHabitCompleted)
        if (componentHandlers.onXpAwarded)            globalHandlers.onXpAwarded.add(componentHandlers.onXpAwarded)
        if (componentHandlers.onFriendActivity)       globalHandlers.onFriendActivity.add(componentHandlers.onFriendActivity)
        if (componentHandlers.onNotificationReceived) globalHandlers.onNotificationReceived.add(componentHandlers.onNotificationReceived)
        if (componentHandlers.onWeeklySummaryReady)   globalHandlers.onWeeklySummaryReady.add(componentHandlers.onWeeklySummaryReady)

        // Initialize Echo channel and listeners ONCE for the whole app
        if (!initialized) {
            initialized = true;
            console.log(`[useRealtime] Connecting to private-user.${userId}`);
            const channel = window.Echo.private(`user.${userId}`)

            channel.listen('.habit.completed', (e) => {
                globalHandlers.onHabitCompleted.forEach(fn => fn(e))
            })

            channel.listen('.xp.awarded', (e) => {
                globalHandlers.onXpAwarded.forEach(fn => fn(e))

                if (e.leveled_up) {
                    toast.success(`🎉 Level up! You reached Level ${e.new_level}!`, { duration: 6000 })
                } else if (e.amount > 0) {
                    toast.success(`+${e.amount} XP — ${e.reason}`, { duration: 3000 })
                } else {
                    toast.warning(`${e.amount} XP — ${e.reason}`, { duration: 3000 })
                }
            })

            channel.listen('.friend.activity', (e) => {
                globalHandlers.onFriendActivity.forEach(fn => fn(e))

                if (e.is_done) {
                    toast(`🎯 ${e.friend_name} completed "${e.habit_name}"`, { duration: 4000 })
                }
            })

            channel.listen('.notification.received', (e) => {
                globalHandlers.onNotificationReceived.forEach(fn => fn(e))
                
                // If it's a weekly summary notification, refresh analytics data
                if (e.type === 'weekly_summary') {
                    globalHandlers.onWeeklySummaryReady.forEach(fn => fn(e))
                }

                // Show standard flat toast to match existing style instead of rich description
                toast(`${e.icon || '🔔'} ${e.title}: ${e.message}`, { duration: 5000 })
            })
        }
    })

    onUnmounted(() => {
        // Unregister this component's handlers on unmount
        if (componentHandlers.onHabitCompleted)       globalHandlers.onHabitCompleted.delete(componentHandlers.onHabitCompleted)
        if (componentHandlers.onXpAwarded)            globalHandlers.onXpAwarded.delete(componentHandlers.onXpAwarded)
        if (componentHandlers.onFriendActivity)       globalHandlers.onFriendActivity.delete(componentHandlers.onFriendActivity)
        if (componentHandlers.onNotificationReceived) globalHandlers.onNotificationReceived.delete(componentHandlers.onNotificationReceived)
    })

    return {
        on: (event, fn) => { componentHandlers[event] = fn },
    }
}
