<x-layouts.crm>
    <x-slot name="header">
        <div>
            <h1 class="text-2xl font-bold text-hando-text-light dark:text-hando-text-dark">Profile Settings</h1>
            <p class="mt-1 text-sm text-hando-gray-500 dark:text-hando-gray-400">Manage your profile information and settings</p>
        </div>
    </x-slot>

    @php
        $activeTab = request()->query('tab', 'about');
    @endphp

    <div class="max-w-7xl mx-auto">
        <!-- Tabs Navigation -->
        <x-crm.card :padding="false">
            <div class="px-6">
                <x-crm.tabs :active="$activeTab" />
            </div>

            <!-- Tab Content -->
            <div class="p-6">
                @if($activeTab === 'about')
                    @include('crm.profile.partials.about')
                @elseif($activeTab === 'work')
                    @include('crm.profile.partials.work-experience')
                @elseif($activeTab === 'education')
                    @include('crm.profile.partials.education')
                @elseif($activeTab === 'settings')
                    @include('crm.profile.partials.settings')
                @endif
            </div>
        </x-crm.card>
    </div>
</x-layouts.crm>
