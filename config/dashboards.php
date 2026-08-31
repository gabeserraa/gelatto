<?php

return [
    'stock_window_months' => env('DASHBOARD_STOCK_WINDOW_MONTHS', 3),
    'low_stock_threshold_percent' => env('DASHBOARD_LOW_STOCK_THRESHOLD_PERCENT', 20),
    'critical_stockout_days' => env('DASHBOARD_CRITICAL_STOCKOUT_DAYS', 1),
    'low_stock_stockout_days' => env('DASHBOARD_LOW_STOCK_STOCKOUT_DAYS', 3),

    'point_types' => ['Balada', 'Mercado', 'Evento', 'Bar', 'Restaurante', 'Outro'],

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
        [
            'key' => 'inventory',
            'name' => 'Estoque',
            'icon' => 'archive-box',
            'route' => 'dashboards.inventory.index',
            'order' => 3,
        ],
        // Fora de escopo — registrar aqui quando forem implementados:
        // 'financeiro'  — fluxo de caixa, custos fixos x variáveis
        // 'reposicao'   — ranking de urgência de reposição, rota semanal
        // 'mapa'        — pontos no mapa (Leaflet.js + OpenStreetMap)
        // 'clientes'    — dados de contato/relacionamento por parceiro
    ],
];
