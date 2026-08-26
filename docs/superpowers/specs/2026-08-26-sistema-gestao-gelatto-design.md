# Sistema de Gestão Gelatto ICE CO. — Design

## Contexto

A Gelatto ICE CO. é uma distribuidora de gelo (revenda, sem produção própria). Um dos pilares do negócio é deixar freezers de gelo em pontos parceiros (baladas, casas de eventos, mercados) em regime parecido com comodato: reposição periódica de gelo, com acompanhamento de estoque, consumo, custo e lucro por ponto.

Este documento descreve o design de um sistema de gestão em Laravel, organizado como uma coleção extensível de dashboards, começando pelo módulo de **Pontos de Freezer** (prioritário) e pelo módulo de **Visão Executiva**.

## Objetivo

Construir a base de um sistema de dashboards modular, onde adicionar um novo dashboard no futuro seja um procedimento mecânico e localizado (controller + view + rota + entrada de registro), sem refatoração da navegação ou do layout. Implementar completo o módulo de Pontos de Freezer (CRUD + histórico + cálculos automáticos de estoque/consumo/lucro) e o módulo de Visão Executiva (KPIs consolidados).

## Stack

Projeto Laravel 10 (PHP 8.3) já criado via Composer, sem pacotes de front-end/auth instalados. Decisões de stack (reversíveis, não afetam schema):

- **Laravel Breeze** (stack Blade) — autenticação simples, um único usuário administrador criado via seeder, sem registro público.
- **Livewire** — componentes para criação/edição em modal e inline (pedido explícito do cliente), com validação server-side e feedback via toast.
- **Tailwind CSS** — via Breeze scaffold, layout responsivo (desktop + mobile).
- **Chart.js** — gráfico de evolução mensal no dashboard executivo.
- **MySQL** — já configurado em `.env` (`DB_DATABASE=laravel`).

## Arquitetura do registro de dashboards

`config/dashboards.php` retorna um array de definições, cada uma com: `key`, `name` (label PT-BR), `icon` (nome de ícone SVG/heroicon), `route` (name da rota), `order`. A sidebar (`resources/views/layouts/navigation.blade.php` ou parcial dedicada) itera esse array ordenado por `order` e renderiza os links via `route($item['route'])`.

Fluxo para adicionar um dashboard novo:
1. Controller em `app/Http/Controllers/Dashboards/`.
2. View em `resources/views/dashboards/{nome}/`.
3. Rota em `routes/web.php`, dentro do grupo `/dashboards` (middleware `auth`).
4. Entrada nova em `config/dashboards.php`.

Nenhuma dessas etapas exige tocar em código de outro dashboard.

### Blade Components reutilizáveis

Em `resources/views/components/dashboard/`:
- `kpi-card.blade.php` — card de indicador (label, valor, variação opcional).
- `data-table.blade.php` — tabela com slot de colunas + paginação Laravel.
- `chart-card.blade.php` — wrapper para canvas Chart.js.
- `status-badge.blade.php` — badge colorido (ativo/inativo/manutenção, ou nível de urgência de reposição).

## Banco de dados

### `points`
| coluna | tipo | notas |
|---|---|---|
| id | bigint pk | |
| name | string | nome do ponto/estabelecimento |
| type | string | balada, casa de eventos, mercado, outro — texto livre |
| address | string nullable | endereço completo |
| latitude | decimal(10,7) nullable | uso futuro (mapa) |
| longitude | decimal(10,7) nullable | uso futuro (mapa) |
| contact_name | string nullable | |
| contact_phone | string nullable | |
| capacity_kg | decimal(8,2) | capacidade do freezer |
| initial_estimate_kg | decimal(8,2) nullable | estimativa manual de consumo mensal, usada só enquanto não há histórico suficiente |
| status | enum('ativo','inativo','manutencao') | default 'ativo' |
| notes | text nullable | |
| timestamps | | |

### `point_movements`
| coluna | tipo | notas |
|---|---|---|
| id | bigint pk | |
| point_id | FK → points, cascade on delete | |
| type | enum('reposicao','retirada','ajuste') | |
| quantity_kg | decimal(8,2) | sempre positivo; sinal do efeito no estoque é derivado do `type` (reposição soma, retirada subtrai, ajuste pode somar ou subtrair — ver campo `adjustment_direction` abaixo) |
| adjustment_direction | enum('increase','decrease') nullable | usado só quando `type = 'ajuste'`, define se corrige estoque pra cima ou pra baixo |
| cost | decimal(8,2) nullable | custo associado (gelo + transporte na reposição) |
| revenue | decimal(8,2) nullable | receita/faturamento associado |
| occurred_at | date | data do evento |
| notes | text nullable | |
| timestamps | | |

