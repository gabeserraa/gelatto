<x-app-layout>
    <x-slot name="header">Configurações</x-slot>

    <div class="flex flex-col gap-6 lg:flex-row">
        @include('dashboards.settings.partials.nav')

        <div class="flex-1 space-y-4">
            <div class="rounded-card border border-slate-200 bg-white p-6 shadow-card">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="rounded-card border border-slate-200 bg-white p-6 shadow-card">
                @include('profile.partials.update-password-form')
            </div>

            <div class="rounded-card border border-slate-200 bg-white p-6 shadow-card">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
