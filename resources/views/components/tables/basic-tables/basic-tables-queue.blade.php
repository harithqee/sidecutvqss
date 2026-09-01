<div x-data="{
    orders: [
        {
            id: 1,
            user: {
                image: './images/user/user-17.jpg',
                name: 'Lindsey Curtis',
                role: 'Web Designer',
            },
            queueNumber: '#1',
            team: {
                images: [
                    './images/user/user-22.jpg',
                    './images/user/user-23.jpg',
                    './images/user/user-24.jpg',
                ],
            },
            status: 'Serving',
        },
        {
            id: 2,
            user: {
                image: './images/user/user-18.jpg',
                name: 'Kaiya George',
                role: 'Project Manager',
            },
            queueNumber: '#2',
            team: {
                images: [
                    './images/user/user-25.jpg',
                    './images/user/user-26.jpg',
                ],
            },
            status: 'In Queue',
        },
        {
            id: 3,
            user: {
                image: './images/user/user-19.jpg',
                name: 'Zain Geidt',
                role: 'Content Writer',
            },
            queueNumber: '#3',
            team: {
                images: [
                    './images/user/user-27.jpg',
                ],
            },
            status: 'In Queue',
        },
        {
            id: 4,
            user: {
                image: './images/user/user-20.jpg',
                name: 'Abram Schleifer',
                role: 'Digital Marketer',
            },
            queueNumber: '#4',
            team: {
                images: [
                    './images/user/user-28.jpg',
                    './images/user/user-29.jpg',
                    './images/user/user-30.jpg',
                ],
            },
            status: 'Canceled',
        },
        {
            id: 5,
            user: {
                image: './images/user/user-21.jpg',
                name: 'Carla George',
                role: 'Front-end Developer',
            },
            queueNumber: '#5',
            team: {
                images: [
                    './images/user/user-31.jpg',
                    './images/user/user-32.jpg',
                    './images/user/user-33.jpg',
                ],
            },
            status: 'In Queue',
        },
    ],
    getStatusClass(status) {
        const classes = {
            'Serving': 'bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-500',
            'In Queue': 'bg-yellow-50 text-yellow-700 dark:bg-yellow-500/15 dark:text-yellow-400',
            'Canceled': 'bg-red-50 text-red-700 dark:bg-red-500/15 dark:text-red-500',
        };
        return classes[status] || '';
    }
}">
    <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-800 dark:bg-white/[0.03]">
        <div class="max-w-full overflow-x-auto custom-scrollbar">
            <table class="w-full min-w-[1102px]">
                <thead>
                    <tr class="border-b border-gray-100 dark:border-gray-800">
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                Customer
                            </p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                Queue Number
                            </p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                Action
                            </p>
                        </th>
                        <th class="px-5 py-3 text-left sm:px-6">
                            <p class="font-medium text-gray-500 text-theme-xs dark:text-gray-400">
                                Status
                            </p>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="order in orders" :key="order.id">
                        <tr class="border-b border-gray-100 dark:border-gray-800">
                            <td class="px-5 py-4 sm:px-6" colspan="1">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 overflow-hidden rounded-full">
                                        <img :src="order.user.image" :alt="order.user.name">
                                    </div>
                                    <div>
                                        <span class="block font-medium text-gray-800 text-theme-sm dark:text-white/90" x-text="order.user.name"></span>
                                        <span class="block text-gray-500 text-theme-xs dark:text-gray-400" x-text="order.user.role"></span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400" x-text="order.queueNumber"></p>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                            <x-ui.button size="sm" variant="primary">
                                Send SMS    
                                    <x-slot name="endIcon">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24">
                                            <path d="M0 0h24v24H0z" fill="none" />
                                            <path fill="currentColor" fill-rule="evenodd" d="M5.788 14.02a1 1 0 0 0 .132.031a456 456 0 0 1 .844 2.002c.503 1.202 1.01 2.44 1.121 2.796c.139.438.285.736.445.94c.083.104.178.196.29.266a1 1 0 0 0 .186.088c.32.12.612.07.795.009a1.3 1.3 0 0 0 .304-.15L9.91 20l2.826-1.762l3.265 2.502q.072.055.156.093c.392.17.772.23 1.13.182c.356-.05.639-.199.85-.368a2 2 0 0 0 .564-.728l.009-.022l.003-.008l.002-.004v-.002l.001-.001a1 1 0 0 0 .04-.133l2.98-15.025a1 1 0 0 0 .014-.146c0-.44-.166-.859-.555-1.112c-.334-.217-.705-.227-.94-.209c-.252.02-.486.082-.643.132a4 4 0 0 0-.26.094l-.011.005l-16.714 6.556l-.002.001a2 2 0 0 0-.167.069a2.5 2.5 0 0 0-.38.212c-.227.155-.75.581-.661 1.285c.07.56.454.905.689 1.071c.128.091.25.156.34.199c.04.02.126.054.163.07l.01.003zm14.138-9.152h-.002l-.026.011l-16.734 6.565l-.026.01l-.01.003a1 1 0 0 0-.09.04a1 1 0 0 0 .086.043l3.142 1.058a1 1 0 0 1 .16.076l10.377-6.075l.01-.005a2 2 0 0 1 .124-.068c.072-.037.187-.091.317-.131c.09-.028.357-.107.645-.014a.85.85 0 0 1 .588.689a.84.84 0 0 1 .003.424c-.07.275-.262.489-.437.653c-.15.14-2.096 2.016-4.015 3.868l-2.613 2.52l-.465.45l5.872 4.502a.54.54 0 0 0 .251.04a.23.23 0 0 0 .117-.052a.5.5 0 0 0 .103-.12l.002-.001l2.89-14.573a2 2 0 0 0-.267.086zm-8.461 12.394l-1.172-.898l-.284 1.805zm-2.247-2.68l1.165-1.125l2.613-2.522l.973-.938l-6.52 3.817l.035.082a339 339 0 0 1 1.22 2.92l.283-1.8a.75.75 0 0 1 .231-.435" clip-rule="evenodd" />
                                        </svg>
                                    </x-slot>
                            </x-ui.button>
                            
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-theme-xs inline-block rounded-full px-2 py-0.5 font-medium" :class="getStatusClass(order.status)" x-text="order.status"></p>
                            </td>
                            <td class="px-5 py-4 sm:px-6">
                                <p class="text-gray-500 text-theme-sm dark:text-gray-400" x-text="order.budget"></p>
                            </td>
                        </tr>
                        
                    </template>
                </tbody>
            </table>
        </div>
    </div>
</div>