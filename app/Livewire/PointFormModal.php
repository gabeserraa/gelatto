<?php

namespace App\Livewire;

use App\Models\Point;
use Livewire\Attributes\On;
use Livewire\Component;

class PointFormModal extends Component
{
    public bool $showModal = false;
    public ?int $pointId = null;

    public string $name = '';
    public string $type = 'Balada';
    public ?string $address = null;
    public ?string $region = null;
    public ?float $latitude = null;
    public ?float $longitude = null;
    public ?string $contact_name = null;
    public ?string $contact_phone = null;
    public ?float $capacity_kg = null;
    public ?float $initial_estimate_kg = null;
    public string $status = 'ativo';
    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:'.implode(',', config('dashboards.point_types')),
            'address' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'contact_name' => 'nullable|string|max:255',
            'contact_phone' => 'nullable|string|max:30',
            'capacity_kg' => 'required|numeric|min:0.01',
            'initial_estimate_kg' => 'nullable|numeric|min:0',
            'status' => 'required|in:ativo,inativo,manutencao',
            'notes' => 'nullable|string',
        ];
    }

    #[On('open-point-form')]
    public function open(?int $pointId = null): void
    {
        $this->reset([
            'name', 'type', 'address', 'region', 'latitude', 'longitude',
            'contact_name', 'contact_phone', 'capacity_kg',
            'initial_estimate_kg', 'status', 'notes',
        ]);
        $this->resetErrorBag();

        $this->pointId = $pointId;
        $this->type = 'Balada';
        $this->status = 'ativo';

        if ($pointId) {
            $point = Point::findOrFail($pointId);
            $this->name = $point->name;
            $this->type = $point->type;
            $this->address = $point->address;
            $this->region = $point->region;
            $this->latitude = $point->latitude !== null ? (float) $point->latitude : null;
            $this->longitude = $point->longitude !== null ? (float) $point->longitude : null;
            $this->contact_name = $point->contact_name;
            $this->contact_phone = $point->contact_phone;
            $this->capacity_kg = (float) $point->capacity_kg;
            $this->initial_estimate_kg = $point->initial_estimate_kg !== null ? (float) $point->initial_estimate_kg : null;
            $this->status = $point->status;
            $this->notes = $point->notes;
        }

        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate();

        $point = Point::updateOrCreate(['id' => $this->pointId], $data);

        $this->showModal = false;
        $this->dispatch('point-saved', pointId: $point->id);
        $this->dispatch('toast', type: 'success', message: 'Ponto salvo com sucesso.');
    }

    public function render()
    {
        return view('livewire.point-form-modal');
    }
}
