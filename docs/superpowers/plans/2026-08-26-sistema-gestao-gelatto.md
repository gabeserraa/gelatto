# Sistema de Gestão Gelatto ICE CO. Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Construir a base modular de dashboards do sistema de gestão da Gelatto ICE CO. (auth, layout, registro dinâmico de dashboards) e implementar completos o Dashboard de Pontos de Freezer (CRUD + histórico + cálculos automáticos) e o Dashboard de Visão Executiva (KPIs consolidados).

**Architecture:** Laravel 10 (Blade + Breeze para auth) com Livewire para toda edição inline/modal. Um registro central (`config/dashboards.php`) alimenta a sidebar e o roteamento; cada dashboard é um Controller + view isolados. Cálculos de estoque/consumo/lucro vivem em services dedicados (`PointStockService`, `OverviewKpiService`), não em accessors espalhados pelo model, e nunca em colunas persistidas de estoque — sempre derivados do histórico de movimentações.

**Tech Stack:** Laravel 10 / PHP 8.3, Breeze (stack Blade), Livewire 3, Tailwind CSS (via Breeze), Chart.js, MySQL (dev), SQLite in-memory (testes), PHPUnit.

**Spec:** `docs/superpowers/specs/2026-08-26-sistema-gestao-gelatto-design.md`

## Global Constraints

- Nomes de tabelas e colunas em inglês; toda a interface (labels, textos, mensagens) em português do Brasil.
- `quantity_kg` em `point_movements` é sempre positivo; o sinal do efeito no estoque vem do `type` (e de `adjustment_direction` quando `type = 'ajuste'`).
- Estoque atual nunca é uma coluna digitada à mão — sempre derivado do histórico de `point_movements`.
- Um único usuário administrador (sem sistema de permissões/papéis, sem registro público).
- Edição de pontos e lançamento de movimentações acontece via Livewire (modal/inline), sem navegar para página de edição separada.
- Adicionar um dashboard novo no futuro deve exigir só: controller em `app/Http/Controllers/Dashboards/`, view em `resources/views/dashboards/{nome}/`, rota no grupo `/dashboards`, entrada em `config/dashboards.php`.
- Sem integração de pagamento, sem app mobile, sem os dashboards "fora de escopo" (Financeiro, Reposição/Logística, Mapa, Clientes) além de registrá-los comentados no config.

---

## File Structure

```
config/dashboards.php                                   — registro central dos dashboards + parâmetros de cálculo
routes/web.php                                           — grupo /dashboards (auth)
app/Providers/RouteServiceProvider.php                   — HOME aponta pra dashboards.points.index

app/Models/Point.php
app/Models/PointMovement.php
app/Services/PointStockService.php
app/Services/OverviewKpiService.php

database/migrations/*_create_points_table.php
database/migrations/*_create_point_movements_table.php
database/factories/PointFactory.php
database/factories/PointMovementFactory.php
database/seeders/DatabaseSeeder.php
database/seeders/PointSeeder.php

app/Livewire/PointFormModal.php            + resources/views/livewire/point-form-modal.blade.php
app/Livewire/MovementFormModal.php         + resources/views/livewire/movement-form-modal.blade.php
app/Livewire/PointDetail.php               + resources/views/livewire/point-detail.blade.php

app/Http/Controllers/Dashboards/PointsDashboardController.php    + resources/views/dashboards/points/index.blade.php
app/Http/Controllers/Dashboards/OverviewDashboardController.php  + resources/views/dashboards/overview/index.blade.php

resources/views/layouts/app.blade.php                     — layout com sidebar (sobrescreve o gerado pelo Breeze)
resources/views/layouts/partials/sidebar.blade.php
resources/views/components/toast.blade.php
resources/views/components/dashboard/icon.blade.php
resources/views/components/dashboard/kpi-card.blade.php
resources/views/components/dashboard/data-table.blade.php
resources/views/components/dashboard/chart-card.blade.php
resources/views/components/dashboard/status-badge.blade.php

tests/Feature/PointStockServiceTest.php
tests/Feature/MovementFormModalTest.php
tests/Feature/PointFormModalTest.php
tests/Feature/PointsDashboardTest.php
tests/Feature/OverviewKpiServiceTest.php
tests/Feature/OverviewDashboardTest.php
```

---

### Task 1: Autenticação (Breeze) + ambiente de testes + usuário admin

**Files:**
- Modify: `composer.json` (via `composer require`)
- Create: arquivos gerados por `breeze:install blade` (`routes/auth.php`, `resources/views/auth/*`, `app/Http/Controllers/Auth/*`, `app/View/Components/AppLayout.php`, `app/View/Components/GuestLayout.php`, `resources/views/layouts/app.blade.php`, `resources/views/layouts/guest.blade.php`, `resources/views/dashboard.blade.php`, `tests/Feature/Auth/*`)
- Modify: `routes/auth.php` — remove rotas de registro
- Modify: `phpunit.xml` — habilita SQLite em memória pros testes
- Modify: `database/seeders/DatabaseSeeder.php` — cria usuário admin

**Interfaces:**
- Produces: rota nomeada `login`, model `App\Models\User` (já existente, sem alteração de schema), ambiente de teste isolado do MySQL de desenvolvimento.

- [ ] **Step 1: Instalar Breeze**

```bash
composer require laravel/breeze --dev
php artisan breeze:install blade
```

Se o comando perguntar sobre dark mode ou framework de testes, responda "no" / mantenha PHPUnit — não afeta este plano.

- [ ] **Step 2: Instalar dependências de front-end e buildar**

```bash
npm install
npm run build
```

- [ ] **Step 3: Remover rotas de registro público**

Em `routes/auth.php`, remova o bloco de rotas de registro (mantendo login, logout, reset de senha):

```php
// Remover estas linhas:
// Route::get('register', [RegisteredUserController::class, 'create'])
//     ->middleware('guest')
//     ->name('register');
// Route::post('register', [RegisteredUserController::class, 'store'])
//     ->middleware('guest');
```

- [ ] **Step 4: Habilitar SQLite em memória para os testes**

Em `phpunit.xml`, descomente e ajuste as linhas de `DB_CONNECTION`/`DB_DATABASE`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

- [ ] **Step 5: Criar usuário admin no seeder**

Substitua o conteúdo de `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Administrador',
            'email' => 'admin@gelatto.com.br',
            'password' => bcrypt('password'),
        ]);
    }
}
```

- [ ] **Step 6: Rodar migrations e seeder no banco de desenvolvimento**

```bash
php artisan migrate
php artisan db:seed
```

- [ ] **Step 7: Rodar os testes de autenticação gerados pelo Breeze**

Run: `php artisan test --filter=Auth`
Expected: PASS (usa SQLite em memória, não toca no MySQL de dev)

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat: auth via Breeze, ambiente de testes SQLite e usuario admin"
```

---

### Task 2: Livewire + registro dinâmico de dashboards + layout com sidebar

**Files:**
- Modify: `composer.json` (via `composer require livewire/livewire`)
- Create: `config/dashboards.php`
- Create: `resources/views/components/dashboard/icon.blade.php`
- Create: `resources/views/layouts/partials/sidebar.blade.php`
- Create: `resources/views/components/toast.blade.php`
- Modify: `resources/views/layouts/app.blade.php` — layout com sidebar
- Modify: `app/Providers/RouteServiceProvider.php` — `HOME` aponta pra `dashboards.points.index`
- Create: `routes/web.php` — grupo `/dashboards`
- Test: `tests/Feature/DashboardRegistryTest.php`

**Interfaces:**
- Consumes: rota `login` (Task 1).
- Produces: `config('dashboards.items')` (array de `['key','name','icon','route','order']`), `config('dashboards.stock_window_months')`, `config('dashboards.low_stock_threshold_percent')`, rota nomeada `dashboards.points.index` (placeholder até Task 10), evento JS `toast` (`{type, message}`) disparável via `$this->dispatch('toast', type: ..., message: ...)` em qualquer componente Livewire.

- [ ] **Step 1: Instalar Livewire**

```bash
composer require livewire/livewire
```

- [ ] **Step 2: Criar o registro central de dashboards**

Create `config/dashboards.php`:

```php
<?php

