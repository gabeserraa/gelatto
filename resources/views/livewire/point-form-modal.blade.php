<div>
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-lg rounded-lg bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-gray-900">
                    {{ $pointId ? 'Editar ponto' : 'Novo ponto' }}
                </h2>

                <form wire:submit="save" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nome do ponto</label>
                        <input type="text" wire:model="name" class="mt-1 w-full rounded-md border-gray-300">
                        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo</label>
                        <input type="text" wire:model="type" class="mt-1 w-full rounded-md border-gray-300" placeholder="Balada, casa de eventos, mercado...">
                        @error('type') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Endereço</label>
                        <input type="text" wire:model="address" class="mt-1 w-full rounded-md border-gray-300">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Latitude</label>
                            <input type="number" step="0.0000001" wire:model="latitude" class="mt-1 w-full rounded-md border-gray-300">
                            @error('latitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Longitude</label>
                            <input type="number" step="0.0000001" wire:model="longitude" class="mt-1 w-full rounded-md border-gray-300">
                            @error('longitude') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contato — nome</label>
                            <input type="text" wire:model="contact_name" class="mt-1 w-full rounded-md border-gray-300">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Contato — telefone</label>
                            <input type="text" wire:model="contact_phone" class="mt-1 w-full rounded-md border-gray-300">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Capacidade do freezer (kg)</label>
                            <input type="number" step="0.01" wire:model="capacity_kg" class="mt-1 w-full rounded-md border-gray-300">
                            @error('capacity_kg') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Estimativa inicial (kg/mês)</label>
                            <input type="number" step="0.01" wire:model="initial_estimate_kg" class="mt-1 w-full rounded-md border-gray-300">
                            @error('initial_estimate_kg') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select wire:model="status" class="mt-1 w-full rounded-md border-gray-300">
                            <option value="ativo">Ativo</option>
                            <option value="inativo">Inativo</option>
                            <option value="manutencao">Em manutenção</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Observações</label>
                        <textarea wire:model="notes" rows="3" class="mt-1 w-full rounded-md border-gray-300"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showModal', false)" class="rounded-md border px-4 py-2 text-sm text-gray-700">
                            Cancelar
                        </button>
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
