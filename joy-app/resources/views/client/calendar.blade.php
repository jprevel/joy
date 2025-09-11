<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $workspace->name }} - Content Calendar</title>
    @vite(['resources/css/app.css'])
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900">
    <div class="min-h-screen">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center py-4">
                    <div class="flex items-center space-x-4">
                        <a href="{{ route('client.access', $magicLink->token) }}" 
                           class="text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100">
                            ← Back to Dashboard
                        </a>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">Content Calendar</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <button onclick="toggleDarkMode()" class="p-2 rounded-lg bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors">
                            <span class="dark:hidden">🌙</span>
                            <span class="hidden dark:inline">☀️</span>
                        </button>
                        <div class="text-right">
                            <p class="text-sm text-gray-900 dark:text-gray-100">{{ $workspace->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $magicLink->name }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Calendar View -->
        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <div class="px-4 py-6 sm:px-0" x-data="calendar()">
                <!-- Calendar Header -->
                <div class="flex items-center justify-between mb-6">
                    <div class="flex items-center space-x-4">
                        <button @click="previousMonth()" class="brand-button">
                            ‹ Previous
                        </button>
                        <h2 class="text-xl font-semibold text-gray-900 dark:text-gray-100" x-text="currentMonthYear"></h2>
                        <button @click="nextMonth()" class="brand-button">
                            Next ›
                        </button>
                    </div>
                    <button @click="goToToday()" class="brand-button">
                        Today
                    </button>
                </div>

                <!-- Calendar Grid -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                    <!-- Day Headers -->
                    <div class="grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-700">
                        <template x-for="day in dayNames">
                            <div class="bg-gray-50 dark:bg-gray-600 py-3 text-center">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300" x-text="day"></span>
                            </div>
                        </template>
                    </div>

                    <!-- Calendar Days -->
                    <div class="grid grid-cols-7 gap-px bg-gray-200 dark:bg-gray-700" style="min-height: 500px;">
                        <template x-for="(week, weekIndex) in calendarData" :key="weekIndex">
                            <template x-for="(day, dayIndex) in week" :key="`${weekIndex}-${dayIndex}`">
                                <div :class="[
                                    'bg-white dark:bg-gray-800 p-2 min-h-[100px] relative',
                                    !day.isCurrentMonth ? 'bg-gray-50 dark:bg-gray-700 text-gray-400 dark:text-gray-500' : '',
                                    day.isToday ? 'bg-blue-50 dark:bg-blue-900' : ''
                                ]">
                                    <div :class="[
                                        'text-sm font-medium mb-1',
                                        day.isToday ? 'text-blue-600 dark:text-blue-300' : '',
                                        !day.isCurrentMonth ? 'text-gray-400 dark:text-gray-500' : 'text-gray-900 dark:text-gray-100'
                                    ]" x-text="day.date.getDate()"></div>
                                    
                                    <div class="space-y-1">
                                        <template x-for="event in day.events" :key="event.id">
                                            <div :class="[
                                                'text-xs p-1 rounded cursor-pointer',
                                                getPlatformColor(event.platform)
                                            ]" @click="openVariantModal(event)">
                                                <div class="flex items-center space-x-1">
                                                    <span x-text="getPlatformIcon(event.platform)"></span>
                                                    <span class="truncate" x-text="event.title"></span>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                        </template>
                    </div>
                </div>

                <!-- Event Modal -->
                <div x-show="showModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" 
                     style="display: none;">
                    <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                        <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" 
                             @click="closeModal()"></div>

                        <div class="inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white dark:bg-gray-800 shadow-xl rounded-lg">
                            <template x-if="selectedEvent">
                                <div>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4" x-text="selectedEvent.title"></h3>
                                    
                                    <div class="space-y-4">
                                        <div class="flex items-center space-x-2">
                                            <span x-text="getPlatformIcon(selectedEvent.platform)"></span>
                                            <span class="text-sm text-gray-600 dark:text-gray-300" x-text="selectedEvent.platform"></span>
                                            <span :class="[
                                                'inline-flex px-2 py-1 text-xs font-medium rounded-full',
                                                getStatusColor(selectedEvent.status)
                                            ]" x-text="selectedEvent.status"></span>
                                        </div>
                                        
                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-2">Content:</p>
                                            <p class="text-sm text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-700 p-3 rounded" x-text="selectedEvent.copy"></p>
                                        </div>
                                        
                                        <div>
                                            <p class="text-sm text-gray-600 dark:text-gray-300 mb-1">Scheduled for:</p>
                                            <p class="text-sm text-gray-900 dark:text-gray-100" x-text="formatDateTime(selectedEvent.scheduled_at)"></p>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-6 flex space-x-3">
                                        <a :href="`{{ route('client.variant', [$magicLink->token, '']) }}/${selectedEvent.id}`"
                                           class="brand-button">
                                            View Details
                                        </a>
                                        <button @click="closeModal()" 
                                                class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 rounded-md hover:bg-gray-200 dark:hover:bg-gray-500">
                                            Close
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Dark mode toggle functionality
        function toggleDarkMode() {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            
            if (isDark) {
                html.classList.remove('dark');
                localStorage.setItem('darkMode', 'false');
            } else {
                html.classList.add('dark');
                localStorage.setItem('darkMode', 'true');
            }
        }

        // Initialize dark mode based on user preference or system preference
        function initializeDarkMode() {
            const savedMode = localStorage.getItem('darkMode');
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            
            if (savedMode === 'true' || (savedMode === null && systemPrefersDark)) {
                document.documentElement.classList.add('dark');
            }
        }

        // Initialize dark mode when page loads
        initializeDarkMode();
    </script>

    <script>
        function calendar() {
            return {
                currentDate: new Date(),
                calendarData: [],
                dayNames: ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
                events: @json($variants->keyBy('id')->map(function($variant) {
                    return [
                        'id' => $variant['id'],
                        'title' => $variant['concept']['title'],
                        'platform' => $variant['platform'],
                        'status' => $variant['status'],
                        'copy' => $variant['copy'],
                        'scheduled_at' => $variant['scheduled_at'],
                    ];
                })),
                showModal: false,
                selectedEvent: null,

                init() {
                    this.generateCalendar();
                },

                get currentMonthYear() {
                    return this.currentDate.toLocaleDateString('en-US', { 
                        month: 'long', 
                        year: 'numeric' 
                    });
                },

                generateCalendar() {
                    const year = this.currentDate.getFullYear();
                    const month = this.currentDate.getMonth();
                    
                    const firstDay = new Date(year, month, 1);
                    const lastDay = new Date(year, month + 1, 0);
                    const startDate = new Date(firstDay);
                    startDate.setDate(startDate.getDate() - firstDay.getDay());

                    this.calendarData = [];
                    let currentWeek = [];

                    for (let i = 0; i < 42; i++) {
                        const date = new Date(startDate);
                        date.setDate(startDate.getDate() + i);

                        const dayEvents = Object.values(this.events).filter(event => {
                            const eventDate = new Date(event.scheduled_at);
                            return eventDate.toDateString() === date.toDateString();
                        });

                        currentWeek.push({
                            date: new Date(date),
                            isCurrentMonth: date.getMonth() === month,
                            isToday: date.toDateString() === new Date().toDateString(),
                            events: dayEvents
                        });

                        if (currentWeek.length === 7) {
                            this.calendarData.push(currentWeek);
                            currentWeek = [];
                        }
                    }
                },

                previousMonth() {
                    this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                    this.generateCalendar();
                },

                nextMonth() {
                    this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                    this.generateCalendar();
                },

                goToToday() {
                    this.currentDate = new Date();
                    this.generateCalendar();
                },

                getPlatformIcon(platform) {
                    const icons = {
                        'facebook': '📘',
                        'instagram': '📷',
                        'linkedin': '💼',
                        'blog': '📝'
                    };
                    return icons[platform] || '📄';
                },

                getPlatformColor(platform) {
                    const colors = {
                        'facebook': 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
                        'instagram': 'bg-pink-100 dark:bg-pink-900 text-pink-800 dark:text-pink-200',
                        'linkedin': 'bg-blue-200 dark:bg-blue-800 text-blue-900 dark:text-blue-200',
                        'blog': 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200'
                    };
                    return colors[platform] || 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200';
                },

                getStatusColor(status) {
                    const colors = {
                        'Draft': 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200',
                        'In Review': 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
                        'Approved': 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                        'Scheduled': 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200'
                    };
                    return colors[status] || 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200';
                },

                openVariantModal(event) {
                    this.selectedEvent = event;
                    this.showModal = true;
                },

                closeModal() {
                    this.showModal = false;
                    this.selectedEvent = null;
                },

                formatDateTime(dateString) {
                    return new Date(dateString).toLocaleDateString('en-US', {
                        weekday: 'long',
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        hour: 'numeric',
                        minute: '2-digit'
                    });
                }
            }
        }
    </script>
</body>
</html>