return [
    'stock_window_months' => env('DASHBOARD_STOCK_WINDOW_MONTHS', 3),
    'low_stock_threshold_percent' => env('DASHBOARD_LOW_STOCK_THRESHOLD_PERCENT', 20),

    'items' => [
        [
            'key' => 'points',
            'name' => 'Pontos de Freezer',
            'icon' => 'cube',
            'route' => 'dashboards.points.index',
            'order' => 1,
        ],
        [
            'key' => 'overview',
            'name' => 'Visão Executiva',
            'icon' => 'chart-bar',
            'route' => 'dashboards.overview.index',
            'order' => 2,
        ],
        // Fora de escopo — registrar aqui quando forem implementados:
        // 'financeiro'  — fluxo de caixa, custos fixos x variáveis
        // 'reposicao'   — ranking de urgência de reposição, rota semanal
        // 'mapa'        — pontos no mapa (Leaflet.js + OpenStreetMap)
        // 'clientes'    — dados de contato/relacionamento por parceiro
    ],
];
```

- [ ] **Step 3: Criar componente de ícone**

Create `resources/views/components/dashboard/icon.blade.php`:

```blade
@props(['name'])

<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" {{ $attributes->merge(['class' => 'h-5 w-5']) }}>
    @switch($name)
        @case('cube')
            <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
            @break
        @case('chart-bar')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5h3v6H3v-6zm6.75-4.5h3v10.5h-3V9zm6.75-3h3v13.5h-3V6z" />
            @break
        @case('menu')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
            @break
        @default
            <circle cx="12" cy="12" r="8" stroke-linecap="round" />
    @endswitch
</svg>
```

- [ ] **Step 4: Criar a sidebar**

Create `resources/views/layouts/partials/sidebar.blade.php`:

```blade
<aside
    x-show="sidebarOpen"
    x-transition
    class="fixed inset-y-0 left-0 z-40 w-64 -translate-x-full transform bg-gray-900 text-gray-200 transition-transform duration-200 ease-in-out md:relative md:translate-x-0"
    :class="{ 'translate-x-0': sidebarOpen }"
>
    <div class="px-4 py-5 text-lg font-bold text-white">Gelatto ICE CO.</div>

    <nav class="mt-2 space-y-1 px-2">
        @foreach (collect(config('dashboards.items'))->sortBy('order') as $item)
            <a
                href="{{ route($item['route']) }}"
                class="flex items-center rounded-md px-3 py-2 text-sm font-medium {{ request()->routeIs($item['route']) ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}"
            >
                <x-dashboard.icon :name="$item['icon']" class="mr-3 h-5 w-5" />
                {{ $item['name'] }}
            </a>
        @endforeach
    </nav>
</aside>
```

- [ ] **Step 5: Criar componente de toast**

Create `resources/views/components/toast.blade.php`:

```blade
<div
    x-data="{ show: false, type: 'success', message: '' }"
    x-on:toast.window="show = true; type = $event.detail.type; message = $event.detail.message; setTimeout(() => show = false, 4000)"
    x-show="show"
    x-transition
    x-cloak
    class="fixed bottom-4 right-4 z-50 rounded-lg px-4 py-3 text-sm text-white shadow-lg"
    :class="type === 'success' ? 'bg-green-600' : 'bg-red-600'"
>
    <span x-text="message"></span>
</div>
```

- [ ] **Step 6: Sobrescrever o layout com sidebar**

Replace `resources/views/layouts/app.blade.php`:

```blade
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Gelatto ICE CO.') }} - {{ $header ?? '' }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="bg-gray-100 antialiased" x-data="{ sidebarOpen: false }">
    <div class="flex min-h-screen">
        @include('layouts.partials.sidebar')

        <div class="flex-1">
            <header class="bg-white shadow">
                <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                    <button class="md:hidden" @click="sidebarOpen = !sidebarOpen" aria-label="Abrir menu">
                        <x-dashboard.icon name="menu" class="h-6 w-6 text-gray-700" />
                    </button>

                    <h1 class="text-lg font-semibold text-gray-900">{{ $header ?? config('app.name') }}</h1>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Sair</button>
                    </form>
                </div>
            </header>

            <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-toast />

    @livewireScripts
</body>
</html>
```

- [ ] **Step 7: Apontar HOME e criar o grupo de rotas de dashboards**

Em `app/Providers/RouteServiceProvider.php`, altere a constante:

```php
public const HOME = '/dashboards/pontos';
```

Replace `routes/web.php`:

```php
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboards.points.index');
});

Route::middleware('auth')->prefix('dashboards')->name('dashboards.')->group(function () {
    // As rotas dos dashboards individuais são adicionadas nas Tasks 10 e 12.
});

require __DIR__.'/auth.php';
```

- [ ] **Step 8: Escrever teste do registro de dashboards**

Create `tests/Feature/DashboardRegistryTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardRegistryTest extends TestCase
{
    public function test_dashboard_items_have_required_keys(): void
    {
        $items = config('dashboards.items');

        $this->assertNotEmpty($items);

        foreach ($items as $item) {
            $this->assertArrayHasKey('key', $item);
            $this->assertArrayHasKey('name', $item);
            $this->assertArrayHasKey('icon', $item);
            $this->assertArrayHasKey('route', $item);
            $this->assertArrayHasKey('order', $item);
        }
    }
}
```

- [ ] **Step 9: Rodar o teste**

Run: `php artisan test --filter=DashboardRegistryTest`
Expected: PASS

- [ ] **Step 10: Commit**

```bash
git add -A
git commit -m "feat: registro dinamico de dashboards, layout com sidebar e Livewire"
```

> Nota: as rotas `dashboards.points.index` e `dashboards.overview.index` referenciadas pela sidebar só existem de fato a partir das Tasks 10 e 12 — até lá, visitar `/dashboards/pontos` ou `/dashboards/geral` resulta em 404, o que é esperado nesse ponto do plano.

---

### Task 3: Blade Components reutilizáveis de dashboard

**Files:**
- Create: `resources/views/components/dashboard/kpi-card.blade.php`
- Create: `resources/views/components/dashboard/status-badge.blade.php`
- Create: `resources/views/components/dashboard/data-table.blade.php`
- Create: `resources/views/components/dashboard/chart-card.blade.php`
- Test: `tests/Feature/DashboardComponentsTest.php`

**Interfaces:**
- Produces: `<x-dashboard.kpi-card :label :value :hint="null" />`, `<x-dashboard.status-badge :status />` (aceita `ativo|inativo|manutencao`), `<x-dashboard.data-table :headers="[]" :paginator="null">{{ $slot }}</x-dashboard.data-table>`, `<x-dashboard.chart-card :title :canvasId />`.

- [ ] **Step 1: Criar kpi-card**

Create `resources/views/components/dashboard/kpi-card.blade.php`:

```blade
@props(['label', 'value', 'hint' => null])

<div class="rounded-lg bg-white p-4 shadow">
    <p class="text-sm text-gray-500">{{ $label }}</p>
    <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $value }}</p>
    @if ($hint)
        <p class="mt-1 text-xs text-gray-400">{{ $hint }}</p>
    @endif
</div>
```

- [ ] **Step 2: Criar status-badge**

Create `resources/views/components/dashboard/status-badge.blade.php`:

```blade
@props(['status'])

@php
    $map = [
        'ativo' => 'bg-green-100 text-green-800',
        'inativo' => 'bg-gray-100 text-gray-800',
        'manutencao' => 'bg-yellow-100 text-yellow-800',
    ];
    $labels = [
        'ativo' => 'Ativo',
        'inativo' => 'Inativo',
        'manutencao' => 'Em manutenção',
    ];
    $classes = $map[$status] ?? 'bg-gray-100 text-gray-800';
    $label = $labels[$status] ?? $status;
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium $classes"]) }}>
    {{ $label }}
</span>
```

- [ ] **Step 3: Criar data-table**

Create `resources/views/components/dashboard/data-table.blade.php`:

```blade
@props(['headers' => [], 'paginator' => null])

<div class="overflow-x-auto rounded-lg bg-white shadow">
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                @foreach ($headers as $header)
                    <th class="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            {{ $slot }}
        </tbody>
    </table>
