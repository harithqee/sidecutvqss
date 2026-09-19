<div x-data="{
    servers: [],
    newServerName: '',
    newServerRole: '',
    toast: { show: false, message: '', type: 'success' },

    showToast(message, type) {
        this.toast.message = message;
        this.toast.type = type || 'success';
        this.toast.show = true;
    },

    async init() {
        await this.loadServers();
    },

    async loadServers() {
        const res = await fetch('/api/barbers');
        const data = await res.json();
        this.servers = data.map(function(s) {
            return {
                id: s.id,
                name: s.name,
                role: s.role,
                image: s.image || './images/user/user-01.jpg',
                isActive: s.is_active,
                editing: false,
            };
        });
    },

    async addServer() {
        if (!this.newServerName.trim() || !this.newServerRole.trim()) {
            this.showToast('Please enter both name and role.', 'error');
            return;
        }
        const res = await fetch('/api/barbers', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ name: this.newServerName, role: this.newServerRole })
        });
        if (!res.ok) {
            this.showToast('Could not add server.', 'error');
            return;
        }
        const s = await res.json();
        this.servers.push({
            id: s.id,
            name: s.name,
            role: s.role,
            image: s.image || './images/user/user-01.jpg',
            isActive: s.is_active,
            editing: false,
        });
        this.newServerName = '';
        this.newServerRole = '';
        this.showToast('Server added successfully.', 'success');
    },

    startEdit(server) {
        server.editing = true;
    },

    async saveEdit(server) {
        const res = await fetch('/api/barbers/' + server.id, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ name: server.name, role: server.role })
        });
        if (!res.ok) {
            this.showToast('Could not save changes.', 'error');
            return;
        }
        server.editing = false;
        this.showToast('Server updated successfully.', 'success');
    },

    cancelEdit(server) {
        server.editing = false;
    },

    async toggleActive(server) {
        const res = await fetch('/api/barbers/' + server.id + '/toggle-active', {
            method: 'PATCH',
            headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) {
            this.showToast('Could not update status.', 'error');
            return;
        }
        const updated = await res.json();
        server.isActive = updated.is_active;
        this.showToast(server.isActive ? 'Server activated.' : 'Server deactivated.', 'success');
    },

    async deleteServer(server) {
        if (!confirm('Delete ' + server.name + '? This cannot be undone.')) {
            return;
        }
        const res = await fetch('/api/barbers/' + server.id, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json' }
        });
        if (res.status === 422) {
            const body = await res.json();
            this.showToast(body.message, 'error');
            return;
        }
        if (!res.ok) {
            this.showToast('Could not delete server.', 'error');
            return;
        }
        this.servers = this.servers.filter(function(s) {
            return s.id !== server.id;
        });
        this.showToast('Server deleted successfully.', 'success');
    }
}">

    <!-- Popup alert -->
    <div x-show="toast.show" x-cloak class="fixed inset-0 z-999 flex items-center justify-center bg-black/40 px-4" style="display: none;">
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
            <h3 class="mb-1 text-base font-semibold text-gray-800 dark:text-white/90" x-text="toast.type === 'success' ? 'Success' : 'Failed'"></h3>
            <p class="mb-5 text-theme-sm text-gray-500 dark:text-gray-400" x-text="toast.message"></p>
            <button @click="toast.show = false" type="button" class="w-full rounded-lg bg-brand-500 px-4 py-2.5 text-theme-sm font-medium text-white transition hover:bg-brand-600">OK</button>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">

        <div class="flex flex-wrap items-end gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800 sm:px-6">
            <div class="min-w-[180px] flex-1">
                <label class="mb-1.5 block text-theme-xs font-medium text-gray-500 dark:text-gray-400">Server Name</label>
                <input type="text" x-model="newServerName" placeholder="e.g. Marcus"
                    class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90" />
            </div>
            <div class="min-w-[180px] flex-1">
                <label class="mb-1.5 block text-theme-xs font-medium text-gray-500 dark:text-gray-400">Role</label>
                <input type="text" x-model="newServerRole" placeholder="e.g. Junior Barber"
                    class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90" />
            </div>
            <button @click="addServer()" type="button"
                class="inline-flex h-10 items-center gap-1.5 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                Add Server
            </button>
        </div>

        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <table class="w-full min-w-[900px]">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">ID</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Server</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Role</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Status</p></th>
                        <th class="px-5 py-3 text-left sm:px-6"><p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">Actions</p></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="server in servers" :key="server.id">
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-5 py-4 sm:px-6"><span class="text-gray-500 text-theme-sm dark:text-gray-400" x-text="server.id"></span></td>
                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 overflow-hidden rounded-full"><img :src="server.image" :alt="server.name"></div>
                                    <template x-if="!server.editing">
                                        <span class="font-medium text-gray-800 text-theme-sm dark:text-white/90" x-text="server.name"></span>
                                    </template>
                                    <template x-if="server.editing">
                                        <input type="text" x-model="server.name" class="w-full rounded-lg border border-brand-300 px-2 py-1.5 text-theme-sm ring-3 ring-brand-500/10 dark:border-brand-700 dark:bg-gray-800 dark:text-white/90" />
                                    </template>
                                </div>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <template x-if="!server.editing">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400" x-text="server.role"></p>
                                </template>
                                <template x-if="server.editing">
                                    <input type="text" x-model="server.role" class="w-full rounded-lg border border-brand-300 px-2 py-1.5 text-theme-sm ring-3 ring-brand-500/10 dark:border-brand-700 dark:bg-gray-800 dark:text-white/90" />
                                </template>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2 py-0.5 text-theme-xs font-medium"
                                   :class="server.isActive ? 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-500' : 'bg-gray-100 text-gray-500 dark:bg-gray-500/15 dark:text-gray-400'">
                                    <svg class="h-1.5 w-1.5 fill-current" viewBox="0 0 8 8"><circle cx="4" cy="4" r="4" /></svg>
                                    <span x-text="server.isActive ? 'Active' : 'Inactive'"></span>
                                </span>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center gap-2">
                                    <template x-if="!server.editing">
                                        <button @click="startEdit(server)" type="button" title="Edit server details"
                                            class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 p-2 text-blue-600 shadow-theme-xs transition hover:bg-blue-100 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </template>
                                    <template x-if="server.editing">
                                        <button @click="saveEdit(server)" type="button" title="Save changes"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-theme-xs font-medium text-white shadow-theme-xs transition hover:bg-green-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            Save
                                        </button>
                                    </template>
                                    <template x-if="server.editing">
                                        <button @click="cancelEdit(server)" type="button" title="Discard changes"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-xs font-medium text-gray-600 shadow-theme-xs transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                            Cancel
                                        </button>
                                    </template>
                                    <button @click="toggleActive(server)" type="button"
                                        :title="server.isActive ? 'Pause this server' : 'Bring this server back online'"
                                        class="inline-flex items-center justify-center rounded-lg border p-2 shadow-theme-xs transition"
                                        :class="server.isActive
                                            ? 'border-yellow-200 bg-yellow-50 text-yellow-600 hover:bg-yellow-100 dark:border-yellow-500/30 dark:bg-yellow-500/10 dark:text-yellow-400 dark:hover:bg-yellow-500/20'
                                            : 'border-green-200 bg-green-50 text-green-600 hover:bg-green-100 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-400 dark:hover:bg-green-500/20'">
                                        <svg x-show="server.isActive" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"><rect x="6" y="4" width="4" height="16" rx="1" fill="currentColor"/><rect x="14" y="4" width="4" height="16" rx="1" fill="currentColor"/></svg>
                                        <svg x-show="!server.isActive" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"><path d="M6 4l14 8-14 8V4z" fill="currentColor"/></svg>
                                    </button>
                                    <button @click="deleteServer(server)" type="button" title="Remove server permanently"
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