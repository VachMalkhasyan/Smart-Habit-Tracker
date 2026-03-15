<template>
    <AppLayout :title="isEditing ? 'Edit Habit' : 'Create Habit'"
               :subtitle="isEditing ? 'Update your habit details' : 'Build a new habit to track'">

        <div class="max-w-2xl mx-auto">
            <div v-if="template"
                 class="bg-indigo-50 dark:bg-indigo-950 border border-indigo-200 dark:border-indigo-800 rounded-2xl p-4 flex items-center gap-3 mb-2">
                <div class="w-10 h-10 bg-white dark:bg-indigo-900 rounded-xl flex items-center justify-center text-xl shrink-0">
                    {{ template.icon }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-indigo-700 dark:text-indigo-300">
                        Using template: {{ template.name }}
                    </p>
                    <p class="text-xs text-indigo-500 dark:text-indigo-400">
                        Fields pre-filled — customize as needed
                    </p>
                </div>
            </div>
            <div v-if="!template" class="flex justify-center mb-2">
                <button type="button" @click="templateModal = true"
                        class="flex items-center gap-2 text-sm text-indigo-500 hover:text-indigo-600 border border-dashed border-indigo-300 dark:border-indigo-700 px-4 py-2 rounded-xl hover:bg-indigo-50 dark:hover:bg-indigo-950 transition-all">
                    <LayoutTemplate class="w-4 h-4" />
                    Start from a template
                </button>
            </div>
            <!-- Template Modal — Full-Page Overlay -->
            <Teleport to="body">
                <Transition name="overlay-fade">
                    <div v-if="templateModal"
                        class="fixed inset-0 z-50 flex items-stretch justify-end"
                        @click.self="templateModal = false"
                    >
                        <!-- Backdrop -->
                        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="templateModal = false"></div>

                        <!-- Slide-in Panel -->
                        <Transition name="panel-slide" appear>
                            <div v-if="templateModal"
                                class="relative z-10 w-full max-w-xl bg-white dark:bg-gray-900 shadow-2xl flex flex-col overflow-hidden"
                            >
                                <!-- Panel Header -->
                                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 dark:border-gray-800 shrink-0">
                                    <div>
                                        <h2 class="text-lg font-bold text-gray-800 dark:text-white">Choose a Template</h2>
                                        <p class="text-sm text-gray-400 dark:text-gray-500 mt-0.5">Pick one to pre-fill the wizard</p>
                                    </div>
                                    <button @click="templateModal = false"
                                        class="w-8 h-8 flex items-center justify-center rounded-xl text-gray-400 hover:text-gray-600 hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors"
                                    >✕</button>
                                </div>

                                <!-- Category tabs -->
                                <div class="flex gap-2 flex-wrap px-6 py-3 border-b border-gray-100 dark:border-gray-800 shrink-0">
                                    <button @click="modalCategory = null"
                                        :class="['px-3 py-1.5 rounded-full text-xs font-semibold transition-all',
                                            modalCategory === null
                                                ? 'bg-indigo-600 text-white shadow-sm'
                                                : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700']">
                                        All
                                    </button>
                                    <button v-for="cat in templateCategories" :key="cat"
                                        @click="modalCategory = cat"
                                        :class="['px-3 py-1.5 rounded-full text-xs font-semibold transition-all capitalize',
                                            modalCategory === cat
                                                ? 'bg-indigo-600 text-white shadow-sm'
                                                : 'bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 hover:bg-gray-200 dark:hover:bg-gray-700']">
                                        {{ cat }}
                                    </button>
                                </div>

                                <!-- Templates List — Scrollable -->
                                <div class="flex-1 overflow-y-auto px-6 py-4">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <button v-for="t in filteredModalTemplates" :key="t.id" type="button"
                                            @click="applyTemplate(t)"
                                            class="flex items-start gap-3 p-4 rounded-2xl border border-gray-100 dark:border-gray-800 hover:border-indigo-300 dark:hover:border-indigo-700 hover:bg-indigo-50/60 dark:hover:bg-indigo-950/40 transition-all text-left group"
                                        >
                                            <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 flex items-center justify-center text-xl shrink-0">
                                                {{ t.icon }}
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-semibold text-gray-800 dark:text-gray-100 group-hover:text-indigo-700 dark:group-hover:text-indigo-300 transition-colors">{{ t.name }}</p>
                                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 line-clamp-2">{{ t.description }}</p>
                                                <p class="text-xs text-indigo-500 dark:text-indigo-400 mt-1.5 font-medium">{{ t.goal }} {{ t.goal_unit }} · {{ t.repeat_count }}×/day</p>
                                            </div>
                                        </button>
                                    </div>

                                    <!-- Empty state -->
                                    <div v-if="filteredModalTemplates.length === 0" class="text-center py-16 text-gray-400">
                                        <p class="text-4xl mb-3">🔍</p>
                                        <p class="text-sm">No templates in this category</p>
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </Transition>
            </Teleport>
            <form v-if="isEditing" @submit.prevent="submit" class="space-y-6">

                <!-- Basic Info Card -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-5">Basic Information</h2>

                    <div class="space-y-4">
                        <!-- Name -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1.5 block">
                                Habit Name <span class="text-red-400">*</span>
                            </label>
                            <input v-model="form.name" type="text"
                                   placeholder="e.g. Read 30 minutes"
                                   class="w-full px-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:bg-gray-900 dark:text-gray-100"
                                   :class="errors.name ? 'border-red-400 dark:border-red-600' : 'border-gray-200 dark:border-gray-700'" />
                            <p v-if="errors.name" class="text-xs text-red-400 mt-1">{{ errors.name }}</p>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1.5 block">
                                Description <span class="text-gray-400 font-normal">(optional)</span>
                            </label>
                            <textarea v-model="form.description"
                                      placeholder="What is this habit about?"
                                      rows="3"
                                      class="w-full px-4 py-2.5 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none">
                            </textarea>
                        </div>

                        <!-- Category -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1.5 block">Category</label>
                            <div class="flex gap-2 flex-wrap">
                                <button v-for="cat in categories" :key="cat.id" type="button"
                                        @click="form.category_id = cat.id"
                                        :class="[
                                        'px-3 py-1.5 rounded-xl text-sm border transition-all',
                                        form.category_id === cat.id
                                            ? 'bg-indigo-600 text-white border-indigo-600'
                                            : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600'
                                    ]">
                                    {{ cat.name }}
                                </button>
                            </div>
                            <p v-if="errors.category_id" class="text-xs text-red-400 mt-1">{{ errors.category_id }}</p>
                        </div>
                    </div>
                </div>

                <!-- Goal & Schedule Card -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-5">Goal & Schedule</h2>

                    <div class="space-y-4">

                        <!-- Goal -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1.5 block">
                                Goal <span class="text-red-400">*</span>
                            </label>
                            <div class="flex gap-2">
                                <input v-model="form.goal" type="number" min="1"
                                       placeholder="30"
                                       class="w-28 px-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:bg-gray-900 dark:text-gray-100"
                                       :class="errors.goal ? 'border-red-400 dark:border-red-600' : 'border-gray-200 dark:border-gray-700'" />
                                <select v-model="form.goal_unit"
                                        class="flex-1 px-3 py-2.5 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-600">
                                    <option value="days">Days</option>
                                    <option value="weeks">Weeks</option>
                                    <option value="months">Months</option>
                                    <option value="years">Years</option>
                                </select>
                            </div>
                            <p v-if="errors.goal" class="text-xs text-red-400 mt-1">{{ errors.goal }}</p>
                        </div>

                        <!-- Repeat Count -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1.5 block">
                                Times per day <span class="text-red-400">*</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <button type="button" @click="decrement"
                                        class="w-9 h-9 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">
                                    <Minus class="w-4 h-4" />
                                </button>
                                <span class="text-lg font-semibold text-gray-800 dark:text-gray-100 w-8 text-center">
                                    {{ form.repeat_count }}
                                </span>
                                <button type="button" @click="increment"
                                        class="w-9 h-9 rounded-xl border border-gray-200 dark:border-gray-700 flex items-center justify-center hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-600 dark:text-gray-300">
                                    <Plus class="w-4 h-4" />
                                </button>
                                <span class="text-sm text-gray-400 dark:text-gray-500">times per day</span>
                            </div>
                        </div>

                        <!-- Start Date -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1.5 block">
                                Start Date <span class="text-red-400">*</span>
                            </label>
                            <input v-model="form.start_date" type="date"
                                   class="w-full px-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:bg-gray-900 dark:text-gray-100"
                                   :class="errors.start_date ? 'border-red-400 dark:border-red-600' : 'border-gray-200 dark:border-gray-700'" />
                            <p v-if="errors.start_date" class="text-xs text-red-400 mt-1">{{ errors.start_date }}</p>
                        </div>

                        <!-- Deadline -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-1.5 block">
                                Duration <span class="text-red-400">*</span>
                            </label>
                            <div class="flex gap-2">
                                <input v-model="form.deadline_value" type="number" min="1"
                                       placeholder="2"
                                       class="w-28 px-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:bg-gray-900 dark:text-gray-100"
                                       :class="errors.deadline_value ? 'border-red-400 dark:border-red-600' : 'border-gray-200 dark:border-gray-700'" />
                                <select v-model="form.deadline_unit"
                                        class="flex-1 px-3 py-2.5 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:focus:ring-indigo-600">
                                    <option value="days">Days</option>
                                    <option value="weeks">Weeks</option>
                                    <option value="months">Months</option>
                                    <option value="years">Years</option>
                                </select>
                            </div>
                            <p v-if="errors.deadline_value" class="text-xs text-red-400 mt-1">{{ errors.deadline_value }}</p>
                        </div>

                        <!-- Computed end date hint -->
                        <p v-if="endDate" class="text-xs text-indigo-500 bg-indigo-50 dark:bg-indigo-950 px-3 py-2 rounded-lg">
                            📅 This habit will end on <strong>{{ endDate }}</strong>
                        </p>
                    </div>
                </div>

                <!-- Priority & Status Card -->
                <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                    <h2 class="font-semibold text-gray-800 dark:text-gray-100 mb-5">Priority & Status</h2>

                    <div class="space-y-4">
                        <!-- Priority -->
                        <div>
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2 block">Priority</label>
                            <div class="flex gap-3">
                                <button v-for="p in priorities" :key="p.value" type="button"
                                        @click="form.priority = p.value"
                                        :class="[
                                        'flex-1 py-2.5 rounded-xl border text-sm font-medium transition-all',
                                        form.priority === p.value ? p.activeClass : 'border-gray-200 text-gray-500 hover:border-gray-300 dark:border-gray-700 dark:text-gray-400 dark:hover:border-gray-600'
                                    ]">
                                    {{ p.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Status (only on edit) -->
                        <div v-if="isEditing">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300 dark:text-gray-300 mb-2 block">Status</label>
                            <div class="flex gap-2 flex-wrap">
                                <button v-for="s in statuses" :key="s.value" type="button"
                                        @click="form.status = s.value"
                                        :class="[
                                        'px-4 py-2 rounded-xl border text-sm font-medium transition-all capitalize',
                                        form.status === s.value ? s.activeClass : 'border-gray-200 text-gray-500 hover:border-gray-300 dark:border-gray-700 dark:text-gray-400 dark:hover:border-gray-600'
                                    ]">
                                    {{ s.label }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-between pb-6">
                    <Link :href="route('habits.index')">
                        <Button type="button" variant="outline" class="gap-2">
                            <ArrowLeft class="w-4 h-4" /> Cancel
                        </Button>
                    </Link>
                    <Button type="submit"
                            :disabled="form.processing"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2 px-8">
                        <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                        <Check v-else class="w-4 h-4" />
                        {{ isEditing ? 'Save Changes' : 'Create Habit' }}
                    </Button>
                </div>

            </form>

            <!-- Wizard (Create Mode only) -->
            <div v-else class="max-w-2xl mx-auto pb-8">

                <!-- Step Progress Bar -->
                <div class="flex items-center mb-8 px-2">
                    <template v-for="(step, idx) in wizardSteps" :key="step.id">
                        <!-- Circle -->
                        <div class="flex flex-col items-center relative z-10">
                            <div :class="[
                                'w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm border-2 transition-all duration-300',
                                currentStep > idx
                                    ? 'bg-indigo-600 border-indigo-600 text-white'
                                    : currentStep === idx
                                        ? 'bg-indigo-600 border-indigo-600 text-white shadow-lg shadow-indigo-200 dark:shadow-indigo-900'
                                        : 'bg-white dark:bg-gray-900 border-gray-300 dark:border-gray-600 text-gray-400'
                            ]">
                                <Check v-if="currentStep > idx" class="w-5 h-5" />
                                <span v-else>{{ step.id }}</span>
                            </div>
                            <div class="mt-2 text-center hidden sm:block">
                                <p :class="['text-xs font-semibold', currentStep === idx ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-500']">{{ step.title }}</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500">{{ step.desc }}</p>
                            </div>
                        </div>
                        <!-- Line connector -->
                        <div v-if="idx < wizardSteps.length - 1" class="flex-1 h-0.5 mx-2 relative">
                            <div class="absolute inset-0 bg-gray-200 dark:bg-gray-700 rounded-full"></div>
                            <div :class="['absolute inset-0 rounded-full bg-indigo-600 transition-all duration-500', currentStep > idx ? 'w-full' : 'w-0']"></div>
                        </div>
                    </template>
                </div>

                <!-- Slide Container -->
                <div class="overflow-hidden relative">
                    <Transition :name="'slide-' + (currentStep > (previousStep ?? 0) ? 'left' : 'right')" mode="out-in">

                        <!-- ===== STEP 1: Name & Category ===== -->
                        <div v-if="currentStep === 0" key="step0" class="space-y-6">
                            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6">
                                <!-- Habit Name -->
                                <div class="mb-6">
                                    <label class="block text-base font-semibold text-gray-700 dark:text-gray-200 mb-3">
                                        What habit do you want to build? <span class="text-red-400">*</span>
                                    </label>
                                    <input
                                        v-model="form.name"
                                        type="text"
                                        autofocus
                                        placeholder="e.g. Read 10 pages, Drink 2L water..."
                                        class="w-full px-4 py-3.5 text-base rounded-xl border focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:bg-gray-800 dark:text-gray-100 transition"
                                        :class="form.errors.name ? 'border-red-400 dark:border-red-600' : 'border-gray-200 dark:border-gray-700'"
                                        @keydown.enter.exact.prevent="nextStep"
                                    />
                                    <p v-if="form.errors.name" class="text-xs text-red-400 mt-2 flex items-center gap-1">
                                        <AlertCircle class="w-3.5 h-3.5" /> {{ form.errors.name }}
                                    </p>
                                </div>

                                <!-- Category -->
                                <div class="mb-5">
                                    <label class="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-3">Category</label>
                                    <div class="flex flex-wrap gap-2">
                                        <button v-for="cat in categories" :key="cat.id" type="button"
                                            @click="form.category_id = cat.id; isCreatingNewCategory = false"
                                            :class="[
                                                'px-4 py-2 rounded-xl text-sm border-2 font-medium transition-all',
                                                form.category_id === cat.id
                                                    ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm'
                                                    : 'bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border-gray-200 dark:border-gray-700 hover:border-indigo-300 dark:hover:border-indigo-600'
                                            ]"
                                        >{{ cat.name }}</button>

                                        <!-- New category button -->
                                        <button v-if="!isCreatingNewCategory" type="button"
                                            @click="isCreatingNewCategory = true; form.category_id = null"
                                            class="px-4 py-2 rounded-xl text-sm border-2 border-dashed border-gray-300 dark:border-gray-600 text-gray-400 hover:border-indigo-400 hover:text-indigo-500 transition-all font-medium"
                                        >+ New category</button>

                                        <!-- Inline new category input -->
                                        <div v-if="isCreatingNewCategory" class="flex items-center gap-2">
                                            <input
                                                v-model="form.new_category_name"
                                                type="text"
                                                placeholder="Category name..."
                                                autofocus
                                                class="px-3 py-1.5 text-sm rounded-xl border border-indigo-400 focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:bg-gray-800 dark:text-gray-100"
                                                @keydown.enter.prevent
                                            />
                                            <button type="button" @click="isCreatingNewCategory = false; form.new_category_name = ''" class="text-xs text-gray-400 hover:text-red-400">✕</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Description (optional, collapsible) -->
                                <div>
                                    <button type="button" @click="showDescription = !showDescription"
                                        class="text-sm text-indigo-500 hover:text-indigo-600 font-medium flex items-center gap-1 mb-3 transition-colors"
                                    >
                                        <span>{{ showDescription ? '− Hide' : '+ Add' }} description</span>
                                    </button>
                                    <Transition name="fade">
                                        <textarea v-if="showDescription"
                                            v-model="form.description"
                                            rows="3"
                                            placeholder="What is this habit about? (optional)"
                                            class="w-full px-4 py-3 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none"
                                        ></textarea>
                                    </Transition>
                                </div>
                            </div>
                        </div>

                        <!-- ===== STEP 2: Goal & Schedule ===== -->
                        <div v-else-if="currentStep === 1" key="step1" class="space-y-6">
                            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6 space-y-6">
                                <!-- Goal -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-3">What's your goal?</label>
                                    <div class="flex items-center gap-3">
                                        <span class="text-sm text-gray-500 dark:text-gray-400">Complete for</span>
                                        <input v-model="form.goal" type="number" min="1"
                                            class="w-24 px-3 py-2.5 text-base text-center font-semibold border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:bg-gray-800 dark:text-gray-100"
                                            :class="form.errors.goal ? 'border-red-400' : 'border-gray-200 dark:border-gray-700'"
                                        />
                                        <select v-model="form.goal_unit"
                                            class="flex-1 px-3 py-2.5 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                            <option value="days">Days</option>
                                            <option value="weeks">Weeks</option>
                                            <option value="months">Months</option>
                                            <option value="years">Years</option>
                                        </select>
                                    </div>
                                    <p v-if="form.errors.goal" class="text-xs text-red-400 mt-2">{{ form.errors.goal }}</p>
                                </div>

                                <!-- Times per day stepper -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-3">How many times per day?</label>
                                    <div class="flex items-center gap-4">
                                        <button type="button" @click="decrement"
                                            class="w-10 h-10 rounded-xl border-2 border-gray-200 dark:border-gray-700 flex items-center justify-center hover:border-indigo-400 hover:text-indigo-600 text-gray-500 transition-all">
                                            <Minus class="w-4 h-4" />
                                        </button>
                                        <span class="text-2xl font-bold text-gray-800 dark:text-white w-12 text-center tabular-nums">× {{ form.repeat_count }}</span>
                                        <button type="button" @click="increment"
                                            class="w-10 h-10 rounded-xl border-2 border-gray-200 dark:border-gray-700 flex items-center justify-center hover:border-indigo-400 hover:text-indigo-600 text-gray-500 transition-all">
                                            <Plus class="w-4 h-4" />
                                        </button>
                                        <span class="text-sm text-gray-400 dark:text-gray-500">per day</span>
                                    </div>
                                </div>

                                <!-- Start Date -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-3">When do you want to start?</label>
                                    <input v-model="form.start_date" type="date"
                                        class="w-full px-4 py-2.5 text-sm border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:bg-gray-800 dark:text-gray-100"
                                        :class="form.errors.start_date ? 'border-red-400' : 'border-gray-200 dark:border-gray-700'"
                                    />
                                </div>

                                <!-- Deadline toggle -->
                                <div>
                                    <div class="flex items-center justify-between mb-3">
                                        <label class="text-sm font-semibold text-gray-600 dark:text-gray-400">Set a deadline?</label>
                                        <button type="button" @click="showDeadline = !showDeadline"
                                            :class="['relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none', showDeadline ? 'bg-indigo-600' : 'bg-gray-300 dark:bg-gray-600']">
                                            <span :class="['inline-block h-4 w-4 transform rounded-full bg-white shadow-md transition-transform', showDeadline ? 'translate-x-6' : 'translate-x-1']"></span>
                                        </button>
                                    </div>
                                    <Transition name="fade">
                                        <div v-if="showDeadline" class="flex items-center gap-3">
                                            <input v-model="form.deadline_value" type="number" min="1" placeholder="e.g. 30"
                                                class="w-24 px-3 py-2.5 text-base text-center font-semibold border rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 dark:bg-gray-800 dark:text-gray-100"
                                                :class="form.errors.deadline_value ? 'border-red-400' : 'border-gray-200 dark:border-gray-700'"
                                            />
                                            <select v-model="form.deadline_unit"
                                                class="flex-1 px-3 py-2.5 text-sm border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                                <option value="days">Days</option>
                                                <option value="weeks">Weeks</option>
                                                <option value="months">Months</option>
                                                <option value="years">Years</option>
                                            </select>
                                        </div>
                                    </Transition>
                                    <p v-if="endDate && showDeadline" class="text-xs text-indigo-500 bg-indigo-50 dark:bg-indigo-950 px-3 py-2 rounded-lg mt-3">
                                        📅 This habit will end on <strong>{{ endDate }}</strong>
                                    </p>
                                    <p v-if="form.errors.deadline_value" class="text-xs text-red-400 mt-2">{{ form.errors.deadline_value }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- ===== STEP 3: Priority, Reminder & Summary ===== -->
                        <div v-else-if="currentStep === 2" key="step2" class="space-y-6">
                            <div class="bg-white dark:bg-gray-900 rounded-2xl border border-gray-100 dark:border-gray-800 shadow-sm p-6 space-y-6">
                                <!-- Priority -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-3">Priority</label>
                                    <div class="grid grid-cols-3 gap-3">
                                        <button v-for="p in priorities" :key="p.value" type="button"
                                            @click="form.priority = p.value"
                                            :class="[
                                                'py-4 rounded-xl border-2 text-sm font-semibold transition-all flex flex-col items-center gap-1',
                                                form.priority === p.value ? p.activeClass : 'border-gray-200 dark:border-gray-700 text-gray-500 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-600'
                                            ]">
                                            <span class="text-2xl">{{ p.emoji }}</span>
                                            <span>{{ p.label }}</span>
                                        </button>
                                    </div>
                                </div>
                                <!-- Reminder Time -->
                                <div>
                                    <label class="block text-sm font-semibold text-gray-600 dark:text-gray-400 mb-3">Daily reminder time <span class="text-gray-400 font-normal">(optional)</span></label>
                                    <input v-model="form.reminder_time" type="time"
                                        class="w-full px-4 py-2.5 text-base border border-gray-200 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 mb-3"
                                    />
                                    <div class="flex gap-2">
                                        <button type="button" @click="setReminderPreset('08:00')"
                                            :class="['flex-1 py-2 px-3 rounded-xl text-sm font-medium border-2 transition-all', form.reminder_time === '08:00' ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-200 dark:border-gray-700 text-gray-500 hover:border-indigo-300']">
                                            🌅 Morning
                                        </button>
                                        <button type="button" @click="setReminderPreset('12:00')"
                                            :class="['flex-1 py-2 px-3 rounded-xl text-sm font-medium border-2 transition-all', form.reminder_time === '12:00' ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-200 dark:border-gray-700 text-gray-500 hover:border-indigo-300']">
                                            ☀️ Midday
                                        </button>
                                        <button type="button" @click="setReminderPreset('20:00')"
                                            :class="['flex-1 py-2 px-3 rounded-xl text-sm font-medium border-2 transition-all', form.reminder_time === '20:00' ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-gray-200 dark:border-gray-700 text-gray-500 hover:border-indigo-300']">
                                            🌙 Evening
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary Card -->
                            <div class="bg-indigo-50 dark:bg-indigo-950/50 border border-indigo-100 dark:border-indigo-800/50 rounded-2xl p-5">
                                <h3 class="font-semibold text-indigo-700 dark:text-indigo-300 mb-4 flex items-center gap-2">
                                    <span>📋</span> Summary — review before creating
                                </h3>
                                <div class="space-y-2.5">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">Habit</span>
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ form.name || '—' }}</span>
                                            <button type="button" @click="currentStep = 0" class="text-indigo-400 hover:text-indigo-600">✏️</button>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">Category</span>
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-800 dark:text-gray-100">
                                                {{ form.new_category_name || categories?.find(c => c.id === form.category_id)?.name || 'None' }}
                                            </span>
                                            <button type="button" @click="currentStep = 0" class="text-indigo-400 hover:text-indigo-600">✏️</button>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">Goal</span>
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ form.goal }} {{ form.goal_unit }}</span>
                                            <button type="button" @click="currentStep = 1" class="text-indigo-400 hover:text-indigo-600">✏️</button>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">Frequency</span>
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-800 dark:text-gray-100">× {{ form.repeat_count }} per day</span>
                                            <button type="button" @click="currentStep = 1" class="text-indigo-400 hover:text-indigo-600">✏️</button>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">Start Date</span>
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ form.start_date }}</span>
                                            <button type="button" @click="currentStep = 1" class="text-indigo-400 hover:text-indigo-600">✏️</button>
                                        </div>
                                    </div>
                                    <div v-if="showDeadline" class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500 dark:text-gray-400">Deadline</span>
                                        <div class="flex items-center gap-2">
                                            <span class="font-semibold text-gray-800 dark:text-gray-100">{{ form.deadline_value }} {{ form.deadline_unit }}</span>
                                            <button type="button" @click="currentStep = 1" class="text-indigo-400 hover:text-indigo-600">✏️</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </Transition>
                </div>

                <!-- Navigation Buttons -->
                <div class="flex items-center justify-between mt-6 gap-3">
                    <Button v-if="currentStep > 0" type="button" variant="outline" @click="prevStep" class="gap-2 flex-1 sm:flex-none">
                        <ArrowLeft class="w-4 h-4" /> Back
                    </Button>
                    <Link v-else :href="route('habits.index')" class="flex-1 sm:flex-none">
                        <Button type="button" variant="outline" class="gap-2 w-full">
                            <ArrowLeft class="w-4 h-4" /> Cancel
                        </Button>
                    </Link>
                    <Button v-if="currentStep < 2" type="button" @click="nextStep"
                        :disabled="currentStep === 0 && !form.name"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2 flex-1 sm:flex-none sm:px-10">
                        Next →
                    </Button>
                    <Button v-else type="button" @click="submit"
                        :disabled="form.processing"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white gap-2 flex-1 sm:flex-none sm:px-10">
                        <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                        <span v-else>Create Habit 🎯</span>
                    </Button>
                </div>
            </div>

        </div>

        <!-- Unsaved Changes Modal -->
        <UnsavedChangesModal :show="showModal" @confirm="confirmLeave" @cancel="cancelLeave" />
    </AppLayout>
