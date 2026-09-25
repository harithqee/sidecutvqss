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

    <div class="flex h-full flex-col rounded-2xl border border-gray-200 bg-gray-100 dark:border-gray-800 dark:bg-white/[0.03]">

        <div class="shadow-default flex flex-1 flex-col rounded-2xl bg-white px-4 pb-6 pt-5 dark:bg-gray-900 sm:px-6 sm:pb-6 sm:pt-6">
            <div class="mb-6 flex items-center gap-4">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gray-100 dark:bg-gray-800">
                    <svg class="fill-gray-800 dark:fill-white/90" width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M11.665 3.75621C11.8762 3.65064 12.1247 3.65064 12.3358 3.75621L18.7807 6.97856L12.3358 10.2009C12.1247 10.3065 11.8762 10.3065 11.665 10.2009L5.22014 6.97856L11.665 3.75621ZM4.29297 8.19203V16.0946C4.29297 16.3787 4.45347 16.6384 4.70757 16.7654L11.25 20.0366V11.6513C11.1631 11.6205 11.0777 11.5843 10.9942 11.5426L4.29297 8.19203ZM12.75 20.037L19.2933 16.7654C19.5474 16.6384 19.7079 16.3787 19.7079 16.0946V8.19202L13.0066 11.5426C12.9229 11.5844 12.8372 11.6208 12.75 11.6516V20.037ZM13.0066 2.41456C12.3732 2.09786 11.6277 2.09786 10.9942 2.41456L4.03676 5.89319C3.27449 6.27432 2.79297 7.05342 2.79297 7.90566V16.0946C2.79297 16.9469 3.27448 17.726 4.03676 18.1071L10.9942 21.5857L11.3296 20.9149L10.9942 21.5857C11.6277 21.9024 12.3732 21.9024 13.0066 21.5857L19.9641 18.1071C20.7264 17.726 21.2079 16.9469 21.2079 16.0946V7.90566C21.2079 7.05342 20.7264 6.27432 19.9641 5.89319L13.0066 2.41456Z" fill="" />
                    </svg>
                </div>
                <div>
                    <h4 class="text-title-sm font-bold text-gray-800 dark:text-white/90">Server Status</h4>
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
                            <img :src="barber.img" :alt="barber.name" class="h-11 w-11 shrink-0 rounded-full object-cover shadow-sm" />
                            <div class="flex min-w-0 flex-col">
                                <span class="truncate text-base font-bold text-gray-900 dark:text-white" x-text="barber.name"></span>
                                <span class="truncate text-xs font-medium text-gray-500 dark:text-gray-400" x-text="barber.role"></span>
                            </div>
                        </div>
                        <span class="flex shrink-0 items-center gap-2 text-sm font-medium transition-colors duration-300"
                              :class="barber.active ? 'text-green-500' : 'text-red-500'">
                            <svg class="h-2.5 w-2.5 fill-current" viewBox="0 0 12 12" xmlns="http://www.w3.org/2000/svg">
                                <circle cx="6" cy="6" r="6" />
                            </svg>
                            <span x-text="barber.active ? 'Active' : 'Inactive'"></span>
                        </span>
                    </div>
                </template>
            </div>

            <div x-show="!anyBarberActive" x-cloak class="mt-6 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-600 dark:bg-red-500/15 dark:text-red-400">
                No servers are currently active. Queue is paused.
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-4 px-4 py-3.5 sm:gap-8 sm:px-6 sm:py-5">
            <div class="min-w-[70px]">
                <p class="mb-1 text-center text-theme-xs text-gray-500 dark:text-gray-400 sm:text-sm">In Queue Now</p>
                <p class="flex items-center justify-center gap-1 text-sm font-semibold text-gray-800 dark:text-white/90 sm:text-lg">
                    <span x-text="queueCount" class="transition-all duration-300"></span>
                </p>
            </div>

            <div class="h-7 w-px bg-gray-200 dark:bg-gray-800"></div>

            <div class="min-w-[70px]">
                <p class="mb-1 text-center text-theme-xs text-gray-500 dark:text-gray-400 sm:text-sm">Queue Status</p>
                <div class="flex items-center justify-center">
                    <span class="flex items-center gap-1 rounded-full py-0.5 pl-2 pr-2.5 text-sm font-medium transition-colors duration-300"
                          :class="anyBarberActive ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' : 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-500'">
                        <svg class="fill-current" width="12" height="12" viewBox="0 0 12 12" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path fill-rule="evenodd" clip-rule="evenodd" d="M6 2C3.79086 2 2 3.79086 2 6C2 8.20914 3.79086 10 6 10C8.20914 10 10 8.20914 10 6C10 3.79086 8.20914 2 6 2Z" fill="" />
                        </svg>
                        <span x-text="anyBarberActive ? 'Active' : 'Inactive'"></span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>