</div>

@if ($paginator)
    <div class="mt-4">
        {{ $paginator->links() }}
    </div>
@endif
```

- [ ] **Step 4: Criar chart-card**

Create `resources/views/components/dashboard/chart-card.blade.php`:

```blade
@props(['title', 'canvasId'])

<div class="rounded-lg bg-white p-4 shadow">
    <h3 class="mb-2 text-sm font-medium text-gray-700">{{ $title }}</h3>
    <canvas id="{{ $canvasId }}"></canvas>
</div>
```

- [ ] **Step 5: Escrever teste de renderização dos components**

Create `tests/Feature/DashboardComponentsTest.php`:

```php
<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DashboardComponentsTest extends TestCase
{
    public function test_kpi_card_renders_label_and_value(): void
    {
        $html = Blade::render('<x-dashboard.kpi-card label="Estoque total" value="120 kg" />');

        $this->assertStringContainsString('Estoque total', $html);
        $this->assertStringContainsString('120 kg', $html);
    }

    public function test_status_badge_renders_portuguese_label(): void
    {
        $html = Blade::render('<x-dashboard.status-badge status="manutencao" />');

        $this->assertStringContainsString('Em manutenção', $html);
    }
}
```

- [ ] **Step 6: Rodar o teste**

Run: `php artisan test --filter=DashboardComponentsTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat: blade components reutilizaveis de dashboard"
```

---

### Task 4: Migrations e Models — Point e PointMovement

**Files:**
- Create: `database/migrations/2026_08_26_000001_create_points_table.php`
- Create: `database/migrations/2026_08_26_000002_create_point_movements_table.php`
- Create: `app/Models/Point.php`
- Create: `app/Models/PointMovement.php`
- Test: `tests/Feature/PointMovementModelTest.php`

**Interfaces:**
- Produces: `Point` (fillable: `name, type, address, latitude, longitude, contact_name, contact_phone, capacity_kg, initial_estimate_kg, status, notes`; relação `movements(): HasMany`), `PointMovement` (fillable: `point_id, type, quantity_kg, adjustment_direction, cost, revenue, occurred_at, notes`; relação `point(): BelongsTo`; método `signedQuantity(): float`).

- [ ] **Step 1: Migration de points**

Create `database/migrations/2026_08_26_000001_create_points_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('points', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable();
            $table->decimal('capacity_kg', 8, 2);
            $table->decimal('initial_estimate_kg', 8, 2)->nullable();
            $table->enum('status', ['ativo', 'inativo', 'manutencao'])->default('ativo');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('points');
    }
};
```

- [ ] **Step 2: Migration de point_movements**

Create `database/migrations/2026_08_26_000002_create_point_movements_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('point_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('point_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['reposicao', 'retirada', 'ajuste']);
            $table->decimal('quantity_kg', 8, 2);
            $table->enum('adjustment_direction', ['increase', 'decrease'])->nullable();
            $table->decimal('cost', 8, 2)->nullable();
            $table->decimal('revenue', 8, 2)->nullable();
            $table->date('occurred_at');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('point_movements');
    }
};
```

- [ ] **Step 3: Model Point**

Create `app/Models/Point.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Point extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'address',
        'latitude',
        'longitude',
        'contact_name',
        'contact_phone',
        'capacity_kg',
        'initial_estimate_kg',
        'status',
        'notes',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'capacity_kg' => 'decimal:2',
        'initial_estimate_kg' => 'decimal:2',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(PointMovement::class);
    }
}
```

- [ ] **Step 4: Model PointMovement (com teste primeiro pro `signedQuantity`)**

Create `tests/Feature/PointMovementModelTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\PointMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointMovementModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_signed_quantity_is_positive_for_reposicao(): void
    {
        $movement = PointMovement::factory()->make(['type' => 'reposicao', 'quantity_kg' => 30]);

        $this->assertSame(30.0, $movement->signedQuantity());
    }

    public function test_signed_quantity_is_negative_for_retirada(): void
    {
        $movement = PointMovement::factory()->make(['type' => 'retirada', 'quantity_kg' => 10]);

        $this->assertSame(-10.0, $movement->signedQuantity());
    }

    public function test_signed_quantity_respects_adjustment_direction(): void
    {
        $increase = PointMovement::factory()->make([
            'type' => 'ajuste', 'quantity_kg' => 5, 'adjustment_direction' => 'increase',
        ]);
        $decrease = PointMovement::factory()->make([
            'type' => 'ajuste', 'quantity_kg' => 5, 'adjustment_direction' => 'decrease',
        ]);

        $this->assertSame(5.0, $increase->signedQuantity());
        $this->assertSame(-5.0, $decrease->signedQuantity());
    }
}
```

- [ ] **Step 5: Rodar o teste (deve falhar — model e factory ainda não existem)**

Run: `php artisan test --filter=PointMovementModelTest`
Expected: FAIL (classe `App\Models\PointMovement` ou factory não encontrada)

- [ ] **Step 6: Criar o Model PointMovement**

Create `app/Models/PointMovement.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'point_id',
        'type',
        'quantity_kg',
        'adjustment_direction',
        'cost',
        'revenue',
        'occurred_at',
        'notes',
    ];

    protected $casts = [
        'quantity_kg' => 'decimal:2',
        'cost' => 'decimal:2',
        'revenue' => 'decimal:2',
        'occurred_at' => 'date',
    ];

    public function point(): BelongsTo
    {
        return $this->belongsTo(Point::class);
    }

    public function signedQuantity(): float
    {
        return match ($this->type) {
            'reposicao' => (float) $this->quantity_kg,
            'retirada' => -(float) $this->quantity_kg,
            'ajuste' => $this->adjustment_direction === 'decrease'
                ? -(float) $this->quantity_kg
                : (float) $this->quantity_kg,
        };
    }
}
```

Uma factory mínima é necessária pro teste rodar — criada de forma completa na Task 6, mas o `make()` acima precisa de uma factory já registrada. Crie uma versão inicial simples agora:

Create `database/factories/PointMovementFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Point;
use App\Models\PointMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

class PointMovementFactory extends Factory
{
    protected $model = PointMovement::class;

    public function definition(): array
    {
        return [
            'point_id' => Point::factory(),
            'type' => 'retirada',
            'quantity_kg' => $this->faker->randomFloat(2, 5, 30),
            'adjustment_direction' => null,
            'cost' => null,
            'revenue' => $this->faker->randomFloat(2, 20, 150),
            'occurred_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'notes' => null,
        ];
    }
}
```

E a factory de `Point`:

Create `database/factories/PointFactory.php`:

```php
<?php

namespace Database\Factories;

use App\Models\Point;
use Illuminate\Database\Eloquent\Factories\Factory;

class PointFactory extends Factory
{
    protected $model = Point::class;

    public function definition(): array
    {
        $capacity = $this->faker->randomElement([50, 80, 100, 150, 200]);

        return [
            'name' => $this->faker->company(),
            'type' => $this->faker->randomElement(['Balada', 'Casa de eventos', 'Mercado', 'Outro']),
            'address' => $this->faker->address(),
            'latitude' => $this->faker->latitude(-27.5, -26.8),
            'longitude' => $this->faker->longitude(-49.1, -48.5),
            'contact_name' => $this->faker->name(),
            'contact_phone' => $this->faker->numerify('(##) #####-####'),
            'capacity_kg' => $capacity,
            'initial_estimate_kg' => $capacity * 0.3,
            'status' => $this->faker->randomElement(['ativo', 'ativo', 'ativo', 'inativo', 'manutencao']),
            'notes' => $this->faker->optional()->sentence(),
        ];
    }
}
```

- [ ] **Step 7: Rodar migrations e o teste**

```bash
php artisan migrate
```

Run: `php artisan test --filter=PointMovementModelTest`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat: migrations e models de Point e PointMovement"
```

---

### Task 5: PointStockService (cálculos de estoque, média e financeiro)

**Files:**
- Create: `app/Services/PointStockService.php`
- Test: `tests/Feature/PointStockServiceTest.php`