</template>

<script setup>
import {computed, ref, watch, onMounted} from 'vue'
import {Link, router, useForm} from '@inertiajs/vue3'
import AppLayout from '@/Layouts/AppLayout.vue'
import { Button } from '@/components/ui/button'
import { Plus, Minus, ArrowLeft, Check, Loader2 } from 'lucide-vue-next'
import dayjs from 'dayjs'
import { LayoutTemplate, Clock, AlertCircle } from 'lucide-vue-next'
import { useUnsavedChanges } from '@/composables/useUnsavedChanges'
import UnsavedChangesModal from '@/Components/UnsavedChangesModal.vue'

const templateModal    = ref(false)
const modalCategory    = ref(null)

const wizardSteps = [
    { id: 1, title: 'Basics', desc: 'Name & Category' },
    { id: 2, title: 'Schedule', desc: 'Goal & Frequency' },
    { id: 3, title: 'Reminders', desc: 'Priority & Time' }
]



const applyTemplate = (t) => {
    form.name           = t.name
    form.description    = t.description ?? ''
    form.goal           = t.goal
    form.goal_unit      = t.goal_unit
    form.repeat_count   = t.repeat_count
    form.deadline_value = t.deadline_value
    form.deadline_unit  = t.deadline_unit
    form.priority       = t.priority
    templateModal.value = false
}

