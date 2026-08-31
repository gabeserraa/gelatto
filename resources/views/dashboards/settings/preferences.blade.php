<x-app-layout>
    <x-slot name="header">Configurações</x-slot>

    <div class="flex flex-col gap-6 lg:flex-row">
        @include('dashboards.settings.partials.nav')

        <div class="flex-1 rounded-card border border-slate-200 bg-white p-6 shadow-card">
            <h2 class="font-display text-lg font-semibold text-navy-950">Preferências</h2>

            @if (session('status') === 'preferences-updated')
                <p class="mt-2 text-sm text-emerald-600">Preferências atualizadas com sucesso.</p>
            @endif

            <form method="POST" action="{{ route('dashboards.settings.preferences.update') }}" class="mt-4 space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Aparência</h3>
                    <div class="mt-2 flex items-center justify-between rounded-[10px] border border-slate-200 px-4 py-3">
                        <div>
                            <p class="text-sm font-medium text-navy-950">Modo escuro</p>
                            <p class="text-xs text-slate-500">Preferência salva — a interface em fundo escuro ainda não foi implementada nas telas.</p>
                        </div>
                        <x-toggle-switch name="dark_mode" :checked="old('dark_mode', $user->dark_mode)" />
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Regional</h3>
                    <div class="mt-2 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Moeda</label>
                            <select name="currency" class="mt-1 w-full rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                                <option value="BRL" @selected(old('currency', $user->currency) === 'BRL')>BRL — Real Brasileiro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Fuso horário</label>
                            <select name="timezone" class="mt-1 w-full rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                                <option value="America/Sao_Paulo" @selected(old('timezone', $user->timezone) === 'America/Sao_Paulo')>America/Sao_Paulo (GMT-3)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div>
                    <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-400">Notificações</h3>
                    <p class="mt-1 text-xs text-slate-500">As preferências abaixo são salvas, mas o envio de e-mail ainda não está configurado no sistema.</p>

                    <div class="mt-2 divide-y divide-slate-100 rounded-[10px] border border-slate-200">
                        <div class="flex items-center justify-between px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-navy-950">Alertas críticos de estoque</p>
                                <p class="text-xs text-slate-500">Quando um ponto atingir nível crítico</p>
                            </div>
                            <x-toggle-switch name="notify_critical_stock" :checked="old('notify_critical_stock', $user->notify_critical_stock)" />
                        </div>
                        <div class="flex items-center justify-between px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-navy-950">Alertas de reposição</p>
                                <p class="text-xs text-slate-500">Quando um ponto precisar de reposição em breve</p>
                            </div>
                            <x-toggle-switch name="notify_low_stock" :checked="old('notify_low_stock', $user->notify_low_stock)" />
                        </div>
                        <div class="flex items-center justify-between px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-navy-950">Relatório financeiro diário</p>
                                <p class="text-xs text-slate-500">Resumo diário de receita e lucro</p>
                            </div>
                            <x-toggle-switch name="notify_daily_financial_report" :checked="old('notify_daily_financial_report', $user->notify_daily_financial_report)" />
                        </div>
                        <div class="flex items-center justify-between px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-navy-950">Relatórios automáticos</p>
                                <p class="text-xs text-slate-500">Notificação quando um relatório for gerado</p>
                            </div>
                            <x-toggle-switch name="notify_report_generated" :checked="old('notify_report_generated', $user->notify_report_generated)" />
                        </div>
                    </div>
                </div>

                <button type="submit" class="rounded-[10px] bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800">Salvar preferências</button>
            </form>
        </div>
    </div>
</x-app-layout>
