@props(['active' => 'about'])

<div class="border-b border-hando-border-light dark:border-hando-border-dark">
    <nav class="-mb-px flex space-x-8" aria-label="Tabs">
        <a
            href="?tab=about"
            class="
                @if($active === 'about')
                    border-hando-primary text-hando-primary
                @else
                    border-transparent text-hando-gray-500 dark:text-hando-gray-400 hover:text-hando-gray-700 dark:hover:text-hando-gray-300 hover:border-hando-gray-300 dark:hover:border-hando-gray-600
                @endif
                whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-150
            "
        >
            About
        </a>

        <a
            href="?tab=work"
            class="
                @if($active === 'work')
                    border-hando-primary text-hando-primary
                @else
                    border-transparent text-hando-gray-500 dark:text-hando-gray-400 hover:text-hando-gray-700 dark:hover:text-hando-gray-300 hover:border-hando-gray-300 dark:hover:border-hando-gray-600
                @endif
                whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-150
            "
        >
            Work Experience
        </a>

        <a
            href="?tab=education"
            class="
                @if($active === 'education')
                    border-hando-primary text-hando-primary
                @else
                    border-transparent text-hando-gray-500 dark:text-hando-gray-400 hover:text-hando-gray-700 dark:hover:text-hando-gray-300 hover:border-hando-gray-300 dark:hover:border-hando-gray-600
                @endif
                whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-150
            "
        >
            Education
        </a>

        <a
            href="?tab=settings"
            class="
                @if($active === 'settings')
                    border-hando-primary text-hando-primary
                @else
                    border-transparent text-hando-gray-500 dark:text-hando-gray-400 hover:text-hando-gray-700 dark:hover:text-hando-gray-300 hover:border-hando-gray-300 dark:hover:border-hando-gray-600
                @endif
                whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors duration-150
            "
        >
            Settings
        </a>
    </nav>
</div>