const props = defineProps({
    habit: Object,
    categories: Array,
    errors: Object,
    template:   Object,
    templateGroups: Object,   // ← add this

})

const allTemplates = computed(() => {
    return Object.values(props.templateGroups ?? {}).flat()
})

const templateCategories = computed(() =>
    Object.keys(props.templateGroups ?? {})
)

const filteredModalTemplates = computed(() => {
    if (!modalCategory.value) return allTemplates.value
    return allTemplates.value.filter(t => t.category === modalCategory.value)
})

const isEditing = computed(() => !!props.habit)

const form = useForm({
    name:              props.habit?.name           ?? props.template?.name           ?? '',
    description:       props.habit?.description    ?? props.template?.description    ?? '',
    category_id:       props.habit?.category_id    ?? null,
    new_category_name: '',
    goal:              props.habit?.goal           ?? props.template?.goal           ?? 30,
    goal_unit:         props.habit?.goal_unit      ?? props.template?.goal_unit      ?? 'days',
    repeat_count:      props.habit?.repeat_count   ?? props.template?.repeat_count   ?? 1,
    start_date:        props.habit?.start_date     ?? new Date().toISOString().split('T')[0],
    deadline_value:    props.habit?.deadline_value ?? props.template?.deadline_value ?? 30,
    deadline_unit:     props.habit?.deadline_unit  ?? props.template?.deadline_unit  ?? 'days',
    priority:          props.habit?.priority       ?? props.template?.priority       ?? 2,
    status:            props.habit?.status         ?? 'active',
    reminder_time:     props.habit?.reminder_time  ?? null,
})

