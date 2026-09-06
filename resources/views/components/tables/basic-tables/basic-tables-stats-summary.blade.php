<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:gap-6 xl:grid-cols-4"
     x-data="{
        stats: [
            { label: 'Customers Today', value: '48', change: '+12%', trend: 'up' },
            { label: 'Avg Wait Time', value: '22 min', change: '-4%', trend: 'down' },
            { label: 'Avg Service Time', value: '25 min', change: '+2%', trend: 'up' },
            { label: 'Completion Rate', value: '92%', change: '+3%', trend: 'up' },
        ]
     }">
    <template x-for="stat in stats" :key="stat.label">
        <div class="rounded-2xl border border-gray-200 bg-white p-5 dark:border-gray-800 dark:bg-white/[0.03] md:p-6">
            <span class="text-sm text-gray-500 dark:text-gray-400" x-text="stat.label"></span>
            <div class="mt-2 flex items-end justify-between">
                <h4 class="font-bold text-gray-800 text-title-sm dark:text-white/90" x-text="stat.value"></h4>
                <span class="flex items-center gap-1 rounded-full py-0.5 pl-2 pr-2.5 text-xs font-medium"
                      :class="stat.trend === 'up' ? 'bg-success-50 text-success-600 dark:bg-success-500/15 dark:text-success-500' : 'bg-red-50 text-red-600 dark:bg-red-500/15 dark:text-red-500'"
                      x-text="stat.change"></span>
            </div>
        </div>
    </template>
</div>