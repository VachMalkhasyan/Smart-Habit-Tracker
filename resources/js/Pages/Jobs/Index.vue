<template>
    <AppLayout title="Job Tracker">
        <template #header-actions>
            <div class="flex items-center gap-3">
                <button @click="toggleViewMode"
                        class="p-2 text-gray-500 hover:text-gray-900 bg-gray-100 dark:bg-gray-800 dark:text-gray-400 dark:hover:text-gray-100 rounded-lg transition-colors"
                        title="Toggle View Mode">
                    <LayoutGrid v-if="viewMode === 'list'" class="w-5 h-5" />
                    <List class="w-5 h-5" v-else />
                </button>
                <button @click="showAddModal = true"
                        class="btn-primary flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors text-sm font-medium shadow-sm">
                    <Plus class="w-4 h-4" />
                    Add Job
                </button>
            </div>
        </template>

        <div class="max-w-full mx-auto space-y-6">
            <!-- Tabs Navigation -->
            <div class="border-b border-gray-200 dark:border-gray-700 mx-4 lg:mx-0">
                <nav class="-mb-px flex space-x-8">
                    <Link :href="route('jobs.index')"
                        class="border-indigo-500 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors"
                        aria-current="page">
                        Board
                    </Link>
                    <Link :href="route('jobs.contacts.index')"
                        class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        Contacts
                    </Link>
                    <Link :href="route('cv.index')"
                        class="border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300 dark:hover:border-gray-600 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                        My CV
                    </Link>
                </nav>
            </div>

            <!-- CV Upload Banner (Dismissible) -->
            <div v-if="!$page.props.has_cv && showCvBanner" class="bg-gradient-to-r from-indigo-600 to-violet-700 rounded-2xl p-4 lg:p-6 text-white shadow-lg mx-4 lg:mx-0 relative overflow-hidden group">
                <div class="absolute top-0 right-0 p-2">
                    <button @click="dismissCvBanner" class="p-1 hover:bg-white/10 rounded-full transition-colors">
                        <X class="w-4 h-4" />
                    </button>
                </div>
                <div class="absolute -right-8 -bottom-8 w-32 h-32 bg-white/10 rounded-full blur-2xl group-hover:scale-110 transition-transform"></div>
                
                <div class="flex flex-col md:flex-row md:items-center gap-4 relative">
                    <div class="w-12 h-12 bg-white/20 backdrop-blur-md rounded-xl flex items-center justify-center text-2xl shrink-0">
                        📄
                    </div>
                    <div class="flex-1">
                        <h4 class="font-black text-lg">Boost your Job Search with AI</h4>
                        <p class="text-indigo-100 text-sm">Upload your CV to unlock ATS scoring, missing skill analysis, and personalized cover letters.</p>
                    </div>
                    <Link :href="route('cv.index')" class="bg-white text-indigo-700 px-6 py-2 rounded-xl font-bold text-sm hover:bg-indigo-50 transition-all text-center">
                        Upload Now ✨
                    </Link>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="flex flex-wrap items-center gap-3">
                <button
                    v-for="(count, key) in displayStats"
                    :key="key"
                    @click="toggleFilter(key)"
                    :class="[
                        'px-4 py-2 rounded-full border text-sm font-medium transition-colors shadow-sm',
                        activeFilter === key
                            ? 'bg-indigo-50 border-indigo-200 text-indigo-700 dark:bg-indigo-900/30 dark:border-indigo-800 dark:text-indigo-300'
                            : 'bg-white border-gray-200 text-gray-700 hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-700'
                    ]"
                >
                    <span class="mr-1">{{ statEmojis[key] }}</span>
                    {{ count }} {{ formatStatLabel(key) }}
                </button>
            </div>

            <!-- Upcoming Interviews Banner -->
            <div v-if="upcoming_interviews.length > 0" class="bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800/30 rounded-xl p-4 flex gap-4 overflow-x-auto snap-x">
                <div v-for="interview in upcoming_interviews" :key="interview.id" 
                     class="flex-shrink-0 flex items-center justify-between gap-4 bg-white dark:bg-gray-800 pr-4 pl-3 py-2 rounded-lg shadow-sm border border-indigo-50 dark:border-gray-700 min-w-[300px] snap-center">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/50 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                            🗓️
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-gray-900 dark:text-gray-100 truncate">{{ interview.application.company_name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ interviewTypeConfig[interview.interview_type]?.label || 'Interview' }} • {{ formatDateTime(interview.scheduled_at) }}</p>
                        </div>
                    </div>
                    <button @click="openPrep(interview)" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300 flex items-center gap-1 px-2 py-1 rounded hover:bg-indigo-50 dark:hover:bg-indigo-900/30 transition-colors">
                        Prep <ArrowRight class="w-3 h-3" />
                    </button>
                </div>
            </div>

            <!-- Kanban View -->
            <div v-if="viewMode === 'kanban'" class="flex gap-4 overflow-x-auto pb-4 snap-x min-h-[60vh]">
                <div v-for="(cfg, statusKey) in statusConfig" :key="statusKey" class="flex-shrink-0 w-80 snap-start flex flex-col">
                    <div class="flex items-center justify-between mb-3 px-1">
                        <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 flex items-center gap-2">
                            <span :class="['w-2.5 h-2.5 rounded-full', cfg.dot]"></span>
                            {{ cfg.label }}
                        </h3>
                        <span class="text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400 px-2 py-0.5 rounded-full">
                            {{ filteredApplications[statusKey]?.length || 0 }}
                        </span>
                    </div>

                    <draggable 
                        v-model="columns[statusKey]" 
                        :group="{ name: 'jobs' }" 
                        item-key="id" 
                        class="flex-1 space-y-3 min-h-[150px] p-2 rounded-xl transition-colors select-none"
                        :class="[columns[statusKey]?.length ? '' : 'border-2 border-dashed border-gray-200 dark:border-gray-700']"
                        ghost-class="opacity-50"
                        @end="onDragEnd"
                        :data-status="statusKey"
                    >
                        <template #item="{ element }">
                            <div class="bg-white dark:bg-gray-800 p-4 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm hover:shadow-md transition-all cursor-grab active:cursor-grabbing group outline-none"
                                @click="goToDetail(element.id)">
                                
                                <div class="flex justify-between items-start mb-2">
                                    <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded flex items-center gap-1" :class="priorityConfig[element.priority].badge">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                        {{ priorityConfig[element.priority].label }}
                                    </span>
                                    
                                    <AtsScoreCard 
                                        :application="element" 
                                        :compact="true" 
                                        :hasCV="$page.props.has_cv"
                                        @analyze="handleAnalyze(element.id)"
                                    />
                                    
                                    <Menu as="div" class="relative inline-block text-left" @click.stop>
                                        <MenuButton class="p-1 rounded-md text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-gray-500 transition-colors">
                                            <MoreHorizontal class="w-4 h-4" />
                                        </MenuButton>
                                        <transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                            <MenuItems class="absolute right-0 mt-1 w-36 origin-top-right divide-y divide-gray-100 dark:divide-gray-700 rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-10">
                                                <div class="px-1 py-1">
                                                    <MenuItem v-slot="{ active }">
                                                        <button @click.stop="editJob(element)" :class="[active ? 'bg-indigo-500 text-white' : 'text-gray-900 dark:text-gray-100', 'group flex w-full items-center rounded-md px-2 py-2 text-sm']">
                                                            <Edit2 class="mr-2 h-4 w-4" aria-hidden="true" />
                                                            Edit
                                                        </button>
                                                    </MenuItem>
                                                    <MenuItem v-slot="{ active }">
                                                        <button @click.stop="deleteJob(element.id)" :class="[active ? 'bg-red-500 text-white' : 'text-red-600', 'group flex w-full items-center rounded-md px-2 py-2 text-sm mt-1']">
                                                            <Trash2 class="mr-2 h-4 w-4" aria-hidden="true" />
                                                            Delete
                                                        </button>
                                                    </MenuItem>
                                                </div>
                                            </MenuItems>
                                        </transition>
                                    </Menu>
                                </div>

                                <h4 class="font-bold text-gray-900 dark:text-gray-100 text-base leading-snug">{{ element.company_name }}</h4>
                                <p class="text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500 font-medium mt-0.5">{{ element.role_title }}</p>

                                <div class="mt-3 space-y-1.5 text-xs text-gray-500 dark:text-gray-400 dark:text-gray-500">
                                    <div v-if="element.location" class="flex items-center gap-1.5">
                                        <MapPin class="w-3.5 h-3.5 shrink-0" />
                                        <span class="truncate">{{ element.location }} <span v-if="element.is_remote">(Remote)</span></span>
                                    </div>
                                    <div v-if="element.salary_min || element.salary_max" class="flex items-center gap-1.5">
                                        <DollarSign class="w-3.5 h-3.5 shrink-0" />
                                        <span>{{ formatSalary(element) }}</span>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <Calendar class="w-3.5 h-3.5 shrink-0" />
                                        <span>Applied: {{ formatDate(element.applied_date || element.created_at) }}</span>
                                    </div>
                                </div>

                                <div v-if="element.next_interview" class="mt-3">
                                    <div class="inline-flex items-center gap-1.5 px-2 py-1 rounded bg-indigo-50 dark:bg-indigo-900/30 text-indigo-700 dark:text-indigo-300 text-xs font-medium border border-indigo-100 dark:border-indigo-800/50">
                                        <Video class="w-3 h-3" />
                                        {{ formatDateTime(element.next_interview.scheduled_at) }}
                                    </div>
                                </div>

                                <div class="mt-4 flex gap-2 border-t border-gray-100 dark:border-gray-700 pt-3">
                                    <button @click.stop="goToDetail(element.id)" class="flex-1 py-1 text-[11px] font-semibold text-gray-600 dark:text-gray-300 bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 rounded transition-colors text-center">Open</button>
                                    <button @click.stop="openCoverLetter(element)" class="flex-1 py-1 text-[11px] font-semibold text-gray-600 dark:text-gray-300 bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 rounded transition-colors text-center">Cover Ltr</button>
                                    <button @click.stop="openResearch(element)" class="flex-1 py-1 text-[11px] font-semibold text-gray-600 dark:text-gray-300 bg-gray-50 hover:bg-gray-100 dark:bg-gray-700 dark:hover:bg-gray-600 rounded transition-colors text-center">Research</button>
                                </div>
                            </div>
                        </template>
                        <template #footer v-if="!columns[statusKey]?.length">
                            <div class="h-full flex items-center justify-center pointer-events-none opacity-50 text-sm text-gray-400">
                                Drop here
                            </div>
                        </template>
                    </draggable>
                </div>
            </div>

            <!-- List View -->
            <div v-if="viewMode === 'list'" class="bg-white dark:bg-gray-900 dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sortBy('company_name')">Company</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sortBy('role_title')">Role</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sortBy('status')">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sortBy('priority')">Priority</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase tracking-wider">ATS Match</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-700" @click="sortBy('applied_date')">Applied</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase tracking-wider">Salary</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 dark:text-gray-500 uppercase tracking-wider">Next Interview</th>
                                <th scope="col" class="relative px-6 py-3"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-900 dark:bg-gray-900 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr v-for="job in flattenedList" :key="job.id" class="hover:bg-gray-50 dark:hover:bg-gray-800 cursor-pointer transition-colors" @click="goToDetail(job.id)">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-gray-100">{{ job.company_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ job.role_title }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full border" 
                                        :class="statusConfig[job.status].badge + ' ' + statusConfig[job.status].border"
                                    >
                                        {{ statusConfig[job.status].label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-[11px] font-bold uppercase px-2 py-0.5 rounded" :class="priorityConfig[job.priority].badge">
                                        {{ priorityConfig[job.priority].label }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <AtsScoreCard 
                                        :application="job" 
                                        :compact="true" 
                                        :hasCV="$page.props.has_cv"
                                        @analyze="handleAnalyze(job.id)"
                                    />
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ formatDate(job.applied_date || job.created_at) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">{{ formatSalary(job) }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 dark:text-gray-500">
                                    <span v-if="job.next_interview" class="text-indigo-600 dark:text-indigo-400 font-medium">
                                        {{ formatDateTime(job.next_interview.scheduled_at) }}
                                    </span>
                                    <span v-else class="text-gray-400">-</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button @click.stop="editJob(job)" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 mr-3">Edit</button>
                                    <button @click.stop="deleteJob(job.id)" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">Delete</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div v-if="flattenedList.length === 0" class="text-center py-16 bg-white dark:bg-gray-900 dark:bg-gray-900 rounded-xl border border-gray-200 dark:border-gray-700">
                <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 mb-4">
                    <Briefcase class="w-8 h-8" />
                </div>
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">No jobs yet</h3>
                <p class="text-gray-500 dark:text-gray-400 dark:text-gray-500 max-w-sm mx-auto mb-6">Start tracking your job search by adding an application to the board.</p>
                <button @click="showAddModal = true" class="btn-primary inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                    <Plus class="w-4 h-4" /> Add Application
                </button>
            </div>
        </div>

        <AddJobModal 
            :show="showAddModal" 
            :job="jobToEdit" 
            :statuses="statuses"
            @close="closeModal" 
            @saved="handleSaved" 
        />
        
        <CoverLetterModal 
            :show="showCoverLetterModal" 
            :job="activeJob" 
            @close="showCoverLetterModal = false" 
        />
        
        <ResearchModal 
            :show="showResearchModal" 
            :job="activeJob" 
            @close="showResearchModal = false" 
        />

        <InterviewPrepModal
            :show="showInterviewPrepModal"
            :interview="activeInterview"
            @close="showInterviewPrepModal = false"
        />
    </AppLayout>
</template>

<script setup>
import { ref, watch, computed, onMounted } from 'vue'
import { Link, router } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import draggable from 'vuedraggable'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import { 
    LayoutGrid, 
    List, 
    Plus, 
    MoreHorizontal, 
    Edit2, 
    Trash2, 
    MapPin, 
    DollarSign, 
    Calendar,
    Briefcase,
    ArrowRight,
    Video,
    X
} from 'lucide-vue-next'
import AtsScoreCard from '@/Components/Jobs/AtsScoreCard.vue'
import { useToast } from '@/composables/useToast'
import { useJobStatus } from '@/composables/useJobStatus'
import AddJobModal from '@/Components/Jobs/AddJobModal.vue'
import CoverLetterModal from '@/Components/Jobs/CoverLetterModal.vue'
import ResearchModal from '@/Components/Jobs/ResearchModal.vue'
import InterviewPrepModal from '@/Components/Jobs/InterviewPrepModal.vue'

const props = defineProps({
    applications: Object,
    stats: Object,
    statuses: Object,
    upcoming_interviews: Array,
})

const toast = useToast()
const { statusConfig, priorityConfig, interviewTypeConfig } = useJobStatus()

const viewMode = ref('kanban')
const activeFilter = ref('all')
const showAddModal = ref(false)
const showCoverLetterModal = ref(false)
const showResearchModal = ref(false)
const showInterviewPrepModal = ref(false)
const jobToEdit = ref(null)
const activeJob = ref(null)
const activeInterview = ref(null)
const sortCol = ref('applied_date')
const sortDesc = ref(true)
const showCvBanner = ref(true)

const columns = ref({})

// Initialize columns from props
const initColumns = () => {
    Object.keys(props.statuses).forEach(status => {
        columns.value[status] = props.applications[status] ? [...props.applications[status]] : []
    })
}

watch(() => props.applications, initColumns, { deep: true, immediate: true })

onMounted(() => {
    const saved = localStorage.getItem('job_view_mode')
    if (saved) viewMode.value = saved

    const bannerHidden = localStorage.getItem('hide_cv_banner')
    if (bannerHidden) showCvBanner.value = false
})

const dismissCvBanner = () => {
    showCvBanner.value = false
    localStorage.setItem('hide_cv_banner', 'true')
}

const handleAnalyze = (id) => {
    router.post(route('cv.score-job', id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('ATS scores updated!'),
    })
}

const toggleViewMode = () => {
    viewMode.value = viewMode.value === 'kanban' ? 'list' : 'kanban'
    localStorage.setItem('job_view_mode', viewMode.value)
}

const toggleFilter = (filterKey) => {
    activeFilter.value = activeFilter.value === filterKey ? 'all' : filterKey
}

const filteredApplications = computed(() => {
    return columns.value 
})

const flattenedList = computed(() => {
    let list = []
    Object.values(columns.value).forEach(col => {
        list = [...list, ...col]
    })
    
    if (activeFilter.value !== 'all') {
        const mapping = {
            'wishlist': ['wishlist'],
            'applied': ['applied'],
            'interviewing': ['phone_screen', 'interview'],
            'offers': ['offer'],
            'rejected': ['rejected'],
            'withdrawn': ['withdrawn']
        }
        
        const filterStatuses = mapping[activeFilter.value] || []
        if (filterStatuses.length) {
            list = list.filter(j => filterStatuses.includes(j.status))
        }
    }
    
    // Sort
    list.sort((a, b) => {
        let valA = a[sortCol.value]
        let valB = b[sortCol.value]
        
        if (typeof valA === 'string') valA = valA.toLowerCase()
        if (typeof valB === 'string') valB = valB.toLowerCase()
            
        if (valA < valB) return sortDesc.value ? 1 : -1
        if (valA > valB) return sortDesc.value ? -1 : 1
        return 0
    })
    
    return list
})

const sortBy = (col) => {
    if (sortCol.value === col) {
        sortDesc.value = !sortDesc.value
    } else {
        sortCol.value = col
        sortDesc.value = false
    }
}

const onDragEnd = async (evt) => {
    const item = evt.item._underlying_vm_
    const toStatus = evt.to.getAttribute('data-status')
    
    if (evt.from !== evt.to || evt.oldIndex !== evt.newIndex) {
        // Find new status array and index to update local order locally if needed,
        // but we'll use the UI state for the ordering request.
        
        let targetStatus = toStatus;
        const ordering = columns.value[targetStatus].map(i => i.id)
        
        try {
            await axios.post(route('jobs.reorder'), {
                applications: ordering,
                status: targetStatus
            })
        } catch (e) {
            toast.error('Failed to save order')
        }
    }
}

const statEmojis = {
    total: '📊',
    wishlist: '✨',
    applied: '📤',
    interviewing: '🎯',
    offers: '🎉',
    rejected: '❌',
    withdrawn: '🛑'
}

const displayStats = computed(() => {
    return props.stats
})

const formatStatLabel = (key) => {
    return key.charAt(0).toUpperCase() + key.slice(1)
}

const goToDetail = (id) => {
    router.visit(route('jobs.show', id))
}

const editJob = (job) => {
    jobToEdit.value = job
    showAddModal.value = true
}

const deleteJob = (id) => {
    if (confirm('Are you sure you want to delete this application?')) {
        router.delete(route('jobs.destroy', id), {
            preserveScroll: true
        })
    }
}

const openCoverLetter = (job) => {
    activeJob.value = job
    showCoverLetterModal.value = true
}

const openResearch = (job) => {
    activeJob.value = job
    showResearchModal.value = true
}

const openPrep = (interview) => {
    activeInterview.value = interview
    showInterviewPrepModal.value = true
}

const closeModal = () => {
    showAddModal.value = false
    jobToEdit.value = null
}

const handleSaved = () => {
    closeModal()
}

const formatSalary = (job) => {
    if (!job.salary_min && !job.salary_max) return '-'
    const c = job.currency || 'USD'
    const sign = c === 'USD' ? '$' : (c === 'EUR' ? '€' : (c === 'GBP' ? '£' : c + ' '))
    if (job.salary_min && job.salary_max) return `${sign}${(job.salary_min/1000).toFixed(0)}k – ${sign}${(job.salary_max/1000).toFixed(0)}k`
    if (job.salary_min) return `${sign}${(job.salary_min/1000).toFixed(0)}k+`
    return `Up to ${sign}${(job.salary_max/1000).toFixed(0)}k`
}

const formatDate = (dateString) => {
    if (!dateString) return ''
    const d = new Date(dateString)
    return d.toLocaleDateString('en-US', { month: 'short', day: 'numeric' })
}

const formatDateTime = (dateString) => {
    if (!dateString) return ''
    const d = new Date(dateString)
    return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}
</script>