// Wizard State
const currentStep           = ref(0)
const previousStep          = ref(0)
const isCreatingNewCategory = ref(false)
const showDescription       = ref(false)
const showDeadline          = ref(!!form.deadline_value)

const nextStep = () => {
    if (currentStep.value === 0 && !form.name) {
        form.setError('name', 'Please enter a habit name')
        return
    }
    if (currentStep.value === 1) {
        if (!form.goal || form.goal < 1) {
            form.setError('goal', 'Please enter a valid goal')
            return
        }
        if (showDeadline.value && (!form.deadline_value || form.deadline_value < 1)) {
            form.setError('deadline_value', 'Please enter a valid deadline')
            return
        }
    }
    form.clearErrors()
    if (currentStep.value < 2) {
        previousStep.value = currentStep.value
        currentStep.value++
    }
}

const prevStep = () => {
    if (currentStep.value > 0) {
        previousStep.value = currentStep.value
        currentStep.value--
    }
}

const setReminderPreset = (time) => {
    form.reminder_time = time
}

// Computed end date
const endDate = computed(() => {
    if (!form.start_date || !form.deadline_value || !form.deadline_unit) return null
    return dayjs(form.start_date)
        .add(form.deadline_value, form.deadline_unit)
        .format('MMM D, YYYY')
})

