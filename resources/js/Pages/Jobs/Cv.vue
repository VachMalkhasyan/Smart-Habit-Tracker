<script setup>
import { ref } from 'vue'
import { Head, Link, useForm, router } from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import SecondaryButton from '@/Components/SecondaryButton.vue'
import TextInput from '@/Components/TextInput.vue'
import InputLabel from '@/Components/InputLabel.vue'
import InputError from '@/Components/InputError.vue'
import { 
    FileText, 
    Upload, 
    Clipboard, 
    CheckCircle, 
    Trash2, 
    MoreHorizontal,
    Star,
    AlertCircle,
    Zap,
    Download
} from 'lucide-vue-next'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import axios from 'axios'

const props = defineProps({
    cvs: Array,
    active_cv: Object,
})

const uploadTab = ref('file') // 'file' or 'text'
const isDragging = ref(false)
const showImprovementModal = ref(false)
const improvementSuggestions = ref(null)
const loadingImprovements = ref(false)

const uploadForm = useForm({
    file: null,
    title: '',
})

const textForm = useForm({
    raw_text: '',
    title: '',
})

const handleFileDrop = (e) => {
    isDragging.value = false
    const file = e.dataTransfer.files[0]
    if (file) {
        uploadForm.file = file
    }
}

const handleFileSelect = (e) => {
    const file = e.target.files[0]
    if (file) {
        uploadForm.file = file
    }
}

const submitUpload = () => {
    uploadForm.post(route('cv.upload'), {
        onSuccess: () => {
            uploadForm.reset()
            uploadForm.file = null
        },
    })
}

const submitText = () => {
    textForm.post(route('cv.store-text'), {
        onSuccess: () => textForm.reset(),
    })
}

const setActiveCv = (id) => {
    router.post(route('cv.activate', id), {}, {
        onSuccess: () => {
            // Optional: add toast
        }
    })
}

const deleteCv = (id, title) => {
    if (confirm(`Delete "${title}"? This cannot be undone.`)) {
        router.delete(route('cv.destroy', id))
    }
}

const formatDate = (dt) => dt
    ? new Date(dt).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' })
    : ''

const getImprovements = async (id) => {
    loadingImprovements.value = true
    showImprovementModal.value = true
    try {
        const { data } = await axios.post(route('cv.improve', id))
        improvementSuggestions.value = data.suggestions
    } finally {
        loadingImprovements.value = false
    }
}

const getScoreColor = (score) => {
    if (score >= 80) return 'text-green-500 bg-green-50 dark:bg-green-900/20'
    if (score >= 60) return 'text-yellow-500 bg-yellow-50 dark:bg-yellow-900/20'
    return 'text-red-500 bg-red-50 dark:bg-red-900/20'
}
</script>

