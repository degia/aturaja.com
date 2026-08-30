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