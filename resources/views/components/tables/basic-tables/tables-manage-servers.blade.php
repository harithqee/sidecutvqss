<div x-data="{
    servers: [],
    loaded: false,
    adding: false,
    draft: { name: '', role: '' },

    get onDuty() { return this.servers.filter(s => s.isActive).length; },

    async init() {
        await this.loadServers();
    },

    toServer(s) {
        return { id: s.id, name: s.name, role: s.role, image: s.image, isActive: s.is_active, editing: false, form: { name: s.name, role: s.role } };
    },

    async loadServers() {
        const res = await fetch('/api/barbers', { headers: { Accept: 'application/json' } });
        const data = await res.json();
        this.servers = data.map(s => this.toServer(s));
        this.loaded = true;
    },

    changed() {
        window.dispatchEvent(new CustomEvent('sidecut:barbers-changed'));
    },

    async addServer() {
        if (!this.draft.name.trim() || !this.draft.role.trim()) {
            Alpine.store('toast').push('Enter a name and a role.', 'error');
            return;
        }
        const res = await fetch('/api/barbers', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ name: this.draft.name.trim(), role: this.draft.role.trim() })
        });
        if (!res.ok) {
            Alpine.store('toast').push('Could not add barber.', 'error');
            return;
        }
        this.servers.push(this.toServer(await res.json()));
        Alpine.store('toast').push(this.draft.name.trim() + ' added and on duty.');
        this.draft = { name: '', role: '' };
        this.adding = false;
        this.changed();
    },

    startEdit(server) {
        server.form = { name: server.name, role: server.role };
        server.editing = true;
    },

    async saveEdit(server) {
        const res = await fetch('/api/barbers/' + server.id, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ name: server.form.name, role: server.form.role })
        });
        if (!res.ok) {
            Alpine.store('toast').push('Could not save changes.', 'error');
            return;
        }
        server.name = server.form.name;
        server.role = server.form.role;
        server.editing = false;
        Alpine.store('toast').push('Changes saved.');
    },

    async toggleActive(server) {
        const res = await fetch('/api/barbers/' + server.id + '/toggle-active', {
            method: 'PATCH',
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) {
            Alpine.store('toast').push('Could not update status.', 'error');
            return;
        }
        const updated = await res.json();
        server.isActive = updated.is_active;
        Alpine.store('toast').push(server.name + (server.isActive ? ' is on duty.' : ' is off duty.'));
        this.changed();
    },

    async deleteServer(server) {
        const confirmed = await Alpine.store('confirm').ask({
            title: 'Remove ' + server.name + '?',
            message: 'They will no longer appear in the queue or the join page. Past history is kept.',
            confirmText: 'Remove barber',
        });
        if (!confirmed) return;
        const res = await fetch('/api/barbers/' + server.id, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json' }
        });
        if (res.status === 422) {
            const body = await res.json();
            Alpine.store('toast').push(body.message, 'error');
            return;
        }
        if (!res.ok) {
            Alpine.store('toast').push('Could not remove barber.', 'error');
            return;
        }
        this.servers = this.servers.filter(s => s.id !== server.id);
        Alpine.store('toast').push(server.name + ' removed.');
        this.changed();
    }
}">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-theme-sm text-gray-500 dark:text-gray-400">
            <span class="font-semibold tabular-nums text-gray-800 dark:text-white/90" x-text="onDuty"></span> of
            <span class="tabular-nums" x-text="servers.length"></span> on duty. Customers can only join while at least one barber is on duty.
        </p>
        <button type="button" @click="adding = true; $nextTick(() => $refs.newName.focus())" x-show="!adding"
            class="inline-flex h-10 items-center gap-2 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
            Add barber
        </button>
    </div>

    <!-- Add form -->
    <form x-show="adding" x-cloak @submit.prevent="addServer()" @keydown.escape="adding = false"
        class="mb-4 flex flex-col gap-3 rounded-2xl border border-gray-200 bg-white p-4 dark:border-gray-800 dark:bg-white/[0.03] sm:flex-row sm:items-end">
        <label class="flex-1">
            <span class="mb-1.5 block text-theme-xs font-medium text-gray-600 dark:text-gray-400">Name</span>
            <input x-ref="newName" type="text" x-model="draft.name" placeholder="e.g. Marcus" autocomplete="off"
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90" />
        </label>
        <label class="flex-1">
            <span class="mb-1.5 block text-theme-xs font-medium text-gray-600 dark:text-gray-400">Role</span>
            <input type="text" x-model="draft.role" placeholder="e.g. Senior Barber" autocomplete="off"
                class="h-11 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90" />
        </label>
        <div class="flex gap-2">
            <button type="button" @click="adding = false" class="h-11 rounded-lg border border-gray-300 px-4 text-theme-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-white/5">Cancel</button>
            <button type="submit" class="h-11 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs hover:bg-brand-600">Add barber</button>
        </div>
    </form>

    <ul class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <template x-for="server in servers" :key="server.id">
            <li class="flex flex-col rounded-2xl border bg-white p-5 transition dark:bg-white/[0.03]"
                :class="server.isActive ? 'border-gray-200 dark:border-gray-800' : 'border-gray-200 bg-gray-50/60 dark:border-gray-800 dark:bg-transparent'">
                <div class="flex items-start gap-3">
                    <template x-if="server.image">
                        <img :src="server.image" :alt="server.name" class="h-12 w-12 shrink-0 rounded-full object-cover" />
                    </template>
                    <template x-if="!server.image">
                        <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full text-base font-semibold"
                            :class="server.isActive ? sc.tone(server.name) : 'bg-gray-100 text-gray-400 dark:bg-white/5 dark:text-gray-500'"
                            x-text="sc.initials(server.name)"></span>
                    </template>

                    <div class="min-w-0 flex-1" x-show="!server.editing">
                        <p class="truncate text-base font-semibold text-gray-800 dark:text-white/90" x-text="server.name"></p>
                        <p class="truncate text-theme-sm text-gray-500 dark:text-gray-400" x-text="server.role"></p>
                    </div>

                    <!-- On-duty switch (TailAdmin toggle) -->
                    <label x-show="!server.editing" class="flex shrink-0 cursor-pointer select-none items-center">
                        <span class="sr-only" x-text="'On duty: ' + server.name"></span>
                        <span class="relative">
                            <input type="checkbox" class="peer sr-only" :checked="server.isActive" @change="toggleActive(server)" />
                            <span class="block h-6 w-11 rounded-full transition peer-focus-visible:ring-3 peer-focus-visible:ring-brand-500/30"
                                :class="server.isActive ? 'bg-success-500' : 'bg-gray-200 dark:bg-white/10'"></span>
                            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow-theme-sm transition-transform duration-200"
                                :class="server.isActive ? 'translate-x-5' : 'translate-x-0'"></span>
                        </span>
                    </label>

                    <form x-show="server.editing" @submit.prevent="saveEdit(server)" @keydown.escape="server.editing = false" class="min-w-0 flex-1 space-y-2">
                        <input type="text" x-model="server.form.name" aria-label="Name"
                            class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90" />
                        <input type="text" x-model="server.form.role" aria-label="Role"
                            class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90" />
                        <div class="flex justify-end gap-2 pt-1">
                            <button type="button" @click="server.editing = false" class="h-9 rounded-lg px-3 text-theme-xs font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">Cancel</button>
                            <button type="submit" class="h-9 rounded-lg bg-brand-500 px-3 text-theme-xs font-medium text-white hover:bg-brand-600">Save</button>
                        </div>
                    </form>
                </div>

                <div x-show="!server.editing" class="mt-5 flex items-center justify-between border-t border-gray-100 pt-4 dark:border-gray-800">
                    <p class="text-theme-xs inline-block whitespace-nowrap rounded-full px-2 py-0.5 font-medium" :class="server.isActive ? 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-500' : 'bg-gray-100 text-gray-500 dark:bg-gray-500/15 dark:text-gray-400'" x-text="server.isActive ? 'On duty' : 'Off duty'"></p>
                    <div class="flex items-center gap-1">
                        <button type="button" @click="startEdit(server)" class="h-8 rounded-md px-2.5 text-theme-xs font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-white/5">Edit</button>
                        <button type="button" @click="deleteServer(server)" class="h-8 rounded-md px-2.5 text-theme-xs font-medium text-gray-500 hover:bg-error-50 hover:text-error-600 dark:text-gray-400 dark:hover:bg-error-500/10 dark:hover:text-error-400">Remove</button>
                    </div>
                </div>
            </li>
        </template>
    </ul>

    <div x-show="loaded && servers.length === 0" class="rounded-2xl border border-dashed border-gray-300 px-5 py-14 text-center dark:border-gray-700">
        <p class="text-theme-sm font-medium text-gray-700 dark:text-gray-300">No barbers yet</p>
        <p class="mt-1 text-theme-xs text-gray-500 dark:text-gray-400">Add your team so customers can pick who cuts their hair.</p>
    </div>
</div>