**Interfaces:**
- Consumes: `Point::movements` (Task 4), `config('dashboards.stock_window_months')`, `config('dashboards.low_stock_threshold_percent')` (Task 2).
- Produces: `PointStockService::currentStock(Point): float`, `::stockPercentage(Point): float`, `::monthlyAverageWithdrawal(Point, ?int $months = null): float`, `::needsRestockSoon(Point): bool`, `::financials(Point, Carbon $start, Carbon $end): array{revenue: float, cost: float, profit: float}`, `::summary(Collection $points, Carbon $start, Carbon $end): array{active_points: int, total_stock: float, revenue: float, cost: float, profit: float}`. Todos os métodos assumem `movements` já eager-loaded no `Point` (caller é responsável por `Point::with('movements')`).

- [ ] **Step 1: Escrever os testes (falhando)**

Create `tests/Feature/PointStockServiceTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Point;
use App\Models\PointMovement;
use App\Services\PointStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PointStockServiceTest extends TestCase
{
    use RefreshDatabase;

    private PointStockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PointStockService();
    }

    public function test_current_stock_sums_reposicao_and_subtracts_retirada(): void
    {
        $point = Point::factory()->create(['capacity_kg' => 100]);

        PointMovement::factory()->create(['point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 80]);
        PointMovement::factory()->create(['point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 30]);

        $point->load('movements');

        $this->assertSame(50.0, $this->service->currentStock($point));
    }

    public function test_current_stock_applies_adjustment_direction(): void
    {
        $point = Point::factory()->create(['capacity_kg' => 100]);

        PointMovement::factory()->create(['point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 50]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'ajuste', 'quantity_kg' => 10, 'adjustment_direction' => 'decrease',
        ]);

        $point->load('movements');

        $this->assertSame(40.0, $this->service->currentStock($point));
    }

    public function test_stock_percentage_relative_to_capacity(): void
    {
        $point = Point::factory()->create(['capacity_kg' => 100]);
        PointMovement::factory()->create(['point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 25]);

        $point->load('movements');

        $this->assertSame(25.0, $this->service->stockPercentage($point));
    }

    public function test_monthly_average_withdrawal_uses_history_when_available(): void
    {
        $point = Point::factory()->create(['initial_estimate_kg' => 999]);

        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 20, 'occurred_at' => now()->subDays(5),
        ]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 10, 'occurred_at' => now()->subMonth()->subDays(5),
        ]);

        $point->load('movements');

        $this->assertSame(15.0, $this->service->monthlyAverageWithdrawal($point, 3));
    }

    public function test_monthly_average_withdrawal_falls_back_to_initial_estimate(): void
    {
        $point = Point::factory()->create(['initial_estimate_kg' => 42]);
        $point->load('movements');

        $this->assertSame(42.0, $this->service->monthlyAverageWithdrawal($point, 3));
    }

    public function test_needs_restock_soon_when_below_threshold_percentage(): void
    {
        $point = Point::factory()->create(['capacity_kg' => 100, 'initial_estimate_kg' => 5]);
        PointMovement::factory()->create(['point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 10]);

        $point->load('movements');

        $this->assertTrue($this->service->needsRestockSoon($point));
    }

    public function test_financials_sum_cost_and_revenue_within_period(): void
    {
        $point = Point::factory()->create();

        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 20,
            'cost' => 50, 'revenue' => null, 'occurred_at' => Carbon::create(2026, 3, 10),
        ]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 15,
            'cost' => null, 'revenue' => 90, 'occurred_at' => Carbon::create(2026, 3, 20),
        ]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 5,
            'cost' => null, 'revenue' => 30, 'occurred_at' => Carbon::create(2026, 4, 1),
        ]);

        $point->load('movements');

        $result = $this->service->financials($point, Carbon::create(2026, 3, 1), Carbon::create(2026, 3, 31));

        $this->assertSame(50.0, $result['cost']);
        $this->assertSame(90.0, $result['revenue']);
        $this->assertSame(40.0, $result['profit']);
    }

    public function test_summary_aggregates_across_points(): void
    {
        $active = Point::factory()->create(['status' => 'ativo', 'capacity_kg' => 100]);
        $inactive = Point::factory()->create(['status' => 'inativo', 'capacity_kg' => 50]);

        PointMovement::factory()->create([
            'point_id' => $active->id, 'type' => 'reposicao', 'quantity_kg' => 60,
            'cost' => 40, 'revenue' => null, 'occurred_at' => Carbon::create(2026, 3, 5),
        ]);
        PointMovement::factory()->create([
            'point_id' => $inactive->id, 'type' => 'reposicao', 'quantity_kg' => 20,
            'cost' => null, 'revenue' => 80, 'occurred_at' => Carbon::create(2026, 3, 6),
        ]);

        $points = Point::with('movements')->get();

        $summary = $this->service->summary($points, Carbon::create(2026, 3, 1), Carbon::create(2026, 3, 31));

        $this->assertSame(1, $summary['active_points']);
        $this->assertSame(80.0, $summary['total_stock']);
        $this->assertSame(80.0, $summary['revenue']);
        $this->assertSame(40.0, $summary['cost']);
        $this->assertSame(40.0, $summary['profit']);
    }
}
```

- [ ] **Step 2: Rodar os testes (devem falhar)**

Run: `php artisan test --filter=PointStockServiceTest`
Expected: FAIL (classe `App\Services\PointStockService` não existe)

- [ ] **Step 3: Implementar o service**

Create `app/Services/PointStockService.php`:

```php
<?php

namespace App\Services;

use App\Models\Point;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class PointStockService
{
    public function currentStock(Point $point): float
    {
        return (float) $point->movements->sum(fn ($movement) => $movement->signedQuantity());
    }

    public function stockPercentage(Point $point): float
    {
        $capacity = (float) $point->capacity_kg;

        if ($capacity <= 0) {
            return 0.0;
        }

        return round(($this->currentStock($point) / $capacity) * 100, 1);
    }

    public function monthlyAverageWithdrawal(Point $point, ?int $months = null): float
    {
        $months ??= (int) config('dashboards.stock_window_months', 3);
        $since = Carbon::now()->subMonths($months)->startOfDay();

        $withdrawals = $point->movements
            ->where('type', 'retirada')
            ->filter(fn ($movement) => $movement->occurred_at->greaterThanOrEqualTo($since));

        if ($withdrawals->isEmpty()) {
            return (float) ($point->initial_estimate_kg ?? 0);
        }

        $monthsWithData = max(1, $withdrawals
            ->map(fn ($movement) => $movement->occurred_at->format('Y-m'))
            ->unique()
            ->count());

        return round($withdrawals->sum(fn ($movement) => (float) $movement->quantity_kg) / $monthsWithData, 2);
    }

    public function needsRestockSoon(Point $point): bool
    {
        $threshold = (float) config('dashboards.low_stock_threshold_percent', 20);

        if ($this->stockPercentage($point) < $threshold) {
            return true;
        }

        return $this->currentStock($point) < $this->monthlyAverageWithdrawal($point);
    }

    public function financials(Point $point, Carbon $start, Carbon $end): array
    {
        $movements = $point->movements->filter(
            fn ($movement) => $movement->occurred_at->between($start, $end)
        );

        $revenue = (float) $movements->sum(fn ($m) => (float) ($m->revenue ?? 0));
        $cost = (float) $movements->sum(fn ($m) => (float) ($m->cost ?? 0));

        return [
            'revenue' => $revenue,
            'cost' => $cost,
            'profit' => $revenue - $cost,
        ];
    }

    public function summary(Collection $points, Carbon $start, Carbon $end): array
    {
        $totals = ['active_points' => 0, 'total_stock' => 0.0, 'revenue' => 0.0, 'cost' => 0.0, 'profit' => 0.0];

        foreach ($points as $point) {
            if ($point->status === 'ativo') {
                $totals['active_points']++;
            }

            $totals['total_stock'] += $this->currentStock($point);

            $financials = $this->financials($point, $start, $end);
            $totals['revenue'] += $financials['revenue'];
            $totals['cost'] += $financials['cost'];
            $totals['profit'] += $financials['profit'];
        }

        return $totals;
    }
}
```

- [ ] **Step 4: Rodar os testes (devem passar)**

