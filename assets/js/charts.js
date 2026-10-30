/* نمودارهای داشبورد و گزارش - Chart.js */
(function () {
    'use strict';
    if (!window.Chart) return;
    const css = n => getComputedStyle(document.documentElement).getPropertyValue(n).trim();
    const charts = [];
    function theme() {
        Chart.defaults.font.family = 'Vazirmatn, Tahoma';
        Chart.defaults.color = css('--mm-muted');
        Chart.defaults.borderColor = css('--mm-border');
    }
    theme();
    const fmt = v => MM.money(v);
    const short = v => { const a = Math.abs(v); return a >= 1e9 ? MM.fa((v / 1e9).toFixed(1)) + ' میلیارد' : a >= 1e6 ? MM.fa(Math.round(v / 1e6)) + ' م' : a >= 1e3 ? MM.fa(Math.round(v / 1e3)) + ' هزار' : MM.fa(v); };

    MM.profitChart = function (canvas, data) {
        const ctx = canvas.getContext('2d');
        const g = ctx.createLinearGradient(0, 0, 0, canvas.height || 300);
        g.addColorStop(0, 'rgba(16,185,129,.35)'); g.addColorStop(1, 'rgba(16,185,129,0)');
        const c = new Chart(ctx, {
            data: {
                labels: data.map(d => d.label.replace(/\d+/g, m => MM.fa(m))),
                datasets: [
                    { type: 'bar', label: 'دریافتی', data: data.map(d => d.income), backgroundColor: 'rgba(59,130,246,.75)', borderRadius: 7, maxBarThickness: 26, order: 2 },
                    { type: 'bar', label: 'هزینه', data: data.map(d => d.expense), backgroundColor: 'rgba(249,115,22,.55)', borderRadius: 7, maxBarThickness: 26, order: 3 },
                    { type: 'line', label: 'سود خالص', data: data.map(d => d.profit), borderColor: '#10b981', backgroundColor: g, fill: true, tension: .38, pointRadius: 3.5, pointBackgroundColor: '#10b981', borderWidth: 2.5, order: 1 },
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom', labels: { usePointStyle: true, padding: 16 } },
                    tooltip: { rtl: true, textDirection: 'rtl', padding: 12, callbacks: { label: i => ' ' + i.dataset.label + ': ' + fmt(i.raw) + ' تومان' } }
                },
                scales: { y: { ticks: { callback: short }, grid: { drawTicks: false } }, x: { grid: { display: false } } }
            }
        });
        charts.push(c);
        return c;
    };

    MM.donutChart = function (canvas, rows) {
        const c = new Chart(canvas, {
            type: 'doughnut',
            data: { labels: rows.map(r => r.name), datasets: [{ data: rows.map(r => +r.income), backgroundColor: rows.map(r => r.color), borderWidth: 0, hoverOffset: 6 }] },
            options: { cutout: '68%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } }, tooltip: { rtl: true, callbacks: { label: i => ' ' + i.label + ': ' + fmt(i.raw) + ' تومان' } } } }
        });
        charts.push(c);
    };

    document.addEventListener('mm:theme', () => { theme(); charts.forEach(c => c.update()); });
})();
