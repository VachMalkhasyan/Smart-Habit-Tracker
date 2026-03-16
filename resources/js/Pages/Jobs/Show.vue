<template>
    <AppLayout :title="application.company_name" :subtitle="application.role_title">
        <template #header-actions>
            <div class="flex items-center gap-3">
                <button @click="editJob" class="btn-outline px-3 py-1.5 rounded-lg text-sm font-medium border border-gray-300 dark:border-gray-600 dark:text-gray-300 dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 transition">Edit</button>
                <button @click="deleteJob" class="px-3 py-1.5 rounded-lg text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 transition">Delete</button>
            </div>
        </template>

        <div class="max-w-6xl mx-auto space-y-6 pb-12">
            <!-- Back Link -->
            <div class="mx-4 lg:mx-0">
                <Link :href="route('jobs.index')" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm font-medium flex items-center gap-1 transition">
                    <ArrowLeft class="w-4 h-4" /> Back to Board
                </Link>
            </div>

            <!-- Header Snapshot -->
            <div class="bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-6 mx-4 lg:mx-0">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-3 mb-1">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ application.company_name }}</h2>
                            <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded flex items-center gap-1" :class="priorityConfig[application.priority].badge">
                                <span class="w-1.5 h-1.5 rounded-full bg-current"></span>
                                {{ priorityConfig[application.priority].label }} Priority
                            </span>
                        </div>
                        <p class="text-lg text-gray-600 dark:text-gray-400 font-medium">{{ application.role_title }}</p>
                    </div>

                    <div class="flex items-center gap-6 text-sm">
                        <div class="flex flex-col gap-1 items-start md:items-end">
                            <Menu as="div" class="relative inline-block text-left">
                                <MenuButton class="inline-flex w-full justify-center px-4 py-2 text-sm font-semibold rounded-full border shadow-sm transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                                    :class="[statusConfig[application.status].badge, statusConfig[application.status].border]">
                                    {{ statusConfig[application.status].label }}
                                    <ChevronDown class="-mr-1 ml-2 h-5 w-5" aria-hidden="true" />
                                </MenuButton>
                                <transition enter-active-class="transition ease-out duration-100" enter-from-class="transform opacity-0 scale-95" enter-to-class="transform opacity-100 scale-100" leave-active-class="transition ease-in duration-75" leave-from-class="transform opacity-100 scale-100" leave-to-class="transform opacity-0 scale-95">
                                    <MenuItems class="absolute right-0 z-10 mt-2 w-48 origin-top-right rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none">
                                        <div class="py-1">
                                            <MenuItem v-for="(cfg, key) in statusConfig" :key="key" v-slot="{ active }">
                                                <button @click="updateStatus(key)" :class="[active ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100' : 'text-gray-700 dark:text-gray-300', 'group flex w-full items-center px-4 py-2 text-sm']">
                                                    <span class="w-2.5 h-2.5 rounded-full mr-3" :class="cfg.dot"></span>
                                                    {{ cfg.label }}
                                                </button>
                                            </MenuItem>
                                        </div>
                                    </MenuItems>
                                </transition>
                            </Menu>
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex flex-wrap gap-x-8 gap-y-3 text-sm text-gray-600 dark:text-gray-400 border-t border-gray-100 dark:border-gray-800 pt-4">
                    <div class="flex items-center gap-1.5 shrink-0">
                        <MapPin class="w-4 h-4 text-gray-400" />
                        {{ application.location || '-' }} <span v-if="application.is_remote" class="text-indigo-600 dark:text-indigo-400 font-medium">(Remote)</span>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <DollarSign class="w-4 h-4 text-gray-400" />
                        {{ formatSalary(application) }}
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <Calendar class="w-4 h-4 text-gray-400" />
                        Applied: {{ formatDate(application.applied_date || application.created_at) }}
                    </div>
                    <div v-if="application.job_url" class="flex items-center gap-1.5 shrink-0">
                        <LinkIcon class="w-4 h-4 text-gray-400" />
                        <a :href="application.job_url" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">View Posting <ExternalLink class="w-3 h-3"/></a>
                    </div>
                </div>
            </div>

            <!-- Two Columns -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mx-4 lg:mx-0">
                
                <!-- Left Column -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Interviews Section -->
                    <div class="bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <CalendarDays class="w-5 h-5 text-indigo-500" /> Interviews
                            </h3>
                            <button @click="showScheduleForm = !showScheduleForm" class="text-sm font-medium text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 flex items-center gap-1">
                                <Plus class="w-4 h-4" /> Schedule Interview
                            </button>
                        </div>

                        <!-- Schedule Form Inline -->
                        <div v-if="showScheduleForm" class="bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg p-5 mb-6">
                            <form @submit.prevent="scheduleInterview" class="space-y-4">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel value="Interview Type *" />
                                        <select v-model="interviewForm.interview_type" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required>
                                            <option v-for="(cfg, key) in interviewTypeConfig" :key="key" :value="key">{{ cfg.label }}</option>
                                        </select>
                                    </div>
                                    <div>
                                        <InputLabel value="Date & Time *" />
                                        <input type="datetime-local" v-model="interviewForm.scheduled_at" class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" required />
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <InputLabel value="Interviewer Name(s)" />
                                        <TextInput v-model="interviewForm.interviewer_name" type="text" class="mt-1 block w-full" placeholder="e.g. Jane Doe" />
                                    </div>
                                    <div>
                                        <InputLabel value="Location / Meeting Link" />
                                        <TextInput v-model="interviewForm.location" type="text" class="mt-1 block w-full" placeholder="e.g. Zoom Link" />
                                    </div>
                                </div>
                                
                                <div class="flex items-center justify-end gap-3 pt-2">
                                    <button type="button" @click="showScheduleForm = false" class="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">Cancel</button>
                                    <PrimaryButton :disabled="interviewForm.processing" class="flex gap-2 items-center">
                                        <Sparkles class="w-4 h-4 text-yellow-300" v-if="!interviewForm.processing" /> 
                                        {{ interviewForm.processing ? 'Scheduling...' : 'Save & Generate AI Prep' }}
                                    </PrimaryButton>
                                </div>
                            </form>
                        </div>

                        <!-- Interivews List -->
                        <div v-if="application.interviews?.length > 0" class="space-y-4">
                            <div v-for="interview in application.interviews" :key="interview.id" 
                                class="border rounded-lg p-4 transition-all"
                                :class="[
                                    hasPassed(interview.scheduled_at) && interview.outcome === 'pending' ? 'bg-yellow-50/50 border-yellow-200 dark:bg-yellow-900/10 dark:border-yellow-800' :
                                    interview.outcome === 'passed' ? 'bg-green-50/50 border-green-200 dark:bg-green-900/10 dark:border-green-800' :
                                    interview.outcome === 'failed' ? 'bg-red-50/50 border-red-200 dark:bg-red-900/10 dark:border-red-800' :
                                    interview.outcome === 'cancelled' ? 'bg-gray-50/50 border-gray-200 dark:bg-gray-800 dark:border-gray-700 opacity-70' :
                                    'bg-white border-gray-200 dark:bg-gray-900 dark:border-gray-700 shadow-sm'
                                ]"
                            >
                                <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                                    <div class="flex items-start gap-3">
                                        <div class="w-10 h-10 rounded-full flex items-center justify-center mt-0.5 shrink-0" :class="interviewTypeConfig[interview.interview_type]?.color || 'bg-gray-100 text-gray-600'">
                                            <Phone class="w-5 h-5 text-current" v-if="interview.interview_type === 'phone'"/>
                                            <Code2 class="w-5 h-5 text-current" v-else-if="interview.interview_type === 'technical'"/>
                                            <Users class="w-5 h-5 text-current" v-else-if="interview.interview_type === 'behavioral'"/>
                                            <Award class="w-5 h-5 text-current" v-else-if="interview.interview_type === 'final'"/>
                                            <Video class="w-5 h-5 text-current" v-else/>
                                        </div>
                                        <div>
                                            <h4 class="font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                                {{ interviewTypeConfig[interview.interview_type]?.label || 'Interview' }}
                                                <span class="text-[10px] font-bold uppercase px-2 py-0.5 rounded border" :class="getOutcomeBadgeClasses(interview.outcome)">{{ interview.outcome }}</span>
                                            </h4>
                                            <div class="text-sm text-gray-600 dark:text-gray-400 font-medium mt-1 inline-flex items-center gap-1.5">
                                                <CalendarDays class="w-3.5 h-3.5" /> {{ formatDateTime(interview.scheduled_at) }}
                                            </div>
                                            <div v-if="interview.interviewer_name" class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 inline-flex items-center gap-1.5 ml-3 border-l border-gray-300 dark:border-gray-600 pl-3">
                                                <User class="w-3 h-3" /> with {{ interview.interviewer_name }}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-wrap items-center gap-2 self-stretch sm:self-auto w-full sm:w-auto mt-2 sm:mt-0 pt-2 sm:pt-0 border-t sm:border-0 border-gray-100 dark:border-gray-700">
                                        <button @click="openPrep(interview)" class="flex-1 sm:flex-none justify-center btn-outline px-3 py-1.5 rounded-lg text-xs font-semibold border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-400 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 transition flex items-center gap-1.5">
                                            <Bot class="w-3.5 h-3.5" /> View AI Prep
                                        </button>
                                        
                                        <div v-if="interview.outcome === 'pending'" class="flex gap-2 flex-1 sm:flex-none">
                                            <button @click="updateInterviewOutcome(interview.id, 'passed')" class="flex-1 sm:flex-none justify-center px-2.5 py-1.5 rounded-lg text-xs font-semibold border border-green-200 dark:border-green-800 text-green-700 dark:text-green-400 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:hover:bg-green-900/50 transition">
                                                ✅ Pass
                                            </button>
                                            <button @click="updateInterviewOutcome(interview.id, 'failed')" class="flex-1 sm:flex-none justify-center px-2.5 py-1.5 rounded-lg text-xs font-semibold border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 bg-red-50 hover:bg-red-100 dark:bg-red-900/30 dark:hover:bg-red-900/50 transition">
                                                ❌ Fail
                                            </button>
                                            <Menu as="div" class="relative inline-block text-left">
                                                <MenuButton class="p-1 px-1.5 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 dark:text-gray-500 transition-colors border border-transparent hover:border-gray-300 dark:hover:border-gray-600">
                                                    <MoreVertical class="w-4 h-4" />
                                                </MenuButton>
                                                <transition enter-active-class="transition duration-100 ease-out" enter-from-class="transform scale-95 opacity-0" enter-to-class="transform scale-100 opacity-100" leave-active-class="transition duration-75 ease-in" leave-from-class="transform scale-100 opacity-100" leave-to-class="transform scale-95 opacity-0">
                                                    <MenuItems class="absolute right-0 mt-1 w-32 origin-top-right divide-y divide-gray-100 dark:divide-gray-700 rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none z-10">
                                                        <div class="px-1 py-1">
                                                            <MenuItem v-slot="{ active }">
                                                                <button @click="updateInterviewOutcome(interview.id, 'cancelled')" :class="[active ? 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-gray-100' : 'text-gray-700 dark:text-gray-300', 'group flex w-full items-center rounded-md px-2 py-2 text-xs']">
                                                                    Mark Cancelled
                                                                </button>
                                                            </MenuItem>
                                                            <MenuItem v-slot="{ active }">
                                                                <button @click="deleteInterview(interview.id)" :class="[active ? 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-400' : 'text-red-600 dark:text-red-400', 'group flex w-full items-center rounded-md px-2 py-2 text-xs mt-1']">
                                                                    <Trash2 class="mr-2 h-3.5 w-3.5" aria-hidden="true" />
                                                                    Delete
                                                                </button>
                                                            </MenuItem>
                                                        </div>
                                                    </MenuItems>
                                                </transition>
                                            </Menu>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div v-else class="text-center py-8 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50/50 dark:bg-gray-800/50">
                            <p class="text-gray-500 dark:text-gray-400 text-sm">No interviews scheduled yet.</p>
                        </div>
                    </div>

                    <!-- Notes Section -->
                    <div class="bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100 flex items-center gap-2">
                                <AlignLeft class="w-5 h-5 text-gray-400" /> Notes
                            </h3>
                            <span class="text-xs text-gray-400 dark:text-gray-500 bg-gray-100 dark:bg-gray-800 px-2 py-1 rounded" v-if="lastSavedAt">
                                Last saved {{ timeSince(lastSavedAt) }}
                            </span>
                        </div>
                        <textarea 
                            v-model="notes" 
                            @blur="saveNotes"
                            class="w-full h-48 border border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 rounded-lg p-3 text-sm focus:ring-indigo-500 focus:border-indigo-500 resize-y" 
                            placeholder="Add your notes about this job, company, or interview process here..."
                        ></textarea>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Notes save automatically when you click outside the text box.</p>
                    </div>

                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    
                    <!-- AI Prep Card -->
                    <div class="bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl overflow-hidden">
                        <div class="bg-indigo-600 px-4 py-3 border-b border-indigo-700">
                            <h3 class="text-base font-bold text-white flex items-center gap-2">
                                <Bot class="w-5 h-5" /> AI Prep Guide
                            </h3>
                        </div>
                        <div class="p-5">
                            <div v-if="nextInterview">
                                <p class="text-sm text-gray-600 dark:text-gray-400 font-medium mb-3">For {{ interviewTypeConfig[nextInterview.interview_type]?.label || 'Next' }} Interview on {{ formatDateTime(nextInterview.scheduled_at) }}</p>
                                
                                <div v-if="nextInterview.ai_prep" class="prose prose-sm dark:prose-invert max-w-none line-clamp-6 mb-4 relative">
                                    <div v-html="renderedSnippet" class="text-gray-600 dark:text-gray-300 text-xs"></div>
                                    <div class="absolute bottom-0 left-0 w-full h-12 bg-gradient-to-t from-white dark:from-gray-900 to-transparent"></div>
                                </div>
                                <div v-else class="text-sm text-gray-500 dark:text-gray-400 mb-4 bg-gray-50 dark:bg-gray-800 p-3 rounded border border-dashed border-gray-200 dark:border-gray-700">
                                    AI prep has not been generated for this interview yet.
                                </div>
                                
                                <button @click="openPrep(nextInterview)" class="w-full btn-primary py-2 px-4 bg-indigo-50 text-indigo-700 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:text-indigo-400 dark:hover:bg-indigo-900/50 rounded-lg text-sm font-bold transition flex justify-center items-center gap-2 shadow-sm">
                                    <Sparkles class="w-4 h-4" /> Full Prep Guide
                                </button>
                            </div>
                            <div v-else class="text-center py-4">
                                <CalendarDays class="w-10 h-10 text-gray-300 dark:text-gray-600 mx-auto mb-2" />
                                <p class="text-sm text-gray-500 dark:text-gray-400">Schedule an interview to get AI-powered preparation.</p>
                            </div>
                        </div>
                    </div>

                    <!-- ATS Score Section -->
                    <AtsScoreCard 
                        :application="application" 
                        :hasCV="$page.props.has_cv"
                        @analyze="handleAnalyze"
                    />

                    <!-- No CV Alert -->
                    <div v-if="!$page.props.has_cv" class="bg-amber-50 dark:bg-amber-900/10 border border-amber-200 dark:border-amber-800 rounded-xl p-4 space-y-3">
                        <div class="flex items-center gap-2 text-amber-800 dark:text-amber-400 font-bold text-xs uppercase tracking-wider">
                            <AlertCircle class="w-4 h-4" /> CV Missing
                        </div>
                        <p class="text-xs text-amber-700 dark:text-amber-500 leading-relaxed">
                            Upload your CV to unlock <strong>ATS scoring</strong>, missing skill analysis, and personalized cover letters for this role.
                        </p>
                        <Link :href="route('cv.index')" class="inline-flex items-center gap-1.5 text-xs font-bold text-indigo-600 dark:text-indigo-400 hover:indigo-700">
                            Upload CV now <ArrowRight class="w-3 h-3"/>
                        </Link>
                    </div>

                    <!-- Actions Card -->
                    <div class="bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-5 space-y-4">
                        <div class="space-y-2">
                            <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-1.5"><FileText class="w-4 h-4 text-gray-400"/> Cover Letter</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Generate a custom cover letter tailored to the job description.</p>
                            <button @click="showCoverLetterModal = true" class="w-full text-left px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-indigo-700 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg group transition flex items-center justify-between border border-gray-200 dark:border-gray-700 shadow-sm">
                                <span>Generate Cover Letter</span>
                                <ArrowRight class="w-4 h-4 text-gray-400 group-hover:text-indigo-500 transition-transform group-hover:translate-x-1" />
                            </button>
                        </div>
                        <div class="border-t border-gray-200 dark:border-gray-700"></div>
                        <div class="space-y-2">
                            <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-1.5"><Search class="w-4 h-4 text-gray-400"/> Company Research</h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Get an AI-generated briefing on the company, culture, and products.</p>
                            <button @click="showResearchModal = true" class="w-full text-left px-3 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:text-indigo-700 dark:hover:text-indigo-400 hover:bg-indigo-50 dark:hover:bg-indigo-900/30 rounded-lg group transition flex items-center justify-between border border-gray-200 dark:border-gray-700 shadow-sm">
                                <span>View Research</span>
                                <ArrowRight class="w-4 h-4 text-gray-400 group-hover:text-indigo-500 transition-transform group-hover:translate-x-1" />
                            </button>
                        </div>
                    </div>

                    <!-- Contacts Card -->
                    <div class="bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-700 rounded-xl p-5">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-gray-100 flex items-center gap-1.5">
                                <Users class="w-4 h-4 text-gray-400" /> Contacts <span class="bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400 px-1.5 py-0.5 rounded text-[10px]">{{ application.contacts?.length || 0 }}</span>
                            </h3>
                            <button @click="showAddContactForm = !showAddContactForm" class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                + Add
                            </button>
                        </div>

                        <!-- Add contact form -->
                        <div v-if="showAddContactForm" class="bg-gray-50 dark:bg-gray-800 border border-gray-100 dark:border-gray-700 rounded-lg p-3 mb-4 text-sm space-y-3">
                            <TextInput v-model="contactForm.name" placeholder="Name *" class="w-full block py-1 px-2" />
                            <TextInput v-model="contactForm.role" placeholder="Role (e.g. Recruiter)" class="w-full block py-1 px-2" />
                            <TextInput v-model="contactForm.email" placeholder="Email" class="w-full block py-1 px-2" />
                            <TextInput v-model="contactForm.linkedin_url" placeholder="LinkedIn URL" class="w-full block py-1 px-2" />
                            <div class="flex gap-2">
                                <button @click="showAddContactForm = false" class="flex-1 py-1 px-2 text-xs font-medium text-gray-600 hover:bg-gray-200 rounded">Cancel</button>
                                <button @click="submitContact" class="flex-1 py-1 px-2 text-xs font-medium bg-indigo-600 text-white rounded hover:bg-indigo-700">Save</button>
                            </div>
                        </div>

                        <div v-if="application.contacts?.length > 0" class="space-y-3 max-h-60 overflow-y-auto pr-1">
                            <div v-for="contact in application.contacts" :key="contact.id" class="flex items-start justify-between group p-2 hover:bg-gray-50 dark:hover:bg-gray-800 rounded-lg border border-transparent hover:border-gray-100 dark:hover:border-gray-700 transition">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-900 dark:text-gray-100 truncate flex items-center gap-1.5">
                                        {{ contact.name }}
                                        <a v-if="contact.linkedin_url" :href="contact.linkedin_url" target="_blank" class="text-blue-500 hover:text-blue-700"><LinkIcon class="w-3 h-3"/></a>
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate" v-if="contact.role">{{ contact.role }}</p>
                                    <a v-if="contact.email" :href="`mailto:${contact.email}`" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline truncate block mt-0.5">{{ contact.email }}</a>
                                </div>
                                <button @click="deleteContact(contact.id)" class="opacity-0 group-hover:opacity-100 p-1.5 text-red-500 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition ml-2 shrink-0">
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>
                        </div>
                        <div v-else class="text-center py-4 text-xs text-gray-400 dark:text-gray-500 italic">
                            No contacts added.
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <AddJobModal :show="showEditModal" :job="application" :statuses="statuses" @close="showEditModal = false" />
        <CoverLetterModal :show="showCoverLetterModal" :job="application" @close="showCoverLetterModal = false" />
        <ResearchModal :show="showResearchModal" :job="application" @close="showResearchModal = false" />
        <InterviewPrepModal :show="showInterviewPrepModal" :interview="activeInterview" @close="showInterviewPrepModal = false" />

    </AppLayout>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import { Link, router, useForm } from '@inertiajs/vue3'