Validação: `quantity_kg > 0`; `adjustment_direction` obrigatório quando `type = 'ajuste'`, proibido nos outros tipos.

### `users`
Tabela padrão do Breeze, sem alteração de schema. Um usuário admin via seeder.

## Cálculos derivados (sem colunas persistidas de estoque)

Implementados como métodos/accessors no model `Point` (ou em um `PointStockService` dedicado, para manter o model magro):

- **Estoque atual** = Σ reposição + Σ ajuste(increase) − Σ retirada − Σ ajuste(decrease), sobre todo o histórico do ponto.
- **% da capacidade** = estoque atual ÷ `capacity_kg`.
- **Média mensal de retirada** = média de `quantity_kg` de movimentações `retirada` nos últimos N meses com dados (N = `config('dashboards.stock_window_months')`, default 3). Se não houver movimentações suficientes no período, usa `initial_estimate_kg` como fallback.
- **Indicador "precisa reposição em breve"** = estoque atual < (percentual configurável, default 20%) da capacidade, OU estoque atual < média mensal de retirada restante estimada para o mês corrente.
- **Gasto do ponto** (período) = Σ `cost` das movimentações no intervalo de data selecionado.
- **Receita do ponto** (período) = Σ `revenue` das movimentações no intervalo.
- **Lucro do ponto** (período) = receita − gasto.

O período padrão de filtro é o mês corrente; a tela permite escolher mês/ano.

## Telas — Módulo 1: Dashboard de Pontos de Freezer

Rota: `dashboards.points.index` (`/dashboards/pontos`).

- **Cards de resumo** (topo): total de pontos ativos, estoque total somado (kg), receita total do mês, custo total do mês, lucro total do mês.
- **Grade de pontos**: nome, status (badge), estoque atual, % da capacidade (barra), média mensal, lucro do mês, badge de urgência de reposição.
- **Filtros**: status, mês/ano (aplicado aos cálculos de receita/custo/lucro e à média mensal).
- **Edição de ponto**: modal Livewire (`PointFormModal`) para criar/editar dados cadastrais, com validação e toast de sucesso/erro.
- **Detalhe do ponto**: painel/drawer Livewire (`PointDetail`) mostrando dados cadastrais + histórico de movimentações paginado + botão "lançar movimentação" que abre modal Livewire (`MovementFormModal`).

## Telas — Módulo 2: Visão Executiva

Rota: `dashboards.overview.index` (`/dashboards/geral`).

- KPIs consolidados: receita total, custo total, lucro total, margem (%), comparação mês atual vs. mês anterior.
- Gráfico de evolução mensal (Chart.js) — receita, custo e lucro dos últimos 12 meses.
- Ranking dos 5 pontos mais lucrativos e 5 menos lucrativos no período selecionado.

## Dashboards fora de escopo (registrados para pedido futuro)

Entradas comentadas em `config/dashboards.php` com uma linha de nota cada, sem controller/view:
1. Dashboard Financeiro (fluxo de caixa, fixo vs. variável).
2. Dashboard de Reposição/Logística (ranking por urgência, rota da semana).
3. Mapa de Pontos (Leaflet.js + OpenStreetMap).
4. Dashboard de Clientes/Parceiros.

## Seeders

`PointSeeder` + `PointMovementSeeder` (ou um `DatabaseSeeder` orquestrando factories): 8 a 12 pontos com tipos variados, 3 a 6 meses de histórico de movimentações cada, valores plausíveis de custo/receita/quantidade, garantindo que os dashboards já carreguem populados.

## Testes

Cobertura mínima via Pest/PHPUnit (o que já vier com o projeto):
- Cálculo de estoque atual (reposição, retirada, ajuste increase/decrease).
- Cálculo de média mensal com e sem histórico suficiente (fallback pra estimativa).
- Cálculo de receita/custo/lucro por período.
- Validação de formulário (quantidade negativa rejeitada, campos obrigatórios).

## Fora de escopo (explícito)

Integração de pagamento, app mobile, sistema de permissões/papéis complexo (fica só 1 admin), qualquer dashboard da lista "fora de escopo" acima.
