<template>
    <div class="min-h-screen bg-gradient-to-br from-indigo-50 via-white to-purple-50 dark:from-gray-950 dark:via-gray-900 dark:to-gray-950 flex items-center justify-center p-4">
        <div class="w-full max-w-md">

            <!-- Card -->
            <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 overflow-hidden">

                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-8 py-8 text-center">
                    <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <Mail class="w-8 h-8 text-white" />
                    </div>
                    <h1 class="text-xl font-bold text-white">Check your email</h1>
                    <p class="text-indigo-200 text-sm mt-1">
                        We sent a 6-digit code to
                    </p>
                    <p class="text-white font-semibold text-sm mt-0.5">{{ email }}</p>
                </div>

                <!-- Body -->
                <div class="px-8 py-8">

                    <!-- Success / Error flash -->
                    <div v-if="$page.props.flash?.success"
                         class="mb-5 bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 text-sm rounded-xl px-4 py-3">
                        {{ $page.props.flash.success }}
                    </div>

                    <p class="text-sm text-gray-500 dark:text-gray-400 text-center mb-6">
                        Enter the 6-digit verification code below.
                        It expires in <span class="font-semibold text-indigo-500">30 minutes</span>.
                    </p>

                    <!-- Code inputs -->
                    <div class="flex gap-2 justify-center mb-6">
                        <input
                            v-for="(_, i) in 6"
                            :key="i"
                            :ref="el => inputs[i] = el"
                            v-model="codeDigits[i]"
                            type="text"
                            inputmode="numeric"
                            maxlength="1"
                            @input="onInput(i, $event)"
                            @keydown="onKeydown(i, $event)"
                            @paste="onPaste($event)"
                            :class="[
                                'w-12 h-14 text-center text-xl font-bold rounded-xl border-2 transition-all focus:outline-none',
                                errors.code
                                    ? 'border-red-400 bg-red-50 dark:bg-red-950 text-red-600'
                                    : codeDigits[i]
                                        ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-950 dark:text-indigo-300'
                                        : 'border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 focus:border-indigo-400'
                            ]"
                        />
                    </div>

                    <!-- Error -->
                    <p v-if="errors.code"
                       class="text-red-500 text-sm text-center mb-4">
                        {{ errors.code }}
                    </p>

                    <!-- Submit -->
                    <button
                        @click="submit"
                        :disabled="fullCode.length < 6 || loading"
                        class="w-full py-3 rounded-xl font-semibold text-white transition-all"
                        :class="fullCode.length === 6 && !loading
                            ? 'bg-indigo-600 hover:bg-indigo-700 cursor-pointer'
                            : 'bg-indigo-300 dark:bg-indigo-900 cursor-not-allowed'">
                        <span v-if="loading" class="flex items-center justify-center gap-2">
                            <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                            </svg>
                            Verifying...
                        </span>
                        <span v-else>Verify Email</span>
                    </button>

                    <!-- Resend -->
                    <div class="mt-5 text-center">
                        <p class="text-sm text-gray-400 dark:text-gray-500 mb-2">
                            Didn't receive it?
                        </p>
                        <button
                            @click="resend"
                            :disabled="cooldown > 0 || resending"
                            class="text-sm font-semibold transition-colors"
                            :class="cooldown > 0
                                ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed'
                                : 'text-indigo-500 hover:text-indigo-600 cursor-pointer'">
                            <span v-if="resending">Sending...</span>
                            <span v-else-if="cooldown > 0">Resend in {{ cooldown }}s</span>
                            <span v-else>Resend code</span>
                        </button>
                    </div>

                    <!-- Logout -->
                    <div class="mt-6 pt-5 border-t border-gray-100 dark:border-gray-800 text-center">
                        <Link :href="route('logout')" method="post" as="button"
                              class="text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            ← Back to login
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import { Mail } from 'lucide-vue-next'

const props = defineProps({
    secondsUntilResend: { type: Number, default: 0 },
    email:              { type: String, required: true },
})

const page     = usePage()
const inputs   = ref([])
const codeDigits = ref(['', '', '', '', '', ''])
const loading  = ref(false)
const resending = ref(false)
const cooldown = ref(props.secondsUntilResend)
const errors   = ref({})

let timer = null

// Start cooldown timer
const startCooldown = (seconds) => {
    cooldown.value = seconds
    clearInterval(timer)
    if (seconds > 0) {
        timer = setInterval(() => {
            cooldown.value--
            if (cooldown.value <= 0) clearInterval(timer)
        }, 1000)
    }
}

onMounted(() => {
    startCooldown(props.secondsUntilResend)
    inputs.value[0]?.focus()
})

onUnmounted(() => clearInterval(timer))

const fullCode = computed(() => codeDigits.value.join(''))

// Input handlers
const onInput = (i, e) => {
    errors.value = {}
    const val = e.target.value.replace(/\D/g, '').slice(-1)
    codeDigits.value[i] = val
    if (val && i < 5) {
        inputs.value[i + 1]?.focus()
    }
    // Auto-submit when all 6 filled
    if (fullCode.value.length === 6) {
        submit()
    }
}

const onKeydown = (i, e) => {
    if (e.key === 'Backspace' && !codeDigits.value[i] && i > 0) {
        inputs.value[i - 1]?.focus()
    }
    if (e.key === 'ArrowLeft' && i > 0)  inputs.value[i - 1]?.focus()
    if (e.key === 'ArrowRight' && i < 5) inputs.value[i + 1]?.focus()
}

const onPaste = (e) => {
    e.preventDefault()
    const pasted = e.clipboardData.getData('text').replace(/\D/g, '').slice(0, 6)
    pasted.split('').forEach((char, i) => {
        codeDigits.value[i] = char
    })
    const nextEmpty = pasted.length < 6 ? pasted.length : 5
    inputs.value[nextEmpty]?.focus()
    if (pasted.length === 6) submit()
}

// Submit
const submit = () => {
    if (fullCode.value.length < 6 || loading.value) return
    loading.value = true
    errors.value  = {}

    router.post(route('verification.code.verify'), { code: fullCode.value }, {
        onError: (e) => {
            errors.value = e
            codeDigits.value = ['', '', '', '', '', '']
            inputs.value[0]?.focus()
        },
        onFinish: () => { loading.value = false }
    })
}

// Resend
const resend = () => {
    if (cooldown.value > 0 || resending.value) return
    resending.value = true
    errors.value    = {}

    router.post(route('verification.resend.send'), {}, {
        onSuccess: () => startCooldown(60),
        onError:   (e) => { errors.value = e },
        onFinish:  () => { resending.value = false }
    })
}
</script>