Run: `php artisan test --filter=PointStockServiceTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "feat: PointStockService com calculos de estoque, media e financeiro"
```

---

### Task 6: Factories completas e Seeders (dados fictícios)

**Files:**
- Modify: `database/factories/PointMovementFactory.php` — adiciona estado `reposicao()`
- Create: `database/seeders/PointSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php` — chama `PointSeeder`

**Interfaces:**
- Consumes: `Point`, `PointMovement` (Task 4).
- Produces: `PointMovementFactory::reposicao(): static` (state), 10 pontos com 3–6 meses de histórico via `php artisan db:seed`.

- [ ] **Step 1: Adicionar estado `reposicao` à factory**

Edit `database/factories/PointMovementFactory.php`, adicionando o método:

```php
    public function reposicao(): static
    {
        return $this->state(fn () => [
            'type' => 'reposicao',
            'quantity_kg' => $this->faker->randomFloat(2, 20, 60),
            'cost' => $this->faker->randomFloat(2, 40, 120),
            'revenue' => null,
            'adjustment_direction' => null,
        ]);
    }
```

- [ ] **Step 2: Criar o PointSeeder**

Create `database/seeders/PointSeeder.php`:

```php
<?php

namespace Database\Seeders;

use App\Models\Point;
use App\Models\PointMovement;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class PointSeeder extends Seeder
{
    public function run(): void
    {
        Point::factory()->count(10)->create()->each(function (Point $point) {
            $months = fake()->numberBetween(3, 6);

            for ($i = $months; $i >= 0; $i--) {
                $monthStart = Carbon::now()->subMonths($i)->startOfMonth();

                PointMovement::factory()->reposicao()->create([
                    'point_id' => $point->id,
                    'occurred_at' => $monthStart->copy()->addDays(fake()->numberBetween(0, 3)),
                ]);

                foreach (range(1, fake()->numberBetween(2, 5)) as $ignored) {
                    PointMovement::factory()->create([
                        'point_id' => $point->id,
                        'type' => 'retirada',
                        'occurred_at' => $monthStart->copy()->addDays(fake()->numberBetween(4, 27)),
                    ]);
                }
            }
        });
    }
}
```

- [ ] **Step 3: Chamar o PointSeeder a partir do DatabaseSeeder**

Edit `database/seeders/DatabaseSeeder.php`, adicionando ao final do método `run()`:

```php
        $this->call(PointSeeder::class);
```

- [ ] **Step 4: Rodar o seeder no banco de desenvolvimento (fresh)**

```bash
php artisan migrate:fresh --seed
```

- [ ] **Step 5: Verificar dados gerados**

Run: `php artisan tinker --execute="echo App\Models\Point::count() . ' pontos, ' . App\Models\PointMovement::count() . ' movimentacoes';"`
Expected: `10 pontos, ` seguido de um número de movimentações bem maior que 10.

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "feat: seeders com pontos e historico de movimentacoes ficticios"
```

---

### Task 7: Livewire PointFormModal (criar/editar ponto)

**Files:**
- Create: `app/Livewire/PointFormModal.php`
- Create: `resources/views/livewire/point-form-modal.blade.php`
- Test: `tests/Feature/PointFormModalTest.php`

**Interfaces:**
- Consumes: `Point` (Task 4), evento `open-point-form` (payload `pointId: ?int`).
- Produces: evento `point-saved` (payload `pointId: int`) disparado após salvar, evento `toast` (payload `type, message`).

- [ ] **Step 1: Escrever o teste (falhando)**

Create `tests/Feature/PointFormModalTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Livewire\PointFormModal;
use App\Models\Point;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PointFormModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_missing_required_fields(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(PointFormModal::class)
            ->call('open')
            ->set('name', '')
            ->call('save')
            ->assertHasErrors(['name']);
    }

    public function test_creates_a_point_with_valid_data(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(PointFormModal::class)
            ->call('open')
            ->set('name', 'Balada Nova')
            ->set('type', 'Balada')
            ->set('capacity_kg', 100)
            ->set('status', 'ativo')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('points', ['name' => 'Balada Nova']);
    }

    public function test_loads_existing_point_data_on_open(): void
    {
        $this->actingAs(User::factory()->create());
        $point = Point::factory()->create(['name' => 'Ponto Existente']);

        Livewire::test(PointFormModal::class)
            ->call('open', $point->id)
            ->assertSet('name', 'Ponto Existente');
    }
}
```

- [ ] **Step 2: Rodar o teste (deve falhar)**

Run: `php artisan test --filter=PointFormModalTest`
Expected: FAIL (classe `App\Livewire\PointFormModal` não existe)

- [ ] **Step 3: Criar o componente**

Create `app/Livewire/PointFormModal.php`:

```php
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
            'type' => 'required|string|max:100',
            'address' => 'nullable|string|max:255',
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
            'name', 'type', 'address', 'latitude', 'longitude',
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
```

- [ ] **Step 4: Criar a view do modal**

Create `resources/views/livewire/point-form-modal.blade.php`:

```blade
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
```

- [ ] **Step 5: Rodar o teste (deve passar)**

Run: `php artisan test --filter=PointFormModalTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "feat: Livewire PointFormModal para criar e editar pontos"
```

---

### Task 8: Livewire MovementFormModal (lançar movimentação)

**Files:**
- Create: `app/Livewire/MovementFormModal.php`
- Create: `resources/views/livewire/movement-form-modal.blade.php`
- Test: `tests/Feature/MovementFormModalTest.php`

**Interfaces:**
- Consumes: `PointMovement` (Task 4), evento `open-movement-form` (payload `pointId: int`).
- Produces: evento `point-saved` (payload `pointId: int`), evento `toast`.

- [ ] **Step 1: Escrever os testes (falhando)**

Create `tests/Feature/MovementFormModalTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Livewire\MovementFormModal;
use App\Models\Point;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MovementFormModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_negative_quantity(): void
    {
        $this->actingAs(User::factory()->create());
        $point = Point::factory()->create();

        Livewire::test(MovementFormModal::class)
            ->call('open', $point->id)
            ->set('quantity_kg', -5)
            ->set('occurred_at', now()->format('Y-m-d'))
            ->call('save')
            ->assertHasErrors(['quantity_kg']);
    }

    public function test_requires_adjustment_direction_when_type_is_ajuste(): void
    {
        $this->actingAs(User::factory()->create());
        $point = Point::factory()->create();

        Livewire::test(MovementFormModal::class)
            ->call('open', $point->id)
            ->set('type', 'ajuste')
            ->set('quantity_kg', 10)
            ->set('occurred_at', now()->format('Y-m-d'))
            ->call('save')
            ->assertHasErrors(['adjustment_direction']);
    }

    public function test_creates_movement_with_valid_data(): void
    {
        $this->actingAs(User::factory()->create());
        $point = Point::factory()->create();

        Livewire::test(MovementFormModal::class)
            ->call('open', $point->id)
            ->set('type', 'retirada')
            ->set('quantity_kg', 12.5)
            ->set('revenue', 60)
            ->set('occurred_at', now()->format('Y-m-d'))
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('point_movements', [
            'point_id' => $point->id, 'type' => 'retirada', 'quantity_kg' => 12.5,
        ]);
    }
}
```

- [ ] **Step 2: Rodar os testes (devem falhar)**

Run: `php artisan test --filter=MovementFormModalTest`
Expected: FAIL

- [ ] **Step 3: Criar o componente**

Create `app/Livewire/MovementFormModal.php`:

```php
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
    public ?float $cost = null;
    public ?float $revenue = null;
    public string $occurred_at = '';
    public ?string $notes = null;

    protected function rules(): array
    {
        return [
            'type' => 'required|in:reposicao,retirada,ajuste',
            'quantity_kg' => 'required|numeric|min:0.01',
            'adjustment_direction' => 'required_if:type,ajuste|nullable|in:increase,decrease',
            'cost' => 'nullable|numeric|min:0',
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
        $this->cost = null;
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
```

- [ ] **Step 4: Criar a view do modal**

Create `resources/views/livewire/movement-form-modal.blade.php`:

```blade
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
                            <label class="block text-sm font-medium text-gray-700">Custo (R$)</label>
                            <input type="number" step="0.01" wire:model="cost" class="mt-1 w-full rounded-md border-gray-300">
                            @error('cost') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
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
                        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">
                            Registrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>
```

- [ ] **Step 5: Rodar os testes (devem passar)**

Run: `php artisan test --filter=MovementFormModalTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "feat: Livewire MovementFormModal para lancar movimentacoes"
```

---

### Task 9: Livewire PointDetail (histórico + drawer)

**Files:**
- Create: `app/Livewire/PointDetail.php`
- Create: `resources/views/livewire/point-detail.blade.php`
- Test: `tests/Feature/PointDetailTest.php`

**Interfaces:**
- Consumes: `PointStockService` (Task 5), evento `open-point-detail` (payload `pointId: int`), evento `point-saved` (Tasks 7 e 8).
- Produces: painel/drawer que dispara `open-movement-form` (com `pointId`) e `open-point-form` (com `pointId`) via `$dispatch` no Blade.

- [ ] **Step 1: Escrever o teste (falhando)**

Create `tests/Feature/PointDetailTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Livewire\PointDetail;
use App\Models\Point;
use App\Models\PointMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PointDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_shows_current_stock_and_movement_history(): void
    {
        $this->actingAs(User::factory()->create());
        $point = Point::factory()->create(['name' => 'Ponto Detalhe', 'capacity_kg' => 100]);

        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 40,
        ]);

        Livewire::test(PointDetail::class)
            ->call('open', $point->id)
            ->assertSee('Ponto Detalhe')
            ->assertSee('40');
    }

    public function test_refreshes_after_point_saved_event(): void
    {
        $this->actingAs(User::factory()->create());
        $point = Point::factory()->create();

        Livewire::test(PointDetail::class)
            ->call('open', $point->id)
            ->dispatch('point-saved', pointId: $point->id)
            ->assertOk();
    }
}
```

- [ ] **Step 2: Rodar o teste (deve falhar)**

Run: `php artisan test --filter=PointDetailTest`
Expected: FAIL

- [ ] **Step 3: Criar o componente**

Create `app/Livewire/PointDetail.php`:

```php
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
```

- [ ] **Step 4: Criar a view do drawer**

Create `resources/views/livewire/point-detail.blade.php`:

```blade
<div>
    @if ($showDrawer && $point)
        <div class="fixed inset-0 z-40 flex justify-end bg-black/50">
            <div class="h-full w-full max-w-xl overflow-y-auto bg-white p-6 shadow-xl">
                <div class="flex items-start justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">{{ $point->name }}</h2>
                        <p class="text-sm text-gray-500">{{ $point->type }} — <x-dashboard.status-badge :status="$point->status" /></p>
                    </div>
                    <button wire:click="$set('showDrawer', false)" class="text-gray-400 hover:text-gray-600">Fechar</button>
                </div>

                <div class="mt-4 grid grid-cols-3 gap-3">
                    <x-dashboard.kpi-card label="Estoque atual" value="{{ number_format($currentStock, 1) }} kg" :hint="number_format($stockPercentage, 1) . '% da capacidade'" />
                    <x-dashboard.kpi-card label="Média mensal" value="{{ number_format($monthlyAverage, 1) }} kg" />
                    <x-dashboard.kpi-card label="Capacidade" value="{{ number_format($point->capacity_kg, 1) }} kg" />
                </div>

                <div class="mt-6 flex gap-2">
                    <button
                        wire:click="$dispatch('open-point-form', { pointId: {{ $point->id }} })"
                        class="rounded-md border px-4 py-2 text-sm text-gray-700"
                    >
                        Editar ponto
                    </button>
                    <button
                        wire:click="$dispatch('open-movement-form', { pointId: {{ $point->id }} })"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
                    >
                        Lançar movimentação
                    </button>
                </div>

                <h3 class="mt-8 text-sm font-medium text-gray-700">Histórico de movimentações</h3>

                <x-dashboard.data-table :headers="['Data', 'Tipo', 'Quantidade (kg)', 'Custo', 'Receita']" :paginator="$movements" class="mt-2">
                    @foreach ($movements as $movement)
                        <tr>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $movement->occurred_at->format('d/m/Y') }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ ucfirst($movement->type) }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($movement->quantity_kg, 1) }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $movement->cost ? 'R$ '.number_format($movement->cost, 2, ',', '.') : '—' }}</td>
                            <td class="px-4 py-2 text-sm text-gray-700">{{ $movement->revenue ? 'R$ '.number_format($movement->revenue, 2, ',', '.') : '—' }}</td>
                        </tr>
                    @endforeach
                </x-dashboard.data-table>
            </div>
        </div>
    @endif
