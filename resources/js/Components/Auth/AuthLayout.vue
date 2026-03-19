<template>
    <div class="min-h-screen bg-[#080810] flex items-center justify-center p-4">
        <!-- Subtle background grid pattern -->
        <div
            class="fixed inset-0 pointer-events-none"
            style="
                background-image: linear-gradient(rgba(124, 106, 255, 0.03) 1px, transparent 1px),
                    linear-gradient(90deg, rgba(124, 106, 255, 0.03) 1px, transparent 1px);
                background-size: 40px 40px;
            "
        ></div>

        <!-- Purple glow top center -->
        <div
            class="fixed top-0 left-1/2 -translate-x-1/2 w-96 h-48 pointer-events-none"
            style="background: radial-gradient(ellipse at center, rgba(124, 106, 255, 0.12) 0%, transparent 70%)"
        ></div>

        <slot />
    </div>
</template>