import axios from 'axios'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Menu, MenuButton, MenuItems, MenuItem } from '@headlessui/vue'
import { 
    ArrowLeft, 
    MoreVertical, 
    ChevronDown, 
    MapPin, 
    DollarSign, 
    Calendar, 
    Link as LinkIcon, 
    ExternalLink, 
    CalendarDays, 
    Plus, 
    Phone, 
    Code2, 
    Users, 
    Award, 
    Video, 
    Bot, 
    Trash2, 
    AlignLeft, 
    Sparkles, 
    FileText, 
    Search,
    User,
    AlertCircle
} from 'lucide-vue-next'
import { useToast } from '@/composables/useToast'
import { useJobStatus } from '@/composables/useJobStatus'
import AddJobModal from '@/Components/Jobs/AddJobModal.vue'
import CoverLetterModal from '@/Components/Jobs/CoverLetterModal.vue'
import ResearchModal from '@/Components/Jobs/ResearchModal.vue'
import AtsScoreCard from '@/Components/Jobs/AtsScoreCard.vue'
import InputLabel from '@/Components/InputLabel.vue'
import TextInput from '@/Components/TextInput.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { marked } from 'marked'

const props = defineProps({
    application: Object,
    statuses: Object,
})

const toast = useToast()
const { statusConfig, priorityConfig, interviewTypeConfig } = useJobStatus()