// Repeat count
const increment = () => form.repeat_count < 20 && form.repeat_count++
const decrement = () => form.repeat_count > 1 && form.repeat_count--

// Priority options
const priorities = [
    { value: 1, emoji: '🔴', label: 'High',   activeClass: 'border-red-400 bg-red-50 text-red-600 dark:border-red-500/50 dark:bg-red-900/30 dark:text-red-400' },
    { value: 2, emoji: '🟡', label: 'Medium', activeClass: 'border-yellow-400 bg-yellow-50 text-yellow-600 dark:border-yellow-500/50 dark:bg-yellow-900/30 dark:text-yellow-400' },
    { value: 3, emoji: '🟢', label: 'Low',    activeClass: 'border-green-400 bg-green-50 text-green-600 dark:border-green-500/50 dark:bg-green-900/30 dark:text-green-400' },
]

// Status options
const statuses = [
    { value: 'active',    label: 'Active',    activeClass: 'border-green-400 bg-green-50 text-green-700 dark:border-green-500/50 dark:bg-green-900/30 dark:text-green-400' },
    { value: 'paused',    label: 'Paused',    activeClass: 'border-yellow-400 bg-yellow-50 text-yellow-700 dark:border-yellow-500/50 dark:bg-yellow-900/30 dark:text-yellow-400' },
    { value: 'inactive',  label: 'Inactive',  activeClass: 'border-gray-400 bg-gray-50 text-gray-700 dark:border-gray-500/50 dark:bg-gray-800/50 dark:text-gray-400' },
    { value: 'completed', label: 'Completed', activeClass: 'border-blue-400 bg-blue-50 text-blue-700 dark:border-blue-500/50 dark:bg-blue-900/30 dark:text-blue-400' },
]

// Unsaved Changes Guard (wizard create mode only)
const isDirty = computed(() =>
    !isEditing.value && (form.name.length > 0 || form.description.length > 0)
)
const { showModal, confirmLeave, cancelLeave, setBypass } = useUnsavedChanges(isDirty)

// Submit
const submit = () => {
    setBypass(true)
    if (isEditing.value) {
        form.put(route('habits.update', props.habit.id), {
            onError: () => setBypass(false)
        })
    } else {
        form.post(route('habits.store'), {
            onError: () => setBypass(false)
        })
    }
}

onMounted(() => {
    const params = new URLSearchParams(window.location.search)
    if (params.get('name'))      form.name      = params.get('name')
    if (params.get('category'))  form.new_category_name = params.get('category')
    if (params.get('goal'))      form.goal      = parseInt(params.get('goal'))
    if (params.get('goal_unit')) form.goal_unit = params.get('goal_unit')
    if (params.get('priority'))  form.priority  = parseInt(params.get('priority'))
})
</script>

<style scoped>
/* Slide Left/Right transitions between wizard steps */
.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active {
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}
.slide-left-enter-from  { opacity: 0; transform: translateX(40px); }
.slide-left-leave-to    { opacity: 0; transform: translateX(-40px); }
.slide-right-enter-from { opacity: 0; transform: translateX(-40px); }
.slide-right-leave-to   { opacity: 0; transform: translateX(40px); }

/* Fade for description textarea and deadline panel */
.fade-enter-active, .fade-leave-active { transition: opacity 0.25s ease; }
.fade-enter-from, .fade-leave-to       { opacity: 0; }

/* Template panel overlay backdrop */
.overlay-fade-enter-active, .overlay-fade-leave-active { transition: opacity 0.25s ease; }
.overlay-fade-enter-from, .overlay-fade-leave-to       { opacity: 0; }

/* Template panel slides in from right */
.panel-slide-enter-active, .panel-slide-leave-active { transition: transform 0.3s cubic-bezier(0.4,0,0.2,1); }
.panel-slide-enter-from, .panel-slide-leave-to       { transform: translateX(100%); }
</style>