</div>
```

- [ ] **Step 5: Rodar o teste (deve passar)**

Run: `php artisan test --filter=PointDetailTest`
Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add -A
git commit -m "feat: Livewire PointDetail com historico e acoes rapidas"
```

---

### Task 10: PointsDashboardController + view (Dashboard de Pontos)

**Files:**
- Create: `app/Http/Controllers/Dashboards/PointsDashboardController.php`
- Create: `resources/views/dashboards/points/index.blade.php`
- Modify: `routes/web.php` — adiciona `dashboards.points.index`
- Test: `tests/Feature/PointsDashboardTest.php`

**Interfaces:**
- Consumes: `PointStockService` (Task 5), `Point` (Task 4), componentes `PointFormModal`/`MovementFormModal`/`PointDetail` (Tasks 7–9), Blade components (Task 3).
- Produces: rota `dashboards.points.index` (GET `/dashboards/pontos`, aceita query params `status`, `month`, `year`).

- [ ] **Step 1: Escrever o teste (falhando)**

Create `tests/Feature/PointsDashboardTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Point;
use App\Models\PointMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointsDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboards.points.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_points_summary(): void
    {
        $this->actingAs(User::factory()->create());

        $point = Point::factory()->create(['name' => 'Balada Teste', 'capacity_kg' => 100]);
        PointMovement::factory()->create([
            'point_id' => $point->id, 'type' => 'reposicao', 'quantity_kg' => 60, 'occurred_at' => now(),
        ]);

        $response = $this->get(route('dashboards.points.index'));

        $response->assertOk();
        $response->assertSee('Balada Teste');
    }

    public function test_filters_by_status(): void
    {
        $this->actingAs(User::factory()->create());

        Point::factory()->create(['name' => 'Ponto Ativo', 'status' => 'ativo']);
        Point::factory()->create(['name' => 'Ponto Inativo', 'status' => 'inativo']);

        $response = $this->get(route('dashboards.points.index', ['status' => 'ativo']));

        $response->assertSee('Ponto Ativo');
        $response->assertDontSee('Ponto Inativo');
    }
}
```

- [ ] **Step 2: Rodar o teste (deve falhar)**

Run: `php artisan test --filter=PointsDashboardTest`
Expected: FAIL (rota `dashboards.points.index` não existe)

- [ ] **Step 3: Criar o controller**

Create `app/Http/Controllers/Dashboards/PointsDashboardController.php`:

```php
<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Models\Point;
use App\Services\PointStockService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class PointsDashboardController extends Controller
{
    public function index(Request $request, PointStockService $stockService)
    {
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $status = $request->input('status');

        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        $points = Point::with('movements')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy('name')
            ->get();

        $rows = $points->map(function (Point $point) use ($stockService, $periodStart, $periodEnd) {
            $financials = $stockService->financials($point, $periodStart, $periodEnd);

            return [
                'point' => $point,
                'currentStock' => $stockService->currentStock($point),
                'stockPercentage' => $stockService->stockPercentage($point),
                'monthlyAverage' => $stockService->monthlyAverageWithdrawal($point),
                'profit' => $financials['profit'],
                'needsRestockSoon' => $stockService->needsRestockSoon($point),
            ];
        });

        $summary = $stockService->summary($points, $periodStart, $periodEnd);

        return view('dashboards.points.index', [
            'rows' => $rows,
            'summary' => $summary,
            'status' => $status,
            'month' => $month,
            'year' => $year,
        ]);
    }
}
```

- [ ] **Step 4: Registrar a rota**

Edit `routes/web.php`, dentro do grupo `dashboards.`:

