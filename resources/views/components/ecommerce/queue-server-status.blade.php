<div class="col-span-12 xl:col-span-6"
     x-data="{
         barbers: [],
         queueCount: 0,

         get anyBarberActive() {
             return this.barbers.some(b => b.active);
         },

         async init() {
             await this.loadBarbers();
             await this.loadQueueCount();
         },

         async loadBarbers() {
             const res = await fetch('/api/barbers');
             const data = await res.json();
             this.barbers = data.map(function(b) {
                 return {
                     name: b.name,
                     role: b.role,
                     img: b.image || './images/user/user-01.jpg',
                     active: b.is_active,
                 };
             });
         },

         async loadQueueCount() {
             const res = await fetch('/api/queue');
             const tickets = await res.json();
             this.queueCount = tickets.length;
         }
     }">

    <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">

        <div class="mb-6 flex items-center justify-between">
            <div>
                <h4 class="font-bold text-gray-800 text-title-sm dark:text-white/90">Server Status</h4>
                <span class="text-sm text-gray-500 dark:text-gray-400">Current availability</span>
            </div>
        </div>

        <div class="flex max-h-[220px] flex-col gap-5 overflow-y-auto pr-1 sm:max-h-[260px] lg:max-h-[300px]
                    [&::-webkit-scrollbar]:w-1.5
                    [&::-webkit-scrollbar-track]:bg-transparent
                    [&::-webkit-scrollbar-thumb]:rounded-full
                    [&::-webkit-scrollbar-thumb]:bg-gray-200
                    dark:[&::-webkit-scrollbar-thumb]:bg-gray-700">
            <template x-for="(barber, index) in barbers" :key="index">
                <div class="flex items-center justify-between gap-3">
                    <div class="flex min-w-0 items-center gap-3">
                        <img :src="barber.img" :alt="barber.name" class="h-11 w-11 shrink-0 rounded-full object-cover" />
                        <div class="flex min-w-0 flex-col">
                            <span class="truncate text-sm font-medium text-gray-800 dark:text-white/90" x-text="barber.name"></span>
                            <span class="truncate text-theme-xs text-gray-500 dark:text-gray-400" x-text="barber.role"></span>
                        </div>
                    </div>

                    <span class="flex items-center gap-1 rounded-full py-0.5 pl-2 pr-2.5 text-xs font-medium"
                          :class="barber.active ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' : 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-500'">
                        <svg class="h-1.5 w-1.5 fill-current" viewBox="0 0 8 8" xmlns="http://www.w3.org/2000/svg"><circle cx="4" cy="4" r="4" /></svg>
                        <span x-text="barber.active ? 'Active' : 'Inactive'"></span>
                    </span>
                </div>
            </template>
        </div>

        <div x-show="!anyBarberActive" x-cloak class="mt-6 rounded-lg bg-red-50 px-3 py-2 text-theme-xs text-red-600 dark:bg-red-500/15 dark:text-red-400">
            No servers are currently active. Queue is paused.
        </div>

        <div class="mt-6 flex items-center gap-6 border-t border-gray-100 pt-5 dark:border-gray-800">
            <div>
                <span class="text-theme-xs text-gray-500 dark:text-gray-400">In Queue Now</span>
                <p class="mt-1 text-sm font-semibold text-gray-800 dark:text-white/90" x-text="queueCount"></p>
            </div>

            <div class="h-8 w-px bg-gray-200 dark:bg-gray-800"></div>

            <div>
                <span class="text-theme-xs text-gray-500 dark:text-gray-400">Queue Status</span>
                <p class="mt-1">
                    <span class="flex w-fit items-center gap-1 rounded-full py-0.5 pl-2 pr-2.5 text-xs font-medium"
                          :class="anyBarberActive ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' : 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-500'">
                        <svg class="fill-current" width="10" height="10" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="6" cy="6" r="5" fill="currentColor" />
                        </svg>
                        <span x-text="anyBarberActive ? 'Active' : 'Inactive'"></span>
                    </span>
                </p>
            </div>
        </div>
    </div>
</div>