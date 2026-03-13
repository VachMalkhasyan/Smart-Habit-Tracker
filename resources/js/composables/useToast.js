import { toast } from 'vue-sonner'
import { usePage, router } from '@inertiajs/vue3'
import { onMounted, onUnmounted } from 'vue'

export function useToast() {
    const page = usePage()

    const showFlash = (props) => {
        const flash = props?.flash
        if (!flash) return
        if (flash.success) toast.success(flash.success)
        if (flash.error)   toast.error(flash.error)
        if (flash.warning) toast.warning(flash.warning)
        if (flash.info)    toast.info(flash.info)
    }

    // Fires after every Inertia navigation (including redirects)
    const removeListener = router.on('finish', () => {
        showFlash(page.props)
    })

    onUnmounted(() => removeListener())

    return {
        success: (msg) => toast.success(msg),
        error:   (msg) => toast.error(msg),
        warning: (msg) => toast.warning(msg),
        info:    (msg) => toast.info(msg),
        promise: (promise, messages) => toast.promise(promise, messages),
    }
}
