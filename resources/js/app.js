import Chart from 'chart.js/auto';
import './bootstrap';
import { renderTrend, renderTrendLine, renderDoughnut, renderNetWorth } from './charts';

// Catatan: Livewire 3 sudah menyertakan & menginisialisasi Alpine sendiri.
// Jangan import/start Alpine secara manual di sini — dapat menyebabkan
// double-initialization sehingga event $dispatch tidak sampai ke komponen.
window.Chart = Chart;
window.AturjaCharts = { renderTrend, renderTrendLine, renderDoughnut, renderNetWorth };