const showEditModal = ref(false)
const showCoverLetterModal = ref(false)
const showResearchModal = ref(false)
const showInterviewPrepModal = ref(false)
const showScheduleForm = ref(false)
const showAddContactForm = ref(false)
const activeInterview = ref(null)

const notes = ref(props.application.notes || '')
const lastSavedAt = ref(null)

const now = ref(new Date())
setInterval(() => now.value = new Date(), 60000)

const nextInterview = computed(() => {
    return props.application.interviews?.find(i => new Date(i.scheduled_at) >= now.value && i.outcome === 'pending')
})

const renderedSnippet = computed(() => {
    if (!nextInterview.value?.ai_prep) return ''
    return marked(nextInterview.value.ai_prep)
})

// Interview Form
const interviewForm = useForm({
    interview_type: 'technical',
    scheduled_at: '',
    interviewer_name: '',
    location: '',
})

// Contact Form
const contactForm = useForm({
    job_application_id: props.application.id,
    name: '',
    role: '',
    email: '',
    linkedin_url: '',
    phone: '',
})

const editJob = () => showEditModal.value = true

const deleteJob = () => {
    if (confirm('Delete this application forever?')) {
        router.delete(route('jobs.destroy', props.application.id))
    }
}

const updateStatus = (statusKey) => {
    if (statusKey === props.application.status) return
    router.patch(route('jobs.update', props.application.id), { status: statusKey }, { preserveScroll: true })
}

