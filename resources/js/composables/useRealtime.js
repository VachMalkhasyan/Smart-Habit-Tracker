import { onMounted, onUnmounted } from 'vue'
import { usePage }     from '@inertiajs/vue3'
import { toast }       from 'vue-sonner'

export function useRealtime() {
    const page   = usePage()
    const userId = page.props.auth.user.id

    const handlers = {
        onHabitCompleted: null,
        onXpAwarded:      null,
        onFriendActivity: null,
    }

    // Use a flag or check to ensure we only subscribe once if needed,
    // but listen() adds an event listener. It's safer to manage listeners directly.
    let channel = null;

    onMounted(() => {
        if (!window.Echo) {
            console.error('Echo is not defined!');
            return;
        }

        console.log(`[useRealtime] Connecting to private-user.${userId}`);
        channel = window.Echo.private(`user.${userId}`)

        channel.listen('.habit.completed', (e) => {
            console.log('[useRealtime] .habit.completed received', e);
            handlers.onHabitCompleted?.(e)
        })

        channel.listen('.xp.awarded', (e) => {
            console.log('[useRealtime] .xp.awarded received', e);
            handlers.onXpAwarded?.(e)

            if (e.leveled_up) {
                toast.success(`🎉 Level up! You reached Level ${e.new_level}!`, { duration: 6000 })
            } else if (e.amount > 0) {
                toast.success(`+${e.amount} XP — ${e.reason}`, { duration: 3000 })
            } else {
                toast.warning(`${e.amount} XP — ${e.reason}`, { duration: 3000 })
            }
        })

        channel.listen('.friend.activity', (e) => {
            console.log('[useRealtime] .friend.activity received', e);
            handlers.onFriendActivity?.(e)

            if (e.is_done) {
                toast(`🎯 ${e.friend_name} completed "${e.habit_name}"`, { duration: 4000 })
            }
        })
    })

    onUnmounted(() => {
        if (channel) {
            // Stop listening to specific events to avoid duplicate handlers on remount,
            // but DONT leave the channel completely because Inertia might have already
            // mounted the next page's layout which needs this channel.
            channel.stopListening('.habit.completed');
            channel.stopListening('.xp.awarded');
            channel.stopListening('.friend.activity');
        }
    })

    return {
        on: (event, fn) => { handlers[event] = fn },
    }
}
