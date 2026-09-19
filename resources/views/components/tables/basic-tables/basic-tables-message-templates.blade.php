<div x-data="{
    templates: [],
    newTemplateName: '',
    newTemplateTrigger: '',
    newTemplateMessage: '',
    toast: { show: false, message: '', type: 'success' },

    showToast(message, type) {
    this.toast.message = message;
    this.toast.type = type || 'success';
    this.toast.show = true;
},

    async init() {
        await this.loadTemplates();
    },

    async loadTemplates() {
        const res = await fetch('/api/message-templates');
        const data = await res.json();
        this.templates = data.map(function(t) {
            return {
                id: t.id,
                name: t.name,
                trigger: t.trigger_event,
                message: t.message_body,
                isActive: t.is_active,
                updatedAt: new Date(t.updated_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
                editing: false,
            };
        });
    },

    async addTemplate() {
        if (!this.newTemplateName.trim() || !this.newTemplateTrigger.trim() || !this.newTemplateMessage.trim()) {
            this.showToast('Please fill in name, trigger, and message.', 'error');
            return;
        }
        const res = await fetch('/api/message-templates', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                name: this.newTemplateName,
                trigger_event: this.newTemplateTrigger,
                message_body: this.newTemplateMessage,
                is_active: true
            })
        });
        if (!res.ok) {
            this.showToast('Could not add template.', 'error');
            return;
        }
        const t = await res.json();
        this.templates.push({
            id: t.id,
            name: t.name,
            trigger: t.trigger_event,
            message: t.message_body,
            isActive: t.is_active,
            updatedAt: new Date(t.updated_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }),
            editing: false,
        });
        this.newTemplateName = '';
        this.newTemplateTrigger = '';
        this.newTemplateMessage = '';
        this.showToast('Template added successfully.', 'success');
    },

    startEdit(template) {
        template.editing = true;
    },

    async saveEdit(template) {
        const res = await fetch('/api/message-templates/' + template.id, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({
                name: template.name,
                trigger_event: template.trigger,
                message_body: template.message
            })
        });
        if (!res.ok) {
            this.showToast('Could not save changes.', 'error');
            return;
        }
        const updated = await res.json();
        template.editing = false;
        template.updatedAt = new Date(updated.updated_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
        this.showToast('Template updated successfully.', 'success');
    },

    cancelEdit(template) {
        template.editing = false;
    },

    async toggleActive(template) {
        const res = await fetch('/api/message-templates/' + template.id + '/toggle-active', {
            method: 'PATCH',
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) {
            this.showToast('Could not update status.', 'error');
            return;
        }
        const updated = await res.json();
        template.isActive = updated.is_active;
        this.showToast(template.isActive ? 'Template enabled.' : 'Template disabled.', 'success');
    },

    async deleteTemplate(template) {
        if (!confirm('Delete template ' + template.name + '? This cannot be undone.')) {
            return;
        }
        const res = await fetch('/api/message-templates/' + template.id, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) {
            this.showToast('Could not delete template.', 'error');
            return;
        }
        this.templates = this.templates.filter(function(t) {
            return t.id !== template.id;
        });
        this.showToast('Template deleted successfully.', 'success');
    }
}">

    <!-- Toast notification -->
    <!-- Popup alert -->
<div x-show="toast.show"
     x-cloak
     class="fixed inset-0 z-999 flex items-center justify-center bg-black/40 px-4"
     style="display: none;">
    <div x-show="toast.show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         @click.away="toast.show = false"
         class="w-full max-w-sm rounded-xl bg-white p-6 text-center shadow-theme-lg dark:bg-gray-900">

        <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full"
             :class="toast.type === 'success' ? 'bg-green-50 dark:bg-green-500/15' : 'bg-red-50 dark:bg-red-500/15'">
            <svg x-show="toast.type === 'success'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-green-600 dark:text-green-400"/>
            </svg>
            <svg x-show="toast.type === 'error'" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none">
                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-red-600 dark:text-red-400"/>
            </svg>
        </div>

        <h3 class="mb-1 text-base font-semibold text-gray-800 dark:text-white/90"
            x-text="toast.type === 'success' ? 'Success' : 'Failed'"></h3>
        <p class="mb-5 text-theme-sm text-gray-500 dark:text-gray-400" x-text="toast.message"></p>

        <button @click="toast.show = false" type="button"
            class="w-full rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600">
            OK
        </button>
    </div>
</div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">

        <div class="flex flex-wrap items-end gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800 sm:px-6">
            <div class="min-w-[160px] flex-1">
                <label class="mb-1.5 block text-theme-xs font-medium text-gray-500 dark:text-gray-400">Template Name</label>
                <input type="text" x-model="newTemplateName" placeholder="e.g. Queue Confirmation"
                    class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90" />
            </div>
            <div class="min-w-[160px] flex-1">
                <label class="mb-1.5 block text-theme-xs font-medium text-gray-500 dark:text-gray-400">Trigger</label>
                <input type="text" x-model="newTemplateTrigger" placeholder="e.g. Customer joins queue"
                    class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90" />
            </div>
            <div class="min-w-[220px] flex-[2]">
                <label class="mb-1.5 block text-theme-xs font-medium text-gray-500 dark:text-gray-400">Message</label>
                <input type="text" x-model="newTemplateMessage" placeholder="e.g. Hi {name}, you are #{queueNumber} in line."
                    class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90" />
            </div>
            <button @click="addTemplate()" type="button"
                class="inline-flex h-10 items-center gap-1.5 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Add Template
            </button>
        </div>

        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <table class="w-full min-w-[1150px]">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">ID</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Template Name</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Trigger</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Message</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Actions</p></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="template in templates" :key="template.id">
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-5 py-4 sm:px-6">
                                <span class="text-gray-500 text-theme-sm dark:text-gray-400" x-text="template.id"></span>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <template x-if="!template.editing">
                                    <div><span class="block font-medium text-gray-800 text-theme-sm dark:text-white/90" x-text="template.name"></span></div>
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
                                    <svg class="h-1.5 w-1.5 fill-current" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4" /></svg>
                                    <span x-text="template.isActive ? 'Active' : 'Inactive'"></span>
                                </span>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center gap-2">
                                    <template x-if="!template.editing">
                                        <button @click="startEdit(template)" type="button" title="Edit template"
                                            class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 p-2 text-blue-600 shadow-theme-xs transition hover:bg-blue-100 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </template>
                                    <template x-if="template.editing">
                                        <button @click="saveEdit(template)" type="button" title="Save changes"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-theme-xs font-medium text-white shadow-theme-xs transition hover:bg-green-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            Save
                                        </button>
                                    </template>
                                    <template x-if="template.editing">
                                        <button @click="cancelEdit(template)" type="button" title="Discard changes"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-xs font-medium text-gray-600 shadow-theme-xs transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            Cancel
                                        </button>
                                    </template>
                                    <button @click="toggleActive(template)" type="button"
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
                                    <button @click="deleteTemplate(template)" type="button" title="Delete template permanently"
                                        class="ml-1 inline-flex items-center justify-center rounded-lg border border-red-200 bg-white p-2 text-red-500 shadow-theme-xs transition hover:border-red-300 hover:bg-red-50 dark:border-red-500/30 dark:bg-white/[0.03] dark:text-red-400 dark:hover:bg-red-500/10">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m3 0v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6h14z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
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