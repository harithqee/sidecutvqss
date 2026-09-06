<div x-data="{
    templates: [
        {
            id: 1,
            name: 'Queue Confirmation',
            trigger: 'Customer joins queue',
            message: 'Hi {name}, you are #{queueNumber} in line at Sidecut. Estimated wait: {waitTime} min.',
            isActive: true,
            updatedAt: 'Sep 4, 2026',
            editing: false,
        },
        {
            id: 2,
            name: 'Turn Reminder',
            trigger: '2 customers before you',
            message: 'Hi {name}, you are almost up! 2 people ahead of you at Sidecut. Please be ready.',
            isActive: true,
            updatedAt: 'Sep 3, 2026',
            editing: false,
        },
        {
            id: 3,
            name: 'Now Serving',
            trigger: 'Customer called to chair',
            message: 'Hi {name}, it is your turn! Please head to {server} at Sidecut now.',
            isActive: true,
            updatedAt: 'Sep 3, 2026',
            editing: false,
        },
        {
            id: 4,
            name: 'Service Complete',
            trigger: 'Ticket marked completed',
            message: 'Thanks for visiting Sidecut, {name}! We hope to see you again soon.',
            isActive: false,
            updatedAt: 'Aug 28, 2026',
            editing: false,
        },
        {
            id: 5,
            name: 'Cancellation Notice',
            trigger: 'Ticket canceled',
            message: 'Hi {name}, your queue ticket #{queueNumber} has been canceled. Feel free to rejoin anytime.',
            isActive: true,
            updatedAt: 'Aug 30, 2026',
            editing: false,
        },
        {
            id: 6,
            name: 'No Show Follow-up',
            trigger: 'Ticket marked no-show',
            message: 'Hi {name}, we missed you at your appointment! Feel free to rejoin the queue whenever you are ready.',
            isActive: false,
            updatedAt: 'Aug 25, 2026',
            editing: false,
        },
        {
            id: 7,
            name: 'Feedback Request',
            trigger: '30 min after service complete',
            message: 'Hi {name}, thanks for visiting Sidecut! We would love to hear your feedback: {feedbackLink}',
            isActive: true,
            updatedAt: 'Aug 22, 2026',
            editing: false,
        },
        {
            id: 8,
            name: 'Long Wait Apology',
            trigger: 'Wait time exceeds 45 min',
            message: 'Hi {name}, sorry for the longer wait today! You are still #{queueNumber} in line, thanks for your patience.',
            isActive: false,
            updatedAt: 'Aug 18, 2026',
            editing: false,
        },
    ],
    startEdit(template) {
        template.editing = true;
    },
    saveEdit(template) {
        template.editing = false;
        template.updatedAt = new Date().toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    },
    cancelEdit(template) {
        template.editing = false;
    },
    toggleActive(template) {
        template.isActive = !template.isActive;
    },
    deleteTemplate(template) {
        if (confirm('Delete template ' + template.name + '? This cannot be undone.')) {
            this.templates = this.templates.filter(function(t) {
                return t.id !== template.id;
            });
        }
    }
}">
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <table class="w-full min-w-[1102px]">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                Template Name
                            </p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                Trigger
                            </p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                Message
                            </p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                Status
                            </p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                Actions
                            </p>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="template in templates" :key="template.id">
                        <tr class="border-b border-gray-100 dark:border-gray-800">

                            <td class="px-5 py-4 sm:px-6">
                                <template x-if="!template.editing">
                                    <div>
                                        <span class="block font-medium text-gray-800 text-theme-sm dark:text-white/90" x-text="template.name"></span>
                                    </div>
                                </template>
                                <template x-if="template.editing">
                                    <input type="text" x-model="template.name" class="w-full rounded-lg border border-brand-300 px-2 py-1.5 text-theme-sm ring-3 ring-brand-500/10 dark:border-brand-700 dark:bg-gray-800 dark:text-white/90" />
                                </template>
                            </td>

                            <td class="px-5 py-4 sm:px-6">
                                <template x-if="!template.editing">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400" x-text="template.trigger"></p>
                                </template>
                                <template x-if="template.editing">
                                    <input type="text" x-model="template.trigger" class="w-full rounded-lg border border-brand-300 px-2 py-1.5 text-theme-sm ring-3 ring-brand-500/10 dark:border-brand-700 dark:bg-gray-800 dark:text-white/90" />
                                </template>
                            </td>

                            <td class="px-5 py-4 sm:px-6">
                                <template x-if="!template.editing">
                                    <p class="max-w-[260px] truncate text-gray-500 text-theme-sm dark:text-gray-400" :title="template.message" x-text="template.message"></p>
                                </template>
                                <template x-if="template.editing">
                                    <textarea x-model="template.message" rows="2" class="w-full rounded-lg border border-brand-300 px-2 py-1.5 text-theme-sm ring-3 ring-brand-500/10 dark:border-brand-700 dark:bg-gray-800 dark:text-white/90"></textarea>
                                </template>
                            </td>

                            <td class="px-5 py-4 sm:px-6">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-theme-xs font-medium"
                                   :class="template.isActive ? 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-500' : 'bg-gray-100 text-gray-500 dark:bg-gray-500/15 dark:text-gray-400'">
                                    <svg class="h-1.5 w-1.5 fill-current" viewBox="0 0 8 8">
                                        <circle cx="4" cy="4" r="4" />
                                    </svg>
                                    <span x-text="template.isActive ? 'Active' : 'Inactive'"></span>
                                </span>
                            </td>

                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center gap-2">

                                    <template x-if="!template.editing">
                                        <button
                                            @click="startEdit(template)"
                                            type="button"
                                            title="Edit template"
                                            class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 p-2 text-blue-600 shadow-theme-xs transition hover:bg-blue-100 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </template>

                                    <template x-if="template.editing">
                                        <button
                                            @click="saveEdit(template)"
                                            type="button"
                                            title="Save changes"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-theme-xs font-medium text-white shadow-theme-xs transition hover:bg-green-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none">
                                                <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Save
                                        </button>
                                    </template>

                                    <template x-if="template.editing">
                                        <button
                                            @click="cancelEdit(template)"
                                            type="button"
                                            title="Discard changes"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-xs font-medium text-gray-600 shadow-theme-xs transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none">
                                                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Cancel
                                        </button>
                                    </template>

                                    <!-- Toggle Active/Inactive: red for disable, green for enable -->
                                    <button
                                        @click="toggleActive(template)"
                                        type="button"
                                        :title="template.isActive ? 'Disable this template' : 'Enable this template'"
                                        class="inline-flex items-center justify-center rounded-lg border p-2 shadow-theme-xs transition"
                                        :class="template.isActive
                                            ? 'border-red-200 bg-red-50 text-red-600 hover:bg-red-100 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-400 dark:hover:bg-red-500/20'
                                            : 'border-green-200 bg-green-50 text-green-600 hover:bg-green-100 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-400 dark:hover:bg-green-500/20'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M12 2v10" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M18.36 6.64a9 9 0 1 1-12.73 0" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>

                                    <button
                                        @click="deleteTemplate(template)"
                                        type="button"
                                        title="Delete template permanently"
                                        class="ml-1 inline-flex items-center justify-center rounded-lg border border-red-200 bg-white p-2 text-red-500 shadow-theme-xs transition hover:border-red-300 hover:bg-red-50 dark:border-red-500/30 dark:bg-white/[0.03] dark:text-red-400 dark:hover:bg-red-500/10">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>

                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>