import { toast } from 'vue-sonner'
import { usePage, router } from '@inertiajs/vue3'

let listenerRegistered = false;

function showFlash(props) {
    const flash = props?.flash
    if (!flash) return
    if (flash.success) toast.success(flash.success)
    if (flash.error)   toast.error(flash.error)
    if (flash.warning) toast.warning(flash.warning)
    if (flash.info)    toast.info(flash.info)
}

export function useToast() {
    const page = usePage()

    if (!listenerRegistered) {
        listenerRegistered = true;
        router.on('finish', () => {
            showFlash(page.props)
        })
    }

    // We don't want to register a listener for every component that calls useToast.
    // However, if we must do it here, we should ensure only one listener exists globally.
    // Handled globally instead of in component instance.

    return {
        success: (msg) => toast.success(msg),
        error:   (msg) => toast.error(msg),
        warning: (msg) => toast.warning(msg),
        info:    (msg) => toast.info(msg),
        promise: (promise, messages) => toast.promise(promise, messages),
    }
}
