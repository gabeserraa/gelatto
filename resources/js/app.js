import './bootstrap';

import Chart from 'chart.js/auto';
Chart.defaults.font.family = "'Inter', system-ui, sans-serif";
Chart.defaults.color = '#64748b';
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.boxWidth = 8;
Chart.defaults.plugins.legend.labels.font = { size: 11 };
Chart.defaults.plugins.legend.position = 'bottom';
window.Chart = Chart;
window.chartPalette = ['#06b6d4', '#22d3ee', '#67e8f9', '#0e7490', '#0891b2', '#a5f3fc', '#155e75', '#cffafe', '#164e63', '#38bdf8'];
