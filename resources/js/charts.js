const PALETTE = {
    green: '#16A34A',
    greenPale: '#D7E9DB',
    grid: '#DCE8DF',
    tick: '#6B8074',
};

export function renderTrend(canvas, { labels, income, expense }) {
    return new Chart(canvas, {
        type: 'bar',
        data: {
            labels,
            datasets: [
                { label: 'Pemasukan', data: income, backgroundColor: PALETTE.green, borderRadius: 6, barPercentage: 0.55 },
                { label: 'Pengeluaran', data: expense, backgroundColor: PALETTE.greenPale, borderRadius: 6, barPercentage: 0.55 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 900, easing: 'easeOutQuart' },
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: PALETTE.tick, font: { size: 11 } } },
                y: { grid: { color: PALETTE.grid }, ticks: { color: PALETTE.tick, font: { size: 11 }, callback: (v) => `${Math.round(v / 1000)}jt` } },
            },
        },
    });
}

export function renderTrendLine(canvas, { labels, income, expense }) {
    return new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label: 'Pemasukan', data: income, borderColor: PALETTE.green, backgroundColor: 'rgba(22,163,74,0.10)', fill: true, tension: 0.4, borderWidth: 2, pointRadius: 2 },
                { label: 'Pengeluaran', data: expense, borderColor: '#DC2626', backgroundColor: 'rgba(220,38,38,0.08)', fill: true, tension: 0.4, borderWidth: 2, pointRadius: 2 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            animation: { duration: 900, easing: 'easeOutQuart' },
            plugins: { legend: { display: false } },
            scales: {
                x: { grid: { display: false }, ticks: { color: PALETTE.tick, font: { size: 11 }, maxRotation: 0, autoSkip: true } },
                y: { grid: { color: PALETTE.grid }, ticks: { color: PALETTE.tick, font: { size: 11 }, callback: (v) => compact(v) } },
            },
        },
    });
}

function compact(value) {
    if (value >= 1000000) return `${(value / 1000000).toFixed(value >= 10000000 ? 0 : 1)}jt`;
    if (value >= 1000) return `${Math.round(value / 1000)}rb`;
    return Math.round(value).toString();
}

export function renderNetWorth(canvas, { labels, assets, liabilities, netWorth }) {
    return new Chart(canvas, {
        type: 'line',
        data: {
            labels,
            datasets: [
                { label: 'Aset', data: assets, borderColor: PALETTE.green, backgroundColor: 'rgba(22,163,74,0.08)', fill: true, tension: 0.4, borderWidth: 2, pointRadius: 2 },
                { label: 'Kewajiban', data: liabilities, borderColor: '#D97706', backgroundColor: 'rgba(217,119,6,0.06)', fill: true, tension: 0.4, borderWidth: 2, pointRadius: 2 },
                { label: 'Net Worth', data: netWorth, borderColor: '#15803D', backgroundColor: 'transparent', tension: 0.4, borderWidth: 2.5, pointRadius: 2 },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: { mode: 'index', intersect: false },
            animation: { duration: 900, easing: 'easeOutQuart' },
            plugins: { legend: { position: 'bottom', labels: { color: PALETTE.tick, usePointStyle: true, boxWidth: 8 } } },
            scales: {
                x: { grid: { display: false }, ticks: { color: PALETTE.tick, font: { size: 11 } } },
                y: { grid: { color: PALETTE.grid }, ticks: { color: PALETTE.tick, font: { size: 11 }, callback: (v) => `${Math.round(v / 1000)}jt` } },
            },
        },
    });
}

export function renderDoughnut(canvas, { labels, values, colors }) {
    return new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels,
            datasets: [{ data: values, backgroundColor: colors, borderWidth: 0, hoverOffset: 6 }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '70%',
            animation: { duration: 900, easing: 'easeOutQuart' },
            plugins: { legend: { display: false } },
        },
    });
}