<template>
    <DropdownMenu>
        <DropdownMenuTrigger asChild>
            <Button variant="outline" class="gap-2 dark:border-gray-700 dark:text-gray-300">
                <Download class="w-4 h-4" />
                Export
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-52 dark:bg-gray-900 dark:border-gray-700">

            <!-- CSV Section -->
            <div class="px-2 py-1.5">
                <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">
                    CSV
                </p>
                <DropdownMenuItem v-for="item in exportItems" :key="'csv-' + item.type"
                                  @click="exportFile('csv', item.type)"
                                  class="gap-2 cursor-pointer dark:text-gray-300 dark:hover:bg-gray-800">
                    <component :is="item.icon" class="w-4 h-4 text-gray-400" />
                    {{ item.label }}
                </DropdownMenuItem>
            </div>

            <DropdownMenuSeparator class="dark:border-gray-700" />

            <!-- PDF Section -->
            <div class="px-2 py-1.5">
                <p class="text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mb-1">
                    PDF
                </p>
                <DropdownMenuItem v-for="item in exportItems" :key="'pdf-' + item.type"
                                  @click="exportFile('pdf', item.type)"
                                  class="gap-2 cursor-pointer dark:text-gray-300 dark:hover:bg-gray-800">
                    <component :is="item.icon" class="w-4 h-4 text-gray-400" />
                    {{ item.label }}
                </DropdownMenuItem>
            </div>

        </DropdownMenuContent>
    </DropdownMenu>
</template>

<script setup>
import {
    DropdownMenu, DropdownMenuTrigger, DropdownMenuContent,
    DropdownMenuItem, DropdownMenuSeparator
} from '@/components/ui/dropdown-menu'
import { Button } from '@/components/ui/button'
import { Download, ListChecks, CalendarCheck, Flame, BarChart2 } from 'lucide-vue-next'

const exportItems = [
    { type: 'habits',      label: 'Habits List',        icon: ListChecks },
    { type: 'completions', label: 'Completion History',  icon: CalendarCheck },
    { type: 'streaks',     label: 'Streaks Summary',     icon: Flame },
    { type: 'analytics',   label: 'Analytics Report',    icon: BarChart2 },
]

const exportFile = (format, type) => {
    window.location.href = route(`export.${format}`, { type })
}
</script>