<template>
    <AppLayout title="My CVs">
        <template #header-actions>
            <div class="flex items-center gap-3">
                <nav class="flex space-x-4">
                    <Link :href="route('jobs.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Board</Link>
                    <Link :href="route('jobs.contacts.index')" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">Contacts</Link>
                    <span class="text-sm font-bold text-indigo-600 dark:text-indigo-400 border-b-2 border-indigo-600 dark:border-indigo-400 pb-1">My CV</span>
                </nav>
            </div>
        </template>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
                
                <!-- Left Column: Upload & List -->
                <div class="lg:col-span-5 space-y-8">
                    
                    <!-- Upload Section -->
                    <div class="bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden">
                        <div class="flex border-b border-gray-100 dark:border-gray-800">
                            <button @click="uploadTab = 'file'" 
                                :class="[uploadTab === 'file' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500']"
                                class="flex-1 py-4 text-sm font-bold transition-all flex items-center justify-center gap-2">
                                <Upload class="w-4 h-4" /> Upload File
                            </button>
                            <button @click="uploadTab = 'text'" 
                                :class="[uploadTab === 'text' ? 'border-b-2 border-indigo-600 text-indigo-600' : 'text-gray-500']"
                                class="flex-1 py-4 text-sm font-bold transition-all flex items-center justify-center gap-2">
                                <Clipboard class="w-4 h-4" /> Paste Text
                            </button>
                        </div>

                        <div class="p-6">
                            <!-- File Tab -->
                            <div v-if="uploadTab === 'file'" class="space-y-4">
                                <div 
                                    @dragover.prevent="isDragging = true"
                                    @dragleave.prevent="isDragging = false"
                                    @drop.prevent="handleFileDrop"
                                    :class="[isDragging ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 dark:border-gray-800']"
                                    class="border-2 border-dashed rounded-xl py-12 flex flex-col items-center justify-center text-center transition-all cursor-pointer relative"
                                    @click="$refs.fileInput.click()"
                                >
                                    <input type="file" ref="fileInput" class="hidden" @change="handleFileSelect" accept=".pdf,.doc,.docx" />
                                    <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/50 rounded-full flex items-center justify-center text-indigo-600 dark:text-indigo-400 mb-4">
                                        <Upload class="w-6 h-6" />
                                    </div>
                                    <div v-if="!uploadForm.file">
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">Drop your CV here</p>
                                        <p class="text-xs text-gray-500 mt-1">PDF, DOC, DOCX up to 5MB</p>
                                    </div>
                                    <div v-else class="flex items-center gap-2">
                                        <CheckCircle class="w-4 h-4 text-green-500" />
                                        <p class="text-sm font-bold text-gray-900 dark:text-white">{{ uploadForm.file.name }}</p>
                                    </div>
                                </div>

                                <div class="space-y-1">
                                    <InputLabel value="CV Title (Optional)" />
                                    <TextInput v-model="uploadForm.title" placeholder="e.g. Senior Frontend Dev CV" class="w-full" />
                                </div>

                                <PrimaryButton @click="submitUpload" :disabled="!uploadForm.file || uploadForm.processing" class="w-full justify-center py-3">
                                    <span v-if="uploadForm.processing">Analyzing...</span>
                                    <span v-else class="flex items-center gap-2">Upload & Analyze ✨</span>
                                </PrimaryButton>
                            </div>

                            <!-- Text Tab -->
                            <div v-if="uploadTab === 'text'" class="space-y-4">
                                <div class="space-y-1">
                                    <InputLabel value="CV Title" />
                                    <TextInput v-model="textForm.title" placeholder="My CV" class="w-full" />
                                </div>
                                <div class="space-y-1">
                                    <InputLabel value="CV Raw Text" />
                                    <textarea 
                                        v-model="textForm.raw_text"
                                        class="w-full h-48 bg-gray-50 dark:bg-gray-800 border-gray-200 dark:border-gray-700 rounded-xl text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-all px-4 py-3"
                                        placeholder="Paste your full CV text here..."
                                    ></textarea>
                                    <div class="flex justify-between text-[10px] text-gray-400 font-bold uppercase tracking-wider">
                                        <span>Min 100 characters</span>
                                        <span>{{ textForm.raw_text.length }} / 20000</span>
                                    </div>
                                </div>
                                <PrimaryButton @click="submitText" :disabled="textForm.raw_text.length < 100 || textForm.processing" class="w-full justify-center py-3">
                                    <span v-if="textForm.processing">Analyzing...</span>
                                    <span v-else class="flex items-center gap-2">Save & Analyze ✨</span>
                                </PrimaryButton>
                            </div>
                        </div>
                    </div>

                    <!-- CV List -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-bold text-gray-500 uppercase tracking-widest px-2">Saved CVs</h3>
                        <div v-for="cv in cvs" :key="cv.id" 
                            @click="cv.is_active ? null : setActiveCv(cv.id)"
                            class="bg-white dark:bg-gray-900 border-2 rounded-2xl p-4 shadow-sm group cursor-pointer transition-all relative overflow-hidden"
                            :class="cv.is_active 
                                ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' 
                                : 'border-gray-100 dark:border-gray-800 hover:border-indigo-300 dark:hover:border-indigo-700'"
                        >
                            <!-- Active Badge -->
                            <div v-if="cv.is_active"
                                class="absolute top-3 right-12 bg-indigo-600 text-white text-[10px] font-black px-2 py-0.5 rounded-full uppercase">
                                ✓ Active
                            </div>

                            <!-- Delete Button -->
                            <button
                                @click.stop="deleteCv(cv.id, cv.title)"
                                class="absolute top-3 right-3 w-7 h-7 flex items-center justify-center text-gray-300 hover:text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition-all rounded-lg"
                                title="Delete CV"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>

                            <div class="flex items-start gap-3 pr-16">
                                <div class="w-10 h-10 bg-gray-100 dark:bg-gray-800 rounded-xl flex items-center justify-center text-gray-400 group-hover:text-indigo-500 transition-colors">
                                    <FileText class="w-5 h-5" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <h4 class="font-bold text-gray-900 dark:text-white truncate">{{ cv.title }}</h4>
                                    <p class="text-xs text-gray-500 mt-0.5 truncate">
                                        {{ cv.file_name ?? 'Pasted text' }} • {{ formatDate(cv.created_at) }}
                                    </p>
                                    <p v-if="cv.parsed_data?.skills?.length" class="text-[10px] font-bold text-indigo-500 mt-1 truncate">
                                        {{ cv.parsed_data.skills.slice(0, 4).join(', ') }}
                                        {{ cv.parsed_data.skills.length > 4 ? `+${cv.parsed_data.skills.length - 4}` : '' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: AI Analysis -->
                <div class="lg:col-span-7">
                    <div v-if="active_cv" class="space-y-6">
                        <div class="bg-gradient-to-br from-indigo-600 to-violet-700 rounded-3xl p-8 text-white relative overflow-hidden group">
                           <div class="absolute top-0 right-0 -m-8 w-48 h-48 bg-white/10 rounded-full blur-3xl group-hover:scale-110 transition-transform"></div>
                           
                           <div class="relative">
                               <div class="flex items-center gap-4 mb-6">
                                   <div class="w-16 h-16 bg-white/20 backdrop-blur-md rounded-2xl flex items-center justify-center text-3xl">
                                       🤖
                                   </div>
                                   <div>
                                       <h2 class="text-2xl font-black">AI CV Intelligence</h2>
                                       <p class="text-indigo-100 text-sm">Automated analysis of your professional profile</p>
                                   </div>
                               </div>

                               <div v-if="active_cv.parsed_data" class="space-y-6">
                                   <div class="grid grid-cols-2 gap-6 p-6 bg-black/10 rounded-2xl backdrop-blur-sm border border-white/10">
                                       <div>
                                           <p class="text-[10px] uppercase tracking-widest font-black text-indigo-200 mb-1">Full Name</p>
                                           <div class="text-lg font-bold">{{ active_cv.parsed_data.name || '—' }}</div>
                                       </div>
                                       <div>
                                           <p class="text-[10px] uppercase tracking-widest font-black text-indigo-200 mb-1">Contact Info</p>
                                           <div class="text-sm font-medium truncate">{{ active_cv.parsed_data.email || '—' }}</div>
                                           <div class="text-sm opacity-80 mt-0.5">{{ active_cv.parsed_data.phone || '' }}</div>
                                       </div>
                                       <div>
                                           <p class="text-[10px] uppercase tracking-widest font-black text-indigo-200 mb-1">Location</p>
                                           <div class="text-sm font-bold">{{ active_cv.parsed_data.location || '—' }}</div>
                                       </div>
                                       <div>
                                           <p class="text-[10px] uppercase tracking-widest font-black text-indigo-200 mb-1">Experience</p>
                                           <div class="text-sm font-bold">{{ active_cv.parsed_data.years_experience || '?' }} Years Professional</div>
                                       </div>
                                   </div>

                                   <div class="space-y-3">
                                       <p class="text-[10px] uppercase tracking-widest font-black text-indigo-200">Skills Detected</p>
                                       <div class="flex flex-wrap gap-2">
                                           <span v-for="skill in active_cv.parsed_data.skills" :key="skill"
                                               class="bg-white/10 hover:bg-white/20 backdrop-blur-sm text-xs font-bold px-3 py-1.5 rounded-xl border border-white/5 transition-all">
                                               {{ skill }}
                                           </span>
                                       </div>
                                   </div>

                                   <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                       <div class="p-6 bg-white/5 rounded-2xl border border-white/5">
                                           <p class="text-[10px] uppercase tracking-widest font-black text-indigo-200 mb-4 flex items-center gap-2">
                                               <Star class="w-3 h-3 text-yellow-400" /> Key Strengths
                                           </p>
                                           <ul class="space-y-3">
                                               <li v-for="strength in active_cv.parsed_data.strengths" :key="strength" class="text-xs flex gap-2">
                                                   <span class="text-green-400 shrink-0">✓</span> {{ strength }}
                                               </li>
                                           </ul>
                                       </div>
                                       <div class="p-6 bg-white/5 rounded-2xl border border-white/5">
                                           <p class="text-[10px] uppercase tracking-widest font-black text-indigo-200 mb-4 flex items-center gap-2">
                                               <AlertCircle class="w-3 h-3 text-orange-400" /> Growth Areas
                                           </p>
                                           <ul class="space-y-3">
                                               <li v-for="area in active_cv.parsed_data.improvement_areas" :key="area" class="text-xs flex gap-2">
                                                   <span class="text-orange-400 shrink-0">↝</span> {{ area }}
                                               </li>
                                           </ul>
                                       </div>
                                   </div>

                                   <button @click="getImprovements(active_cv.id)" 
                                       class="w-full bg-white text-indigo-700 py-4 rounded-2xl font-black text-sm hover:bg-indigo-50 transition-all flex items-center justify-center gap-2 shadow-xl shadow-indigo-900/40">
                                       <Zap class="w-4 h-4" /> Get AI Improvement Suggestions
                                   </button>
                               </div>
                               <div v-else class="flex flex-col items-center justify-center py-10">
                                   <div class="animate-spin text-4xl mb-4">🌀</div>
                                   <p class="font-bold">Extracting intelligence from your CV...</p>
                                   <p class="text-xs opacity-70">This usually takes 5-10 seconds</p>
                               </div>
                           </div>
                        </div>

                        <!-- Improvement Modal (Slide-in) -->
                        <div v-if="showImprovementModal" class="bg-white dark:bg-gray-900 border border-gray-100 dark:border-gray-800 rounded-3xl p-8 shadow-2xl space-y-8 animate-in slide-in-from-bottom duration-500">
                             <div class="flex items-center justify-between">
                                 <h3 class="text-xl font-black dark:text-white flex items-center gap-2">
                                     <Zap class="w-6 h-6 text-yellow-500 fill-yellow-500" /> Improvement Report
                                 </h3>
                                 <button @click="showImprovementModal = false" class="text-gray-400 hover:text-gray-600 transition-colors">✕</button>
                             </div>

                             <div v-if="loadingImprovements" class="flex flex-col items-center justify-center py-20 text-center">
                                 <div class="relative w-20 h-20 mb-6">
                                     <div class="absolute inset-0 border-4 border-indigo-100 dark:border-gray-800 rounded-full"></div>
                                     <div class="absolute inset-0 border-4 border-indigo-600 rounded-full border-t-transparent animate-spin"></div>
                                 </div>
                                 <p class="font-bold dark:text-white text-lg">AI Coach is reviewing your Resume</p>
                                 <p class="text-sm text-gray-500 max-w-xs mt-2">Checking sections, keywords, and ATS optimization...</p>
                             </div>

                             <div v-else-if="improvementSuggestions" class="space-y-8">
                                 <div class="flex items-center gap-6">
                                     <div :class="getScoreColor(improvementSuggestions.overall_score)" class="w-24 h-24 rounded-3xl flex flex-col items-center justify-center border">
                                         <span class="text-3xl font-black">{{ improvementSuggestions.overall_score }}</span>
                                         <span class="text-[10px] font-bold uppercase tracking-wider opacity-70">Overall</span>
                                     </div>
                                     <div class="flex-1">
                                         <p class="text-sm font-medium dark:text-gray-300 italic">"{{ improvementSuggestions.summary_feedback }}"</p>
                                     </div>
                                 </div>

                                 <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                     <div v-for="win in improvementSuggestions.quick_wins" :key="win"
                                         class="p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-100 dark:border-indigo-800 rounded-2xl flex items-start gap-3">
                                         <CheckCircle class="w-4 h-4 text-indigo-600 mt-1 shrink-0" />
                                         <span class="text-xs font-bold text-indigo-900 dark:text-indigo-200">{{ win }}</span>
                                     </div>
                                 </div>

                                 <div class="space-y-4">
                                     <h4 class="text-xs uppercase font-black tracking-widest text-gray-400 flex items-center gap-2">
                                         <AlertCircle class="w-4 h-4" /> Section Analysis
                                     </h4>
                                     <div class="grid grid-cols-1 gap-4">
                                         <div v-for="section in improvementSuggestions.sections" :key="section.section"
                                             class="p-6 bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 rounded-2xl group hover:border-indigo-300 transition-all"
                                         >
                                             <div class="flex justify-between items-start mb-4">
                                                 <h5 class="font-bold dark:text-white">{{ section.section }}</h5>
                                                 <span :class="getScoreColor(section.score)" class="text-[10px] font-black px-2 py-1 rounded-lg border">{{ section.score }}/100</span>
                                             </div>
                                             <div class="space-y-3">
                                                 <div class="text-xs text-red-500 font-bold flex gap-2">
                                                     <span class="shrink-0">Issue:</span>
                                                     <span class="dark:text-gray-300 font-medium">{{ section.issue }}</span>
                                                 </div>
                                                 <div class="text-xs text-green-600 font-bold flex gap-2 p-3 bg-green-50 dark:bg-green-900/10 rounded-xl">
                                                     <span class="shrink-0">Suggestion:</span>
                                                     <span class="dark:text-gray-200 font-medium">{{ section.suggestion }}</span>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                 </div>

                                 <div class="p-6 bg-amber-50 dark:bg-amber-900/10 border border-amber-100 dark:border-amber-900/30 rounded-3xl">
                                     <h4 class="text-xs uppercase font-black tracking-widest text-amber-600 mb-3 flex items-center gap-2">
                                         🔍 ATS Keywords To Add
                                     </h4>
                                     <div class="flex flex-wrap gap-2">
                                         <span v-for="keyword in improvementSuggestions.ats_keywords_to_add" :key="keyword"
                                             class="bg-white dark:bg-gray-900 text-[10px] font-bold px-3 py-1.5 rounded-lg border border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-400">
                                             {{ keyword }}
                                         </span>
                                     </div>
                                 </div>
                             </div>
                        </div>
                    </div>

                    <div v-else class="h-full flex flex-col items-center justify-center text-center p-12 bg-white dark:bg-gray-900 border border-dashed border-gray-200 dark:border-gray-800 rounded-3xl space-y-6">
                        <div class="w-24 h-24 bg-gray-50 dark:bg-gray-800 rounded-full flex items-center justify-center text-5xl">
                            📄
                        </div>
                        <div>
                            <h3 class="text-xl font-black dark:text-white">No active CV yet</h3>
                            <p class="text-gray-500 max-w-xs mx-auto mt-2">Upload or paste your resume to unlock AI insights, ATS scoring, and targeted cover letters.</p>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AppLayout>
</template>

<style scoped>
@keyframes slide-in {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
}

.animate-in {
    animation: slide-in 0.5s ease-out;
}
</style>