```php
Route::middleware('auth')->prefix('dashboards')->name('dashboards.')->group(function () {
    Route::get('/pontos', [\App\Http\Controllers\Dashboards\PointsDashboardController::class, 'index'])->name('points.index');
});
```

- [ ] **Step 5: Criar a view**

Create `resources/views/dashboards/points/index.blade.php`:

```blade
<x-app-layout>
    <x-slot name="header">Pontos de Freezer</x-slot>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <x-dashboard.kpi-card label="Pontos ativos" value="{{ $summary['active_points'] }}" />
        <x-dashboard.kpi-card label="Estoque total" value="{{ number_format($summary['total_stock'], 1) }} kg" />
        <x-dashboard.kpi-card label="Receita do mês" value="R$ {{ number_format($summary['revenue'], 2, ',', '.') }}" />
        <x-dashboard.kpi-card label="Custo do mês" value="R$ {{ number_format($summary['cost'], 2, ',', '.') }}" />
        <x-dashboard.kpi-card label="Lucro do mês" value="R$ {{ number_format($summary['profit'], 2, ',', '.') }}" />
    </div>

    <form method="GET" class="mt-6 flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-sm text-gray-600">Status</label>
            <select name="status" class="mt-1 rounded-md border-gray-300" onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="ativo" @selected($status === 'ativo')>Ativo</option>
                <option value="inativo" @selected($status === 'inativo')>Inativo</option>
                <option value="manutencao" @selected($status === 'manutencao')>Em manutenção</option>
            </select>
        </div>
        <div>
            <label class="block text-sm text-gray-600">Mês</label>
            <input type="number" name="month" min="1" max="12" value="{{ $month }}" class="mt-1 w-20 rounded-md border-gray-300">
        </div>
        <div>
            <label class="block text-sm text-gray-600">Ano</label>
            <input type="number" name="year" value="{{ $year }}" class="mt-1 w-24 rounded-md border-gray-300">
        </div>
        <button type="submit" class="rounded-md bg-gray-800 px-4 py-2 text-sm text-white">Filtrar</button>
    </form>

    <div class="mt-6">
        <x-dashboard.data-table :headers="['Ponto', 'Status', 'Estoque', '% capacidade', 'Média mensal', 'Lucro do mês', 'Repor?', '']">
            @foreach ($rows as $row)
                <tr>
                    <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $row['point']->name }}</td>
                    <td class="px-4 py-2"><x-dashboard.status-badge :status="$row['point']->status" /></td>
                    <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($row['currentStock'], 1) }} kg</td>
                    <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($row['stockPercentage'], 1) }}%</td>
                    <td class="px-4 py-2 text-sm text-gray-700">{{ number_format($row['monthlyAverage'], 1) }} kg</td>
                    <td class="px-4 py-2 text-sm text-gray-700">R$ {{ number_format($row['profit'], 2, ',', '.') }}</td>
                    <td class="px-4 py-2">
                        @if ($row['needsRestockSoon'])
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Repor em breve</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-right">
                        <button
                            onclick="Livewire.dispatch('open-point-detail', { pointId: {{ $row['point']->id }} })"
                            class="text-sm text-indigo-600 hover:text-indigo-800"
                        >
                            Ver detalhes
                        </button>
                    </td>
                </tr>
            @endforeach
        </x-dashboard.data-table>
    </div>

    <div class="mt-4">
        <button
            onclick="Livewire.dispatch('open-point-form')"
            class="rounded-md bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"
        >
            Novo ponto
        </button>
    </div>

    <livewire:point-form-modal />
    <livewire:movement-form-modal />
    <livewire:point-detail />
</x-app-layout>
```

- [ ] **Step 6: Rodar os testes**

Run: `php artisan test --filter=PointsDashboardTest`
Expected: PASS

- [ ] **Step 7: Commit**

```bash
git add -A
git commit -m "feat: Dashboard de Pontos de Freezer completo"
```

---

### Task 11: OverviewKpiService (KPIs consolidados)

**Files:**
- Create: `app/Services/OverviewKpiService.php`
- Test: `tests/Feature/OverviewKpiServiceTest.php`

**Interfaces:**
- Consumes: `PointStockService::summary()` e `::financials()` (Task 5), `Point` (Task 4).
- Produces: `OverviewKpiService::monthlyTotals(Carbon, Carbon): array`, `::monthOverMonthComparison(): array{current: array, previous: array}`, `::last12MonthsSeries(): array<int, array{label: string, revenue: float, cost: float, profit: float}>`, `::ranking(Carbon, Carbon, int $limit = 5): array{top: array, bottom: array}` (cada item com `['point' => Point, 'profit' => float]`).

- [ ] **Step 1: Escrever os testes (falhando)**

Create `tests/Feature/OverviewKpiServiceTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Point;
use App\Models\PointMovement;
use App\Services\OverviewKpiService;
use App\Services\PointStockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class OverviewKpiServiceTest extends TestCase
{
    use RefreshDatabase;

    private OverviewKpiService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OverviewKpiService(new PointStockService());
    }

    public function test_last_12_months_series_has_12_entries(): void
    {
        $series = $this->service->last12MonthsSeries();

        $this->assertCount(12, $series);
        $this->assertArrayHasKey('revenue', $series[0]);
        $this->assertArrayHasKey('cost', $series[0]);
        $this->assertArrayHasKey('profit', $series[0]);
    }

    public function test_ranking_orders_points_by_profit_descending(): void
    {
        $high = Point::factory()->create(['name' => 'Ponto Lucrativo']);
        $low = Point::factory()->create(['name' => 'Ponto Fraco']);

        PointMovement::factory()->create([
            'point_id' => $high->id, 'type' => 'retirada', 'quantity_kg' => 10,
            'revenue' => 500, 'cost' => null, 'occurred_at' => now(),
        ]);
        PointMovement::factory()->create([
            'point_id' => $low->id, 'type' => 'retirada', 'quantity_kg' => 10,
            'revenue' => 10, 'cost' => null, 'occurred_at' => now(),
        ]);

        $ranking = $this->service->ranking(now()->startOfMonth(), now()->endOfMonth());

        $this->assertSame('Ponto Lucrativo', $ranking['top'][0]['point']->name);
    }
}
```

- [ ] **Step 2: Rodar os testes (devem falhar)**

Run: `php artisan test --filter=OverviewKpiServiceTest`
Expected: FAIL

- [ ] **Step 3: Implementar o service**

Create `app/Services/OverviewKpiService.php`:

```php
<?php

namespace App\Services;

use App\Models\Point;
use Illuminate\Support\Carbon;

class OverviewKpiService
{
    public function __construct(private PointStockService $stockService)
    {
    }

    public function monthlyTotals(Carbon $start, Carbon $end): array
    {
        $points = Point::with('movements')->get();

        return $this->stockService->summary($points, $start, $end);
    }

    public function monthOverMonthComparison(): array
    {
        $current = $this->monthlyTotals(now()->startOfMonth(), now()->endOfMonth());
        $previous = $this->monthlyTotals(
            now()->subMonthNoOverflow()->startOfMonth(),
            now()->subMonthNoOverflow()->endOfMonth()
        );

        return ['current' => $current, 'previous' => $previous];
    }

    public function last12MonthsSeries(): array
    {
        $series = [];

        for ($i = 11; $i >= 0; $i--) {
            $start = now()->subMonths($i)->startOfMonth();
            $end = $start->copy()->endOfMonth();
            $totals = $this->monthlyTotals($start, $end);

            $series[] = [
                'label' => $start->translatedFormat('M/y'),
                'revenue' => $totals['revenue'],
                'cost' => $totals['cost'],
                'profit' => $totals['profit'],
            ];
        }

        return $series;
    }

    public function ranking(Carbon $start, Carbon $end, int $limit = 5): array
    {
        $points = Point::with('movements')->get();

        $ranked = $points->map(function (Point $point) use ($start, $end) {
            $financials = $this->stockService->financials($point, $start, $end);

            return ['point' => $point, 'profit' => $financials['profit']];
        })->sortByDesc('profit')->values();

        return [
            'top' => $ranked->take($limit)->all(),
            'bottom' => $ranked->reverse()->take($limit)->values()->all(),
        ];
    }
}
```

