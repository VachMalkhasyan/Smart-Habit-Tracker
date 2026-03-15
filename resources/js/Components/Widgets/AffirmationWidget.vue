<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import AffirmationCard from '@/Components/AffirmationCard.vue'

const props = defineProps({
    widgetConfig: Object
})

const page = usePage()
const affirmation = computed(() => page.props.daily_affirmation)
const generatedAt = computed(() => page.props.affirmation_date)
</script>

<template>
    <div v-if="affirmation">
        <AffirmationCard 
            :affirmation="affirmation" 
            :generated-at="generatedAt" 
        />
    </div>
</template>