const saveNotes = async () => {
    if (notes.value === props.application.notes) return
    
    // Using router.patch as requested for consistency with controller return type
    router.patch(route('jobs.update', props.application.id), { notes: notes.value }, {
        preserveScroll: true,
        onSuccess: () => {
            lastSavedAt.value = new Date()
        }
    })
}

const scheduleInterview = () => {
    interviewForm.post(route('jobs.interviews.store', props.application.id), {
        preserveScroll: true,
        onSuccess: () => {
            showScheduleForm.value = false
            interviewForm.reset()
            toast.success('Interview scheduled! AI is generating prep in the background.')
        }
    })
}

const updateInterviewOutcome = (id, outcome) => {
    router.patch(route('jobs.interviews.update', id), { outcome }, { preserveScroll: true })
}

const deleteInterview = (id) => {
    if (confirm('Delete this interview?')) {
        router.delete(route('jobs.interviews.destroy', id), { preserveScroll: true })
    }
}

const submitContact = () => {
    contactForm.post(route('jobs.contacts.store'), {
        preserveScroll: true,
        onSuccess: () => {
            showAddContactForm.value = false
            contactForm.reset()
        }
    })
}

const deleteContact = (id) => {
    if (confirm('Remove contact?')) {
        router.delete(route('jobs.contacts.destroy', id), { preserveScroll: true })
    }
}

