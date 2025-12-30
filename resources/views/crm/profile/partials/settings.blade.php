<div class="space-y-8">
    <!-- Account Settings -->
    <div>
        <h3 class="text-lg font-semibold text-hando-text-light dark:text-hando-text-dark mb-4">Account Settings</h3>

        <div class="space-y-6">
            <!-- Email Notifications -->
            <div class="flex items-center justify-between py-4 border-b border-hando-border-light dark:border-hando-border-dark">
                <div>
                    <h4 class="text-sm font-medium text-hando-text-light dark:text-hando-text-dark">Email Notifications</h4>
                    <p class="text-sm text-hando-gray-500 dark:text-hando-gray-400 mt-1">Receive email about your account activity</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer" checked>
                    <div class="w-11 h-6 bg-hando-gray-300 dark:bg-hando-gray-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-hando-primary rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-hando-primary"></div>
                </label>
            </div>

            <!-- Push Notifications -->
            <div class="flex items-center justify-between py-4 border-b border-hando-border-light dark:border-hando-border-dark">
                <div>
                    <h4 class="text-sm font-medium text-hando-text-light dark:text-hando-text-dark">Push Notifications</h4>
                    <p class="text-sm text-hando-gray-500 dark:text-hando-gray-400 mt-1">Receive push notifications on your devices</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer">
                    <div class="w-11 h-6 bg-hando-gray-300 dark:bg-hando-gray-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-hando-primary rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-hando-primary"></div>
                </label>
            </div>

            <!-- Two-Factor Authentication -->
            <div class="flex items-center justify-between py-4 border-b border-hando-border-light dark:border-hando-border-dark">
                <div>
                    <h4 class="text-sm font-medium text-hando-text-light dark:text-hando-text-dark">Two-Factor Authentication</h4>
                    <p class="text-sm text-hando-gray-500 dark:text-hando-gray-400 mt-1">Add an extra layer of security to your account</p>
                </div>
                <x-hando-button variant="secondary" size="sm">
                    Enable
                </x-hando-button>
            </div>
        </div>
    </div>

    <!-- Privacy Settings -->
    <div>
        <h3 class="text-lg font-semibold text-hando-text-light dark:text-hando-text-dark mb-4">Privacy</h3>

        <div class="space-y-6">
            <!-- Profile Visibility -->
            <div class="py-4 border-b border-hando-border-light dark:border-hando-border-dark">
                <h4 class="text-sm font-medium text-hando-text-light dark:text-hando-text-dark mb-3">Profile Visibility</h4>
                <div class="space-y-3">
                    <label class="flex items-center">
                        <input type="radio" name="visibility" value="public" class="w-4 h-4 text-hando-primary border-hando-border-light focus:ring-hando-primary" checked>
                        <span class="ml-3 text-sm text-hando-text-light dark:text-hando-text-dark">Public - Anyone can see your profile</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="visibility" value="private" class="w-4 h-4 text-hando-primary border-hando-border-light focus:ring-hando-primary">
                        <span class="ml-3 text-sm text-hando-text-light dark:text-hando-text-dark">Private - Only you can see your profile</span>
                    </label>
                    <label class="flex items-center">
                        <input type="radio" name="visibility" value="friends" class="w-4 h-4 text-hando-primary border-hando-border-light focus:ring-hando-primary">
                        <span class="ml-3 text-sm text-hando-text-light dark:text-hando-text-dark">Friends - Only friends can see your profile</span>
                    </label>
                </div>
            </div>

            <!-- Show Activity Status -->
            <div class="flex items-center justify-between py-4 border-b border-hando-border-light dark:border-hando-border-dark">
                <div>
                    <h4 class="text-sm font-medium text-hando-text-light dark:text-hando-text-dark">Show Activity Status</h4>
                    <p class="text-sm text-hando-gray-500 dark:text-hando-gray-400 mt-1">Let others see when you're online</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer" checked>
                    <div class="w-11 h-6 bg-hando-gray-300 dark:bg-hando-gray-600 peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-hando-primary rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-hando-primary"></div>
                </label>
            </div>
        </div>
    </div>

    <!-- Password Change -->
    <div>
        <h3 class="text-lg font-semibold text-hando-text-light dark:text-hando-text-dark mb-4">Change Password</h3>

        <form method="POST" action="{{ route('password.update') }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <x-hando-label for="current_password" value="Current Password" :required="true" />
                <x-hando-input
                    id="current_password"
                    name="current_password"
                    type="password"
                    required
                />
            </div>

            <div>
                <x-hando-label for="password" value="New Password" :required="true" />
                <x-hando-input
                    id="password"
                    name="password"
                    type="password"
                    required
                />
            </div>

            <div>
                <x-hando-label for="password_confirmation" value="Confirm New Password" :required="true" />
                <x-hando-input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                />
            </div>

            <x-hando-button variant="primary" type="submit">
                Update Password
            </x-hando-button>
        </form>
    </div>

    <!-- Danger Zone -->
    <div class="pt-6 border-t-2 border-hando-danger/20">
        <h3 class="text-lg font-semibold text-hando-danger mb-4">Danger Zone</h3>

        <div class="border border-hando-danger/30 rounded-hando p-6 bg-red-50/50 dark:bg-red-900/10">
            <div class="flex items-start justify-between">
                <div>
                    <h4 class="text-sm font-semibold text-hando-text-light dark:text-hando-text-dark">Delete Account</h4>
                    <p class="text-sm text-hando-gray-600 dark:text-hando-gray-400 mt-2">Once you delete your account, there is no going back. Please be certain.</p>
                </div>
                <x-hando-button variant="danger" size="sm">
                    Delete Account
                </x-hando-button>
            </div>
        </div>
    </div>

    <!-- Save Changes Button -->
    <div class="flex items-center justify-end space-x-3 pt-4 border-t border-hando-border-light dark:border-hando-border-dark">
        <x-hando-button variant="secondary">
            Cancel
        </x-hando-button>
        <x-hando-button variant="primary">
            Save Settings
        </x-hando-button>
    </div>
</div>
