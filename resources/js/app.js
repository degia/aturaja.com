import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import './bootstrap';
import { renderTrend, renderDoughnut, renderNetWorth } from './charts';

window.Alpine = Alpine;
window.Chart = Chart;
window.AturjaCharts = { renderTrend, renderDoughnut, renderNetWorth };

Alpine.start();