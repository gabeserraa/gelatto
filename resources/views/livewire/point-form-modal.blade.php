<div>
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-lg rounded-card bg-white p-6 shadow-xl">
                <h2 class="font-display text-lg font-semibold text-navy-950">
                    {{ $pointId ? 'Editar ponto' : 'Novo ponto' }}
                </h2>

                <form wire:submit="save" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Nome do ponto</label>
                        <input type="text" wire:model="name" class="mt-1 w-full rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Tipo</label>
                        <select wire:model="type" class="mt-1 w-full rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                            @foreach (config('dashboards.point_types') as $pointType)
                                <option value="{{ $pointType }}">{{ $pointType }}</option>
                            @endforeach
                        </select>
                        @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Endereço</label>
                            <input type="text" wire:model="address" class="mt-1 w-full rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Região</label>
                            <input type="text" wire:model="region" class="mt-1 w-full rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500" placeholder="Centro, Zona Sul...">
                            @error('region') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Latitude</label>
                            <input type="number" step="0.0000001" wire:model="latitude" class="mt-1 w-full rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                            @error('latitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Longitude</label>
                            <input type="number" step="0.0000001" wire:model="longitude" class="mt-1 w-full rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                            @error('longitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Contato — nome</label>
                            <input type="text" wire:model="contact_name" class="mt-1 w-full rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Contato — telefone</label>
                            <input type="text" wire:model="contact_phone" class="mt-1 w-full rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Capacidade do freezer (kg)</label>
                            <input type="number" step="0.01" wire:model="capacity_kg" class="mt-1 w-full rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                            @error('capacity_kg') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700">Estimativa inicial (kg/mês)</label>
                            <input type="number" step="0.01" wire:model="initial_estimate_kg" class="mt-1 w-full rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                            @error('initial_estimate_kg') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Status</label>
                        <select wire:model="status" class="mt-1 w-full rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500">
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                            <option value="manutencao">Em manutenção</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Observações</label>
                        <textarea wire:model="notes" rows="3" class="mt-1 w-full rounded-[10px] border-slate-300 text-sm focus:border-cyan-500 focus:ring-cyan-500"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showModal', false)" class="rounded-[10px] border border-slate-300 px-4 py-2 text-[13px] font-semibold text-slate-700 hover:bg-slate-50">
                            Cancelar
                        </button>
                        <button type="submit" class="rounded-[10px] bg-navy-950 px-4 py-2 text-[13px] font-semibold text-white hover:bg-navy-800">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
