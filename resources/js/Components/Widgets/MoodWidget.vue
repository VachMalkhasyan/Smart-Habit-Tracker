<script setup>
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import MoodCheckIn from '@/Components/MoodCheckIn.vue'

const props = defineProps({
    widgetConfig: Object
})

const page = usePage()
const todayMood = computed(() => page.props.today_mood)
</script>

<template>
    <MoodCheckIn 
        compact 
        :current-mood="todayMood" 
    />
</template>
