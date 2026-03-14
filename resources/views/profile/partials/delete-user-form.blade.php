<section class="space-y-6" x-data="{ open: @json($errors->userDeletion->isNotEmpty()) }" x-cloak>
    <header>
        <h2 class="text-lg font-bold text-slate-900">
            {{ __('Delete Account') }}
        </h2>

        <p class="mt-1 text-sm text-slate-600">
            {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Before deleting your account, please download any data or information that you wish to retain.') }}
        </p>
    </header>

    <button x-on:click.prevent="open = true" class="rounded-full bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700">
        {{ __('Delete Account') }}
    </button>

    <!-- Modal -->
    <div
        x-show="open"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center p-4"
        style="display: none;"
        aria-modal="true" role="dialog"
    >
        <div class="fixed inset-0 bg-black/50" x-on:click="open = false"></div>

        <div class="relative w-full max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-lg" x-transition.scale>
            <form method="post" action="{{ route('profile.destroy') }}">
                @csrf
                @method('delete')

                <h2 class="text-lg font-bold text-slate-900">
                    {{ __('Are you sure you want to delete your account?') }}
                </h2>

                <p class="mt-1 text-sm text-slate-600">
                    {{ __('Once your account is deleted, all of its resources and data will be permanently deleted. Please enter your password to confirm you would like to permanently delete your account.') }}
                </p>

                <div class="mt-6">
                    <label for="password" class="field-label">{{ __('Password') }}</label>
                    <input id="password" name="password" type="password" class="field-input" placeholder="{{ __('Password') }}" autocomplete="current-password" />
                    <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" x-on:click="open = false" class="btn-pill btn-pill-soft">{{ __('Cancel') }}</button>

                    <button type="submit" class="ms-3 rounded-full bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700">{{ __('Delete Account') }}</button>
                </div>
            </form>
        </div>
    </div>
</section>