const handleAnalyze = () => {
    router.post(route('cv.score-job', props.application.id), {}, {
        preserveScroll: true,
        onSuccess: () => toast.success('ATS scores updated!'),
    })
}

const openPrep = (interview) => {
    activeInterview.value = interview
    showInterviewPrepModal.value = true
}

const getOutcomeBadgeClasses = (outcome) => {
    switch(outcome) {
        case 'pending': return 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900/30 dark:text-yellow-400 dark:border-yellow-800';
        case 'passed': return 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900/30 dark:text-green-400 dark:border-green-800';
        case 'failed': return 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-400 dark:border-red-800';
        case 'cancelled': return 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-gray-800 dark:text-gray-400 dark:border-gray-700';
        default: return '';
    }
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
    return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })
}

const formatDateTime = (dateString) => {
    if (!dateString) return ''
    const d = new Date(dateString)
    return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' })
}

const hasPassed = (dateString) => {
    return new Date(dateString) < new Date()
}

const timeSince = (date) => {
    const seconds = Math.floor((new Date() - date) / 1000);
    if (seconds < 60) return `${seconds}s ago`
    return `${Math.floor(seconds/60)}m ago`
}
</script>

<style scoped>
/* Optional styling to make textarea resize nicely without breaking layout */
textarea {
    resize: vertical;
    min-height: 120px;
}
</style>
