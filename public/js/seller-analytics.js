document.addEventListener('DOMContentLoaded', () => {

    // ===============================
    // Общие настройки для ВСЕХ графиков
    // ===============================
    const baseOptions = {
        responsive: true,
        maintainAspectRatio: false,
        resizeDelay: 200,
        layout: {
            padding: { left: 0, right: 0, top: 0, bottom: 0 }
        },
        plugins: {
            legend: {
                position: 'bottom',
                labels: {
                    boxWidth: 10,
                    padding: 8,
                    font: { size: 10 }
                }
            }
        }
    };

    // ===============================
    // ПОНЧИК
    // ===============================
    const donutCtx = document.getElementById('donutChart');
    if (donutCtx) {
        new Chart(donutCtx, {
            type: 'doughnut',
            data: {
                labels: window.donutLabels ?? ['Просмотры', 'Избранное', 'Корзины'],
                datasets: [{
                    data: window.donutData ?? [],
                    backgroundColor: ['#6366f1', '#10b981', '#fbbf24'],
                    hoverOffset: 4
                }]
            },
            options: {
                ...baseOptions,
                cutout: '60%',
            }
        });
    }

    // ===============================
    // BAR — ТОП товаров (горизонтальный)
    // ===============================
    const barCtx = document.getElementById('barChart');

    if (barCtx && window.topTitles) {

        const barChart = new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: window.topTitles,
                datasets: [{
                    label: 'Просмотры',
                    data: window.topViews,
                    backgroundColor: '#6366f1',
                    borderRadius: 6,
                    maxBarThickness: 26
                }]
            },
            options: {
                ...baseOptions,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true,
                        grid: { display: false },
                        ticks: { font: { size: 9 }, maxTicksLimit: 4 }
                    },
                    y: {
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                }
            }
        });

        // Клик по строке — перейти на товар
        barCtx.onclick = function (evt) {
            const pts = barChart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
            if (!pts.length) return;

            const index = pts[0].index;
            const url = window.topUrls[index];
            if (url) window.location.href = url;
        };
    }

    // ===============================
    // TIMELINE — Активность по дням
    // ===============================
    const tlCtx = document.getElementById('timelineChart');

    if (tlCtx && window.tlLabels) {

        const timelineChart = new Chart(tlCtx, {
            type: 'line',
            data: {
                labels: window.tlLabels,
                datasets: [
                    {
                        label: 'Просмотры',
                        data: window.tlViews,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99,102,241,0.18)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.35
                    },
                    {
                        label: 'Избранное',
                        data: window.tlFavs,
                        borderColor: '#10b981',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.35
                    },
                    {
                        label: 'Корзины',
                        data: window.tlCarts,
                        borderColor: '#fbbf24',
                        borderWidth: 2,
                        fill: false,
                        tension: 0.35
                    }
                ]
            },
            options: {
                ...baseOptions,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { font: { size: 10 } }
                    },
                    x: {
                        ticks: { font: { size: 9 }, maxRotation: 0, minRotation: 0 }
                    }
                }
            }
        });

        // Переход по точке графика
        tlCtx.onclick = function (evt) {
            const pts = timelineChart.getElementsAtEventForMode(evt, 'nearest', { intersect: true }, true);
            if (!pts.length) return;

            const idx = pts[0].index;
            const date = window.tlLabels[idx];

            if (window.timelineDayUrlBase) {
                window.location.href = window.timelineDayUrlBase.replace('___DATE___', date);
            }
        };
    }

});