- [ ] **Step 4: Rodar os testes (devem passar)**

Run: `php artisan test --filter=OverviewKpiServiceTest`
Expected: PASS

- [ ] **Step 5: Commit**

```bash
git add -A
git commit -m "feat: OverviewKpiService com comparativo mensal, serie e ranking"
```

---

### Task 12: OverviewDashboardController + view (Visão Executiva)

**Files:**
- Create: `app/Http/Controllers/Dashboards/OverviewDashboardController.php`
- Create: `resources/views/dashboards/overview/index.blade.php`
- Modify: `routes/web.php` — adiciona `dashboards.overview.index`
- Test: `tests/Feature/OverviewDashboardTest.php`

**Interfaces:**
- Consumes: `OverviewKpiService` (Task 11), Blade components (Task 3), Chart.js (via CDN removido — usar asset local através do npm, ver Step 3).

- [ ] **Step 1: Escrever o teste (falhando)**

Create `tests/Feature/OverviewDashboardTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverviewDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('dashboards.overview.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_kpis(): void
    {
        $this->actingAs(User::factory()->create());

        $response = $this->get(route('dashboards.overview.index'));

        $response->assertOk();
        $response->assertSee('Lucro');
        $response->assertSee('Margem');
    }
}
```

- [ ] **Step 2: Rodar o teste (deve falhar)**

Run: `php artisan test --filter=OverviewDashboardTest`
Expected: FAIL

- [ ] **Step 3: Instalar Chart.js como dependência npm**

```bash
npm install chart.js
```

Em `resources/js/app.js`, adicione:

```js
import Chart from 'chart.js/auto';
window.Chart = Chart;
```

```bash
npm run build
```

- [ ] **Step 4: Criar o controller**

Create `app/Http/Controllers/Dashboards/OverviewDashboardController.php`:

```php
<?php

namespace App\Http\Controllers\Dashboards;

use App\Http\Controllers\Controller;
use App\Services\OverviewKpiService;

class OverviewDashboardController extends Controller
{
    public function index(OverviewKpiService $kpiService)
    {
        $comparison = $kpiService->monthOverMonthComparison();
        $series = $kpiService->last12MonthsSeries();
        $ranking = $kpiService->ranking(now()->startOfMonth(), now()->endOfMonth());

        $current = $comparison['current'];
        $margin = $current['revenue'] > 0
            ? round(($current['profit'] / $current['revenue']) * 100, 1)
            : 0.0;

        return view('dashboards.overview.index', [
            'comparison' => $comparison,
            'series' => $series,
            'ranking' => $ranking,
            'margin' => $margin,
        ]);
    }
}
```

- [ ] **Step 5: Registrar a rota**

Edit `routes/web.php`, adicionando ao grupo `dashboards.`:

```php
    Route::get('/geral', [\App\Http\Controllers\Dashboards\OverviewDashboardController::class, 'index'])->name('overview.index');
```

- [ ] **Step 6: Criar a view**

Create `resources/views/dashboards/overview/index.blade.php`:

```blade
<x-app-layout>
    <x-slot name="header">Visão Executiva</x-slot>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-dashboard.kpi-card label="Receita do mês" value="R$ {{ number_format($comparison['current']['revenue'], 2, ',', '.') }}" :hint="'Mês anterior: R$ '.number_format($comparison['previous']['revenue'], 2, ',', '.')" />
        <x-dashboard.kpi-card label="Custo do mês" value="R$ {{ number_format($comparison['current']['cost'], 2, ',', '.') }}" :hint="'Mês anterior: R$ '.number_format($comparison['previous']['cost'], 2, ',', '.')" />
        <x-dashboard.kpi-card label="Lucro do mês" value="R$ {{ number_format($comparison['current']['profit'], 2, ',', '.') }}" :hint="'Mês anterior: R$ '.number_format($comparison['previous']['profit'], 2, ',', '.')" />
        <x-dashboard.kpi-card label="Margem" value="{{ number_format($margin, 1) }}%" />
    </div>

    <div class="mt-6">
        <x-dashboard.chart-card title="Evolução mensal (últimos 12 meses)" canvasId="monthly-chart" />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div>
            <h3 class="mb-2 text-sm font-medium text-gray-700">Mais lucrativos</h3>
            <x-dashboard.data-table :headers="['Ponto', 'Lucro do mês']">
                @foreach ($ranking['top'] as $item)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $item['point']->name }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">R$ {{ number_format($item['profit'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </x-dashboard.data-table>
        </div>
        <div>
            <h3 class="mb-2 text-sm font-medium text-gray-700">Menos lucrativos</h3>
            <x-dashboard.data-table :headers="['Ponto', 'Lucro do mês']">
                @foreach ($ranking['bottom'] as $item)
                    <tr>
                        <td class="px-4 py-2 text-sm text-gray-900">{{ $item['point']->name }}</td>
                        <td class="px-4 py-2 text-sm text-gray-700">R$ {{ number_format($item['profit'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </x-dashboard.data-table>
        </div>
    </div>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            new Chart(document.getElementById('monthly-chart'), {
                type: 'line',
                data: {
                    labels: @json(collect($series)->pluck('label')),
                    datasets: [
                        { label: 'Receita', data: @json(collect($series)->pluck('revenue')), borderColor: '#4f46e5' },
                        { label: 'Custo', data: @json(collect($series)->pluck('cost')), borderColor: '#dc2626' },
                        { label: 'Lucro', data: @json(collect($series)->pluck('profit')), borderColor: '#16a34a' },
                    ],
                },
            });
        });
    </script>
    @endpush
</x-app-layout>
```

O layout `resources/views/layouts/app.blade.php` (Task 2) precisa renderizar a stack `scripts`. Edit o arquivo adicionando, logo antes de `@livewireScripts`:

```blade
    @stack('scripts')
```

- [ ] **Step 7: Rodar os testes**

Run: `php artisan test --filter=OverviewDashboardTest`
Expected: PASS

- [ ] **Step 8: Commit**

```bash
git add -A
git commit -m "feat: Dashboard de Visao Executiva com KPIs, grafico e ranking"
```

---

### Task 13: Finalização — suite completa, banco de dev, servidor local

**Files:**
- Modify: nenhum arquivo de produção (apenas verificação e, se necessário, correções pontuais encontradas nos passos abaixo)

- [ ] **Step 1: Rodar a suite de testes completa**

Run: `php artisan test`
Expected: todos os testes PASS (Breeze Auth, DashboardRegistry, DashboardComponents, PointMovementModel, PointStockService, PointFormModal, MovementFormModal, PointDetail, PointsDashboard, OverviewKpiService, OverviewDashboard)

- [ ] **Step 2: Recriar o banco de desenvolvimento com dados fictícios**

```bash
php artisan migrate:fresh --seed
```

- [ ] **Step 3: Subir o servidor local**

```bash
php artisan serve
```

Em outro terminal, com o servidor no ar:

```bash
curl -s -o /dev/null -w "%{http_code}\n" http://127.0.0.1:8000/login
```

Expected: `200`

- [ ] **Step 4: Smoke test manual autenticado**

Acesse `http://127.0.0.1:8000/login` no navegador, entre com `admin@gelatto.com.br` / `password`, e confirme visualmente:
- Sidebar lista "Pontos de Freezer" e "Visão Executiva".
- `/dashboards/pontos` mostra os cards de resumo e a grade de ~10 pontos populados pelo seeder.
- Clicar em "Ver detalhes" abre o drawer com histórico e permite lançar uma movimentação nova (toast de sucesso aparece).
- "Novo ponto" abre o modal de cadastro e salva corretamente.
- `/dashboards/geral` mostra os KPIs, o gráfico de evolução mensal renderizado e os rankings.

- [ ] **Step 5: Registrar explicitamente os dashboards fora de escopo**

Confirme que os comentários em `config/dashboards.php` (Task 2, Step 2) listando Financeiro, Reposição/Logística, Mapa e Clientes/Parceiros ainda estão presentes e atualizados.

- [ ] **Step 6: Commit final**

```bash
git add -A
git commit -m "chore: verificacao final da suite, seed de dev e smoke test manual"
```
