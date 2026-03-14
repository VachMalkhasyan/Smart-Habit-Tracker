import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

export function useUnsavedChanges(isDirty) {
    const showModal   = ref(false)
    const pendingUrl  = ref(null)
    const bypassGuard = ref(false)

    // Browser tab close / reload guard
    const handleBeforeUnload = (e) => {
        if (isDirty.value && !bypassGuard.value) {
            e.preventDefault()
            e.returnValue = ''
        }
    }

    let removeGuard = null

    onMounted(() => {
        window.addEventListener('beforeunload', handleBeforeUnload)

        removeGuard = router.on('before', (event) => {
            if (isDirty.value && !bypassGuard.value) {
                // Save where user was trying to go
                pendingUrl.value = event.detail.visit.url.href ?? event.detail.visit.url
                // Block navigation and show custom modal
                event.preventDefault()
                showModal.value = true
                return false
            }
        })
    })

    onUnmounted(() => {
        window.removeEventListener('beforeunload', handleBeforeUnload)
        removeGuard?.()
    })

    const setBypass = (val = true) => {
        bypassGuard.value = val
    }

    const confirmLeave = () => {
        showModal.value = false
        bypassGuard.value = true
        if (pendingUrl.value) {
            router.visit(pendingUrl.value)
        }
    }

    const cancelLeave = () => {
        showModal.value  = false
        pendingUrl.value = null
    }

    return { showModal, confirmLeave, cancelLeave, setBypass }
}
