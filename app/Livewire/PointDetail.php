<?php

namespace App\Livewire;

use App\Models\Point;
use App\Services\PointStockService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class PointDetail extends Component
{
    use WithPagination;

    public ?int $pointId = null;
    public bool $showDrawer = false;

    #[On('open-point-detail')]
    public function open(int $pointId): void
    {
        $this->pointId = $pointId;
        $this->showDrawer = true;
        $this->resetPage();
    }

    #[On('point-saved')]
    public function refresh(): void
    {
        // Livewire já re-renderiza o componente; nada a fazer além de existir
        // como listener pra manter o drawer sincronizado após salvar.
    }

    public function render(PointStockService $stockService)
    {
        $point = $this->pointId ? Point::with('movements')->find($this->pointId) : null;

        $movements = $point
            ? $point->movements()->orderByDesc('occurred_at')->paginate(10)
            : null;

        return view('livewire.point-detail', [
            'point' => $point,
            'movements' => $movements,
            'currentStock' => $point ? $stockService->currentStock($point) : 0.0,
            'stockPercentage' => $point ? $stockService->stockPercentage($point) : 0.0,
            'monthlyAverage' => $point ? $stockService->monthlyAverageWithdrawal($point) : 0.0,
        ]);
    }
}
