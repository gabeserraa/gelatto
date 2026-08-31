<?php

namespace App\Livewire;

use App\Models\PointMovement;
use Livewire\Attributes\On;
use Livewire\Component;

class MovementFormModal extends Component
{
    public bool $showModal = false;
    public ?int $pointId = null;

    public string $type = 'retirada';
    public ?float $quantity_kg = null;
    public ?string $adjustment_direction = null;
    public ?float $cost_per_kg = null;
    public ?float $revenue = null;
    public string $occurred_at = '';
    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'type' => 'required|in:reposicao,retirada,ajuste',
            'quantity_kg' => 'required|numeric|min:0.01',
            'adjustment_direction' => 'required_if:type,ajuste|nullable|in:increase,decrease',
            'cost_per_kg' => 'nullable|numeric|min:0',
            'revenue' => 'nullable|numeric|min:0',
            'occurred_at' => 'required|date',
            'notes' => 'nullable|string',
        ];
    }

    #[On('open-movement-form')]
    public function open(int $pointId): void
    {
        $this->pointId = $pointId;
        $this->type = 'retirada';
        $this->quantity_kg = null;
        $this->adjustment_direction = null;
        $this->cost_per_kg = null;
        $this->revenue = null;
        $this->occurred_at = now()->format('Y-m-d');
        $this->notes = null;
        $this->resetErrorBag();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();
        $data['point_id'] = $this->pointId;

        $data['cost'] = $this->cost_per_kg !== null
            ? round($this->cost_per_kg * $this->quantity_kg, 2)
            : null;
        unset($data['cost_per_kg']);

        if ($data['type'] !== 'ajuste') {
            $data['adjustment_direction'] = null;
        }

        PointMovement::create($data);

        $this->showModal = false;
        $this->dispatch('point-saved', pointId: $this->pointId);
        $this->dispatch('toast', type: 'success', message: 'Movimentação registrada.');
    }

    public function render()
    {
        return view('livewire.movement-form-modal');
    }
}
