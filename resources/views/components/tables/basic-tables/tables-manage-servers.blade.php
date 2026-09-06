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
        this.servers = this.servers.filter(function(s) {
            return s.id !== server.id;
        });
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
            <table class="w-full min-w-[800px]">
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
                                        <input type="text" x-model="server.name" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-theme-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90" />
                                    </template>
                                </div>
                            </td>

                            <td class="px-5 py-4 sm:px-6">
                                <template x-if="!server.editing">
                                    <p class="text-gray-500 text-theme-sm dark:text-gray-400" x-text="server.role"></p>
                                </template>
                                <template x-if="server.editing">
                                    <input type="text" x-model="server.role" class="w-full rounded-lg border border-gray-300 px-2 py-1.5 text-theme-sm dark:border-gray-700 dark:bg-gray-800 dark:text-white/90" />
                                </template>
                            </td>

                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-theme-xs inline-block rounded-full px-2 py-0.5 font-medium"
                                   :class="server.isActive ? 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-500' : 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-500'"
                                   x-text="server.isActive ? 'Active' : 'Inactive'"></p>
                            </td>

                            <td class="px-5 py-4 sm:px-6">
                                <div class="flex items-center gap-2">

                                    <template x-if="!server.editing">
                                        <button
                                            @click="startEdit(server)"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-xs font-medium text-gray-700 shadow-theme-xs transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]">
                                            Edit
                                        </button>
                                    </template>

                                    <template x-if="server.editing">
                                        <button
                                            @click="saveEdit(server)"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg bg-green-600 px-3 py-2 text-theme-xs font-medium text-white shadow-theme-xs transition hover:bg-green-700">
                                            Save
                                        </button>
                                    </template>

                                    <template x-if="server.editing">
                                        <button
                                            @click="cancelEdit(server)"
                                            type="button"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-xs font-medium text-gray-700 shadow-theme-xs transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]">
                                            Cancel
                                        </button>
                                    </template>

                                    <button
                                        @click="toggleActive(server)"
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-theme-xs font-medium text-gray-700 shadow-theme-xs transition hover:bg-gray-50 dark:border-gray-700 dark:bg-white/[0.03] dark:text-gray-300 dark:hover:bg-white/[0.05]">
                                        <span x-text="server.isActive ? 'Deactivate' : 'Activate'"></span>
                                    </button>

                                    <button
                                        @click="deleteServer(server)"
                                        type="button"
                                        class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-3 py-2 text-theme-xs font-medium text-white shadow-theme-xs transition hover:bg-red-700">
                                        Delete
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
