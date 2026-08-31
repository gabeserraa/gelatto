<div>
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                <h2 class="text-lg font-semibold text-gray-900">Nova movimentação</h2>

                <form wire:submit="save" class="mt-4 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Tipo</label>
                        <select wire:model.live="type" class="mt-1 w-full rounded-md border-gray-300">
                            <option value="reposicao">Reposição</option>
                            <option value="retirada">Retirada/venda</option>
                            <option value="ajuste">Ajuste</option>
                        </select>
                    </div>

                    @if ($type === 'ajuste')
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Direção do ajuste</label>
                            <select wire:model="adjustment_direction" class="mt-1 w-full rounded-md border-gray-300">
                                <option value="">Selecione</option>
                                <option value="increase">Corrigir para cima</option>
                                <option value="decrease">Corrigir para baixo</option>
                            </select>
                            @error('adjustment_direction') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Quantidade (kg)</label>
                        <input type="number" step="0.01" wire:model="quantity_kg" class="mt-1 w-full rounded-md border-gray-300">
                        @error('quantity_kg') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Custo unit. (R$/kg)</label>
                            <input type="number" step="0.01" wire:model="cost_per_kg" class="mt-1 w-full rounded-md border-gray-300">
                            @error('cost_per_kg') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Receita (R$)</label>
                            <input type="number" step="0.01" wire:model="revenue" class="mt-1 w-full rounded-md border-gray-300">
                            @error('revenue') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Data do evento</label>
                        <input type="date" wire:model="occurred_at" class="mt-1 w-full rounded-md border-gray-300">
                        @error('occurred_at') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700">Observações</label>
                        <textarea wire:model="notes" rows="2" class="mt-1 w-full rounded-md border-gray-300"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" wire:click="$set('showModal', false)" class="rounded-md border px-4 py-2 text-sm text-gray-700">
                            Cancelar
                        </button>
                        <button type="submit" class="rounded-md bg-blue-600 px-4 py-2 text-sm text-white hover:bg-blue-700">
                            Registrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
