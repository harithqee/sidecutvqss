<div x-data="{
    servers: [
        {
            id: 1,
            name: 'Lindsey Curtis',
            role: 'Senior Barber',
            image: './images/user/user-17.jpg',
            isActive: true,
            editing: false,
        },
        {
            id: 2,
            name: 'Danial',
            role: 'Junior Barber',
            image: './images/user/user-18.jpg',
            isActive: false,
            editing: false,
        },
    ],
    newServerName: '',
    newServerRole: '',
    nextId: 3,

    addServer() {
        if (!this.newServerName.trim() || !this.newServerRole.trim()) {
            alert('Please enter both name and role.');
            return;
        }
        this.servers.push({
            id: this.nextId,
            name: this.newServerName,
            role: this.newServerRole,
            image: './images/user/user-01.jpg',
            isActive: true,
            editing: false,
        });
        this.nextId = this.nextId + 1;
        this.newServerName = '';
        this.newServerRole = '';
    },

    startEdit(server) {
        server.editing = true;
    },

    saveEdit(server) {
        server.editing = false;
    },

    cancelEdit(server) {
        server.editing = false;
    },

    toggleActive(server) {
        server.isActive = !server.isActive;
    },

    deleteServer(server) {
        if (confirm('Delete ' + server.name + '? This cannot be undone.')) {
            this.servers = this.servers.filter(function(s) {
                return s.id !== server.id;
            });
        }
    }
}">
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">

        <div class="flex flex-wrap items-end gap-3 border-b border-gray-100 px-5 py-4 dark:border-gray-800 sm:px-6">
            <div class="min-w-[180px] flex-1">
                <label class="mb-1.5 block text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                    Server Name
                </label>
                <input
                    type="text"
                    x-model="newServerName"
                    placeholder="e.g. Marcus"
                    class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90" />
            </div>
            <div class="min-w-[180px] flex-1">
                <label class="mb-1.5 block text-theme-xs font-medium text-gray-500 dark:text-gray-400">
                    Role
                </label>
                <input
                    type="text"
                    x-model="newServerRole"
                    placeholder="e.g. Junior Barber"
                    class="h-10 w-full rounded-lg border border-gray-300 bg-transparent px-3 text-theme-sm text-gray-800 outline-none focus:border-brand-300 focus:ring-3 focus:ring-brand-500/10 dark:border-gray-700 dark:text-white/90" />
            </div>
            <button
                @click="addServer()"
                type="button"
                class="inline-flex h-10 items-center gap-1.5 rounded-lg bg-brand-500 px-4 text-theme-sm font-medium text-white shadow-theme-xs transition hover:bg-brand-600">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none">
                    <path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                Add Server
            </button>
        </div>

        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <table class="w-full min-w-[850px]">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                Server
                            </p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                Role
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
                    <template x-for="server in servers" :key="server.id">
                        <tr class="border-b border-gray-100 dark:border-gray-800">

                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 overflow-hidden rounded-full">
                                        <img :src="server.image" :alt="server.name">
                                    </div>
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
                                    <svg class="h-1.5 w-1.5 fill-current" viewBox="0 0 8 8">
                                        <circle cx="4" cy="4" r="4" />
                                    </svg>
                                    <span x-text="server.isActive ? 'Active' : 'Inactive'"></span>
                                </span>
                            </td>

                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center gap-2">

                                    <!-- Edit: informational — outlined blue -->
                                    <template x-if="!server.editing">
                                        <button
                                            @click="startEdit(server)"
                                            type="button"
                                            title="Edit server details"
                                            class="inline-flex items-center justify-center rounded-lg border border-blue-200 bg-blue-50 p-2 text-blue-600 shadow-theme-xs transition hover:bg-blue-100 dark:border-blue-500/30 dark:bg-blue-500/10 dark:text-blue-400 dark:hover:bg-blue-500/20">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </template>

                                    <!-- Save: positive/confirm — solid green -->
                                    <template x-if="server.editing">
                                        <button
                                            @click="saveEdit(server)"
                                            type="button"
                                            title="Save changes"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-theme-xs font-medium text-white shadow-theme-xs transition hover:bg-green-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none">
                                                <path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Save
                                        </button>
                                    </template>

                                    <!-- Cancel: neutral — plain gray outline -->
                                    <template x-if="server.editing">
                                        <button
                                            @click="cancelEdit(server)"
                                            type="button"
                                            title="Discard changes"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-xs font-medium text-gray-600 shadow-theme-xs transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none">
                                                <path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                            Cancel
                                        </button>
                                    </template>

                                    <!-- Toggle Active/Inactive: caution when deactivating, positive when activating -->
                                    <button
                                        @click="toggleActive(server)"
                                        type="button"
                                        :title="server.isActive ? 'Pause this server' : 'Bring this server back online'"
                                        class="inline-flex items-center justify-center rounded-lg border p-2 shadow-theme-xs transition"
                                        :class="server.isActive
                                            ? 'border-yellow-200 bg-yellow-50 text-yellow-600 hover:bg-yellow-100 dark:border-yellow-500/30 dark:bg-yellow-500/10 dark:text-yellow-400 dark:hover:bg-yellow-500/20'
                                            : 'border-green-200 bg-green-50 text-green-600 hover:bg-green-100 dark:border-green-500/30 dark:bg-green-500/10 dark:text-green-400 dark:hover:bg-green-500/20'">
                                        <svg x-show="server.isActive" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <rect x="6" y="4" width="4" height="16" rx="1" fill="currentColor"/>
                                            <rect x="14" y="4" width="4" height="16" rx="1" fill="currentColor"/>
                                        </svg>
                                        <svg x-show="!server.isActive" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M6 4l14 8-14 8V4z" fill="currentColor"/>
                                        </svg>
                                    </button>

                                    <!-- Delete: destructive — solid red, isolated with left margin -->
                                    <button
                                        @click="deleteServer(server)"
                                        type="button"
                                        title="Remove server permanently"
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