<form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
    @csrf
    @method('PATCH')

    <!-- Profile Photo -->
    <div class="flex items-center space-x-6">
        <img
            src="https://ui-avatars.com/api/?name={{ Auth::user()->name }}&size=128&background=3B82F6&color=fff"
            alt="Profile photo"
            class="w-20 h-20 rounded-full"
        />
        <div>
            <x-hando-button variant="secondary" type="button">
                Change Photo
            </x-hando-button>
            <p class="mt-2 text-xs text-hando-gray-500 dark:text-hando-gray-400">JPG, GIF or PNG. Max size 2MB</p>
        </div>
    </div>

    <!-- Name Fields -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <x-hando-label for="first_name" value="First Name" :required="true" />
            <x-hando-input
                id="first_name"
                name="first_name"
                type="text"
                value="{{ old('first_name', explode(' ', Auth::user()->name)[0] ?? '') }}"
                required
            />
        </div>

        <div>
            <x-hando-label for="last_name" value="Last Name" :required="true" />
            <x-hando-input
                id="last_name"
                name="last_name"
                type="text"
                value="{{ old('last_name', explode(' ', Auth::user()->name)[1] ?? '') }}"
                required
            />
        </div>
    </div>

    <!-- Email -->
    <div>
        <x-hando-label for="email" value="Email Address" :required="true" />
        <x-hando-input
            id="email"
            name="email"
            type="email"
            value="{{ old('email', Auth::user()->email) }}"
            required
        >
            <x-slot name="icon">
                <svg class="h-5 w-5 text-hando-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </x-slot>
        </x-hando-input>
    </div>

    <!-- Phone -->
    <div>
        <x-hando-label for="phone" value="Phone Number" />
        <x-hando-input
            id="phone"
            name="phone"
            type="tel"
            placeholder="+1 (555) 000-0000"
        >
            <x-slot name="icon">
                <svg class="h-5 w-5 text-hando-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                </svg>
            </x-slot>
        </x-hando-input>
    </div>

    <!-- Bio -->
    <div>
        <x-hando-label for="bio" value="Bio" />
        <textarea
            id="bio"
            name="bio"
            rows="4"
            class="block w-full rounded-hando border-hando-border-light dark:border-hando-border-dark bg-white dark:bg-hando-gray-800 text-hando-text-light dark:text-hando-text-dark placeholder-hando-gray-400 focus:outline-none focus:ring-2 focus:ring-hando-primary focus:border-transparent transition-colors duration-200 text-sm px-3 py-2.5"
            placeholder="Tell us about yourself..."
        ></textarea>
    </div>

    <!-- Location -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <x-hando-label for="city" value="City" />
            <x-hando-input
                id="city"
                name="city"
                type="text"
                placeholder="San Francisco"
            />
        </div>

        <div>
            <x-hando-label for="country" value="Country" />
            <x-hando-input
                id="country"
                name="country"
                type="text"
                placeholder="United States"
            />
        </div>
    </div>

    <!-- Actions -->
    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-hando-border-light dark:border-hando-border-dark">
        <x-hando-button variant="secondary" type="button">
            Cancel
        </x-hando-button>
        <x-hando-button variant="primary" type="submit">
            Save Changes
        </x-hando-button>
    </div>
</form>
