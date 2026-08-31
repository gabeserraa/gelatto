<x-app-layout>
    <x-slot name="header">{{ __('Profile') }}</x-slot>

    <div class="space-y-6">
        <div class="rounded-card border border-slate-200 bg-white p-6 shadow-card sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="rounded-card border border-slate-200 bg-white p-6 shadow-card sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="rounded-card border border-slate-200 bg-white p-6 shadow-card sm:p-8">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
