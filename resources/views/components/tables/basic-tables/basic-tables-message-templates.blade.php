<div x-data="{
    templates: [],
    loaded: false,
    editor: null,
    saving: false,
    // The queue sends templates 1–3 automatically (join, turn approaching, receipt).
    systemIds: [1, 2, 3],
    variables: ['customerName', 'queueNumber', 'waitTime', 'position'],
    triggerSuggestions: ['Customer joins the queue', 'Staff taps Send SMS', 'Service is finished'],

    async init() {
        await this.loadTemplates();
    },

    toTemplate(t) {
        return {
            id: t.id,
            name: t.name,
            trigger: t.trigger_event,
            message: t.message_body,
            isActive: t.is_active,
            updatedAt: new Date(t.updated_at).toLocaleDateString(undefined, { day: 'numeric', month: 'short' }),
        };
    },

    async loadTemplates() {
        const res = await fetch('/api/message-templates', { headers: { Accept: 'application/json' } });
        const data = await res.json();
        this.templates = data.map(t => this.toTemplate(t));
        this.loaded = true;
    },

    placeholder(name) {
        return '{' + '{' + name + '}' + '}';
    },

    // Escape the message, then mark up the variables so they read as slots, not text.
    render(text) {
        const escaped = String(text || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
        return escaped.replace(/\{\{\s*(\w+)\s*\}\}/g, '<span class=\'rounded bg-brand-100 px-1 py-px font-medium text-brand-700 dark:bg-brand-500/20 dark:text-brand-200\'>$1</span>');
    },

    segments(text) {
        const length = String(text || '').length;
        return length <= 160 ? 1 : Math.ceil(length / 153);
    },

    openCreate() {
        this.editor = { id: null, name: '', trigger: '', message: '' };
        this.$nextTick(() => this.$refs.editorName.focus());
    },

    openEdit(template) {
        this.editor = { id: template.id, name: template.name, trigger: template.trigger, message: template.message };
        this.$nextTick(() => this.$refs.editorMessage.focus());
    },

    insertVariable(name) {
        const field = this.$refs.editorMessage;
        const token = this.placeholder(name);
        const start = field.selectionStart ?? this.editor.message.length;
        const end = field.selectionEnd ?? start;
        this.editor.message = this.editor.message.slice(0, start) + token + this.editor.message.slice(end);
        this.$nextTick(() => {
            field.focus();
            field.setSelectionRange(start + token.length, start + token.length);
        });
    },

    async save() {
        const e = this.editor;
        if (!e.name.trim() || !e.trigger.trim() || !e.message.trim()) {
            Alpine.store('toast').push('Fill in the name, when it is sent, and the message.', 'error');
            return;
        }
        this.saving = true;
        const isNew = e.id === null;
        const res = await fetch(isNew ? '/api/message-templates' : '/api/message-templates/' + e.id, {
            method: isNew ? 'POST' : 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ name: e.name.trim(), trigger_event: e.trigger.trim(), message_body: e.message.trim(), ...(isNew ? { is_active: true } : {}) })
        });
        this.saving = false;
        if (!res.ok) {
            Alpine.store('toast').push(isNew ? 'Could not create template.' : 'Could not save changes.', 'error');
            return;
        }
        const saved = this.toTemplate(await res.json());
        if (isNew) {
            this.templates.push(saved);
        } else {
            const index = this.templates.findIndex(t => t.id === e.id);
            if (index !== -1) this.templates[index] = { ...this.templates[index], ...saved, isActive: this.templates[index].isActive };
        }
        this.editor = null;
        Alpine.store('toast').push(isNew ? 'Template created.' : 'Template saved.');
    },

    async toggleActive(template) {
        const res = await fetch('/api/message-templates/' + template.id + '/toggle-active', {
            method: 'PATCH',
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) {
            Alpine.store('toast').push('Could not update template.', 'error');
            return;
        }
        const updated = await res.json();
        template.isActive = updated.is_active;
        Alpine.store('toast').push(template.isActive ? template.name + ' will be sent.' : template.name + ' is paused. Customers won\'t get it.');
    },

    async deleteTemplate(template) {
        const isSystem = this.systemIds.includes(template.id);
        const confirmed = await Alpine.store('confirm').ask({
            title: 'Delete ' + template.name + '?',
            message: isSystem
                ? 'The queue sends this text automatically. Deleting it stops those messages. To stop it for now, pause it instead.'
                : 'This template will be removed for good.',
            confirmText: 'Delete template',
        });
        if (!confirmed) return;
        const res = await fetch('/api/message-templates/' + template.id, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) {
            Alpine.store('toast').push('Could not delete template.', 'error');
            return;
        }
        this.templates = this.templates.filter(t => t.id !== template.id);
        Alpine.store('toast').push('Template deleted.');
    }
}" @keydown.escape.window="editor = null">

    <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white/90">SMS templates</h2>
            <p class="mt-0.5 text-theme-sm text-gray-500 dark:text-gray-400">Highlighted words are filled in for each customer when the text goes out.</p>
        </div>
        <button type="button" @click="openCreate()"
            class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            New template
        </button>
    </div>

    <ul class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
        <template x-for="template in templates" :key="template.id">
            <li class="flex flex-col rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03]">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h3 class="truncate text-base font-semibold text-gray-800 dark:text-white/90" x-text="template.name"></h3>
                        <p class="mt-0.5 truncate text-theme-xs text-gray-500 dark:text-gray-400">
                            Sent when: <span class="text-gray-700 dark:text-gray-300" x-text="template.trigger"></span>
                        </p>
                    </div>
                    <label class="flex shrink-0 cursor-pointer select-none items-center">
                        <span class="sr-only" x-text="'Send ' + template.name"></span>
                        <span class="relative">
                            <input type="checkbox" class="peer sr-only" :checked="template.isActive" @change="toggleActive(template)" />
                            <span class="block h-6 w-11 rounded-full transition peer-focus-visible:ring-3 peer-focus-visible:ring-brand-500/30"
                                :class="template.isActive ? 'bg-brand-500' : 'bg-gray-200 dark:bg-white/10'"></span>
                            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-theme-sm transition-transform duration-200"
                                :class="template.isActive ? 'translate-x-5' : 'translate-x-0'"></span>
                        </span>
                    </label>
                </div>

                <!-- Phone-style preview -->
                <div class="mt-4 flex-1 rounded-xl bg-gray-50 p-3 dark:bg-gray-900/60" :class="template.isActive ? '' : 'opacity-60'">
                    <p class="max-w-[92%] rounded-2xl rounded-bl-md bg-white px-3.5 py-2.5 text-theme-sm leading-relaxed whitespace-pre-line text-gray-700 shadow-theme-xs dark:bg-gray-800 dark:text-gray-200"
                        x-html="render(template.message)"></p>
                </div>

                <div class="mt-4 flex items-center justify-between text-theme-xs">
                    <span class="tabular-nums text-gray-400 dark:text-gray-500">
                        <span x-text="template.isActive ? '' : 'Paused · '"></span><span x-text="template.message.length + ' chars · ' + segments(template.message) + ' SMS'"></span>
                    </span>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="openEdit(template)" class="h-8 rounded-md px-2.5 font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">Edit</button>
                        <button type="button" @click="deleteTemplate(template)" class="h-8 rounded-md px-2.5 font-medium text-gray-500 hover:bg-error-50 hover:text-error-600 dark:text-gray-400 dark:hover:bg-error-500/10 dark:hover:text-error-400">Delete</button>
                    </div>
                </div>
            </li>
        </template>
    </ul>

    <div x-show="loaded && templates.length === 0" class="rounded-2xl border border-dashed border-gray-300 px-5 py-12 text-center dark:border-gray-700">
        <p class="text-theme-sm font-medium text-gray-700 dark:text-gray-300">No templates yet</p>
        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">Create one to text customers when they join or when their turn is near.</p>
    </div>

    <!-- Create / edit -->
    <div x-show="editor !== null" x-cloak style="display: none;" class="fixed inset-0 z-999999 flex items-end justify-center p-4 sm:items-center"
        role="dialog" aria-modal="true" aria-labelledby="template-editor-title">
        <div class="absolute inset-0 bg-gray-900/50" @click="editor = null" x-show="editor !== null" x-transition.opacity></div>

        <template x-if="editor">
            <form @submit.prevent="save()" class="relative flex max-h-[calc(100vh-2rem)] w-full max-w-2xl flex-col overflow-hidden rounded-2xl bg-white shadow-theme-xl dark:bg-gray-900 dark:ring-1 dark:ring-gray-800">
                <header class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <h2 id="template-editor-title" class="text-lg font-semibold text-gray-800 dark:text-white/90" x-text="editor.id ? 'Edit template' : 'New template'"></h2>
                    <button type="button" @click="editor = null" aria-label="Close" class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-700 dark:hover:bg-white/5 dark:hover:text-white">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>
                </header>

                <div class="grid flex-1 gap-6 overflow-y-auto px-6 py-5 md:grid-cols-[1fr_220px]">
                    <div class="space-y-4">
                        <label class="block">
                            <span class="mb-1.5 block text-theme-xs font-medium text-gray-600 dark:text-gray-400">Name</span>
                            <input x-ref="editorName" type="text" x-model="editor.name" placeholder="e.g. Running late reminder"
                                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90" />
                        </label>
                        <label class="block">
                            <span class="mb-1.5 block text-theme-xs font-medium text-gray-600 dark:text-gray-400">Sent when</span>
                            <input type="text" x-model="editor.trigger" list="trigger-suggestions" placeholder="e.g. Customer joins the queue"
                                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90" />
                            <datalist id="trigger-suggestions">
                                <template x-for="suggestion in triggerSuggestions" :key="suggestion"><option :value="suggestion"></option></template>
                            </datalist>
                        </label>
                        <div>
                            <div class="mb-1.5 flex items-baseline justify-between">
                                <label for="template-message" class="text-theme-xs font-medium text-gray-600 dark:text-gray-400">Message</label>
                                <span class="text-theme-xs tabular-nums"
                                    :class="editor.message.length > 500 ? 'text-error-600' : 'text-gray-400 dark:text-gray-500'"
                                    x-text="editor.message.length + ' / 500'"></span>
                            </div>
                            <textarea id="template-message" x-ref="editorMessage" x-model="editor.message" rows="6" maxlength="500"
                                class="w-full resize-y rounded-lg border border-gray-300 bg-transparent px-3 py-2.5 text-theme-sm leading-relaxed text-gray-800 outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90"></textarea>
                            <div class="mt-2 flex flex-wrap items-center gap-1.5">
                                <span class="text-theme-xs text-gray-500 dark:text-gray-400">Insert:</span>
                                <template x-for="variable in variables" :key="variable">
                                    <button type="button" @click="insertVariable(variable)"
                                        class="rounded-md border border-gray-200 px-2 py-1 font-mono text-[11px] text-gray-600 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 dark:border-gray-700 dark:text-gray-300 dark:hover:border-brand-500/40 dark:hover:bg-brand-500/10 dark:hover:text-brand-200"
                                        x-text="variable"></button>
                                </template>
                            </div>
                        </div>
                    </div>

                    <div>
                        <p class="mb-1.5 text-theme-xs font-medium text-gray-600 dark:text-gray-400">Preview</p>
                        <div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-800/60">
                            <p x-show="editor.message.trim()" class="rounded-2xl rounded-bl-md bg-white px-3.5 py-2.5 text-theme-sm leading-relaxed whitespace-pre-line text-gray-700 shadow-theme-xs dark:bg-gray-800 dark:text-gray-200"
                                x-html="render(editor.message)"></p>
                            <p x-show="!editor.message.trim()" class="py-6 text-center text-theme-xs text-gray-400">Start typing to preview.</p>
                        </div>
                        <p class="mt-2 text-theme-xs text-gray-500 dark:text-gray-400" x-show="editor.message.trim()"
                            x-text="segments(editor.message) === 1 ? 'Fits in one SMS.' : 'Splits into ' + segments(editor.message) + ' SMS parts.'"></p>
                    </div>
                </div>

                <footer class="flex justify-end gap-2 border-t border-gray-100 px-6 py-4 dark:border-gray-800">
                    <button type="button" @click="editor = null" class="h-11 rounded-lg border border-gray-300 px-4 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Cancel</button>
                    <button type="submit" :disabled="saving" class="h-11 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600 disabled:opacity-60"
                        x-text="saving ? 'Saving…' : (editor.id ? 'Save changes' : 'Create template')"></button>
                </footer>
            </form>
        </template>
    </div>
</div>
