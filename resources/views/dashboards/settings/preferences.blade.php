<x-app-layout>
    <x-slot name="header">Configurações</x-slot>

    <div class="flex flex-col gap-6 lg:flex-row">
        @include('dashboards.settings.partials.nav')

        <div class="flex-1 rounded-lg bg-white p-6 shadow">
            <h2 class="text-lg font-semibold text-gray-900">Preferências</h2>

            @if (session('status') === 'preferences-updated')
                <p class="mt-2 text-sm text-green-600">Preferências atualizadas com sucesso.</p>
            @endif

            <form method="POST" action="{{ route('dashboards.settings.preferences.update') }}" class="mt-4 space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Aparência</h3>
                    <div class="mt-2 flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-gray-900">Modo escuro</p>
                            <p class="text-xs text-gray-500">Preferência salva — a interface em fundo escuro ainda não foi implementada nas telas.</p>
                        </div>
                        <input type="checkbox" name="dark_mode" value="1" @checked(old('dark_mode', $user->dark_mode)) class="h-5 w-9 rounded-full">
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Regional</h3>
                    <div class="mt-2 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Moeda</label>
                            <select name="currency" class="mt-1 w-full rounded-md border-gray-300">
                                <option value="BRL" @selected(old('currency', $user->currency) === 'BRL')>BRL — Real Brasileiro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Fuso horário</label>
                            <select name="timezone" class="mt-1 w-full rounded-md border-gray-300">
                                <option value="America/Sao_Paulo" @selected(old('timezone', $user->timezone) === 'America/Sao_Paulo')>America/Sao_Paulo (GMT-3)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-gray-400">Notificações</h3>
                    <p class="mt-1 text-xs text-gray-500">As preferências abaixo são salvas, mas o envio de e-mail ainda não está configurado no sistema.</p>

                    <div class="mt-2 divide-y divide-gray-200 rounded-md border border-gray-200">
                        <div class="flex items-center justify-between px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Alertas críticos de estoque</p>
                                <p class="text-xs text-gray-500">Quando um ponto atingir nível crítico</p>
                            </div>
                            <input type="checkbox" name="notify_critical_stock" value="1" @checked(old('notify_critical_stock', $user->notify_critical_stock)) class="h-5 w-9 rounded-full">
                        </div>
                        <div class="flex items-center justify-between px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Alertas de reposição</p>
                                <p class="text-xs text-gray-500">Quando um ponto precisar de reposição em breve</p>
                            </div>
                            <input type="checkbox" name="notify_low_stock" value="1" @checked(old('notify_low_stock', $user->notify_low_stock)) class="h-5 w-9 rounded-full">
                        </div>
                        <div class="flex items-center justify-between px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Relatório financeiro diário</p>
                                <p class="text-xs text-gray-500">Resumo diário de receita e lucro</p>
                            </div>
                            <input type="checkbox" name="notify_daily_financial_report" value="1" @checked(old('notify_daily_financial_report', $user->notify_daily_financial_report)) class="h-5 w-9 rounded-full">
                        </div>
                        <div class="flex items-center justify-between px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">Relatórios automáticos</p>
                                <p class="text-xs text-gray-500">Notificação quando um relatório for gerado</p>
                            </div>
                            <input type="checkbox" name="notify_report_generated" value="1" @checked(old('notify_report_generated', $user->notify_report_generated)) class="h-5 w-9 rounded-full">
                        </div>
                    </div>
                </div>

                <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">Salvar preferências</button>
            </form>
        </div>
    </div>
</x-app-layout>
