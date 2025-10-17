// assets/js/stats.js

// Global chart instance and state
let timeChart = null;
let chartTimeRange = '7days'; // Default range: '7days', 'month', 'all'
let allTrabajosData = []; // To store the full sorted dataset

/**
 * Sets the time range for the chart and re-renders it.
 * @param {string} range - The new time range ('7days', 'month', 'all').
 */
function setChartTimeRange(range) {
    chartTimeRange = range;

    // Update active state of buttons
    const rangeMap = {
        '7days': 'Últimos 7 Días',
        'month': 'Último Mes',
        'all': 'Todo'
    };
    document.querySelectorAll('#chart-time-range button').forEach(btn => {
        if (btn.textContent === rangeMap[range]) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });

    // Re-render the chart with the new range
    renderRevenueChart();
}


/**
 * Initializes the entire statistics tab.
 * @param {Array} trabajos - The full list of job objects.
 */
function initStats(trabajos) {
    const statsContent = document.getElementById('stats-content');
    if (!statsContent) return;

    if (!trabajos || trabajos.length === 0) {
        statsContent.innerHTML = '<div class="initial-view text-center p-5"><i class="bi bi-bar-chart-line text-muted" style="font-size: 4rem;"></i><h4 class="mt-3">No hay datos suficientes</h4><p class="lead text-muted">Añade trabajos para ver las estadísticas.</p></div>';
        return;
    }
    
    // Store the full dataset, sorted by date
    allTrabajosData = [...trabajos].sort((a, b) => new Date(a.fecha_creacion) - new Date(b.fecha_creacion));

    // Render all components of the stats tab
    setChartTimeRange(chartTimeRange); // Renders the chart with the current range
    renderStatsCards(allTrabajosData);
    renderTimeline(allTrabajosData);
}

/**
 * Updates the stats tab if it's active. Called on global data refresh.
 * @param {Array} trabajos - The updated full list of job objects.
 */
function updateStats(trabajos) {
    const statsTab = document.getElementById('stats-trabajos-tab');
    if (statsTab && statsTab.classList.contains('active')) {
        initStats(trabajos);
    }
}

/**
 * Renders the main revenue and expenses line chart based on the current time range.
 */
function renderRevenueChart() {
    const ctx = document.getElementById('revenueChart');
    if (!ctx) return;
    
    const chartTitle = document.getElementById('revenue-chart-title');
    const endDate = new Date();
    endDate.setHours(23, 59, 59, 999);
    let startDate = new Date();
    let isAllTime = false;

    // 1. Determine date range and filter data
    switch (chartTimeRange) {
        case 'month':
            startDate.setDate(startDate.getDate() - 30);
            if(chartTitle) chartTitle.textContent = 'Actividad del Último Mes';
            break;
        case 'all':
            isAllTime = true;
            if(chartTitle) chartTitle.textContent = 'Actividad Histórica';
            break;
        case '7days':
        default:
            startDate.setDate(startDate.getDate() - 7);
             if(chartTitle) chartTitle.textContent = 'Actividad de los Últimos 7 Días';
            break;
    }
    startDate.setHours(0, 0, 0, 0);

    const trabajosInRange = isAllTime ? allTrabajosData : allTrabajosData.filter(job => {
        const jobDate = new Date(job.fecha_creacion);
        return jobDate >= startDate && jobDate <= endDate;
    });

    // 2. Aggregate filtered data by day
    const dataByDay = trabajosInRange.reduce((acc, job) => {
        const day = new Date(job.fecha_creacion).toISOString().slice(0, 10); // YYYY-MM-DD
        if (!acc[day]) {
            acc[day] = { profit: 0, expense: 0 };
        }
        acc[day].profit += parseFloat(job.net_profit);
        acc[day].expense += parseFloat(job.gastos);
        return acc;
    }, {});

    // 3. Generate labels for the chart's X-axis
    let labels = []; // Raw YYYY-MM-DD labels
    if (isAllTime) {
        // For 'all time', only show days with actual data to avoid a huge chart
        labels = Object.keys(dataByDay).sort();
    } else {
        // For fixed ranges, show all days in the period to visualize gaps
        for (let d = new Date(startDate); d <= endDate; d.setDate(d.getDate() + 1)) {
            labels.push(d.toISOString().slice(0, 10));
        }
    }

    // 4. Map aggregated data to the labels array
    const profitData = labels.map(day => dataByDay[day]?.profit || 0);
    const expenseData = labels.map(day => dataByDay[day]?.expense || 0);
    const netData = labels.map(day => (dataByDay[day]?.profit || 0) - (dataByDay[day]?.expense || 0));
    
    const displayLabels = labels.map(label => new Date(label + 'T00:00:00').toLocaleDateString('es-AR', { month: 'short', day: 'numeric' }));

    // 5. Destroy old chart instance and create a new one
    if (timeChart) {
        timeChart.destroy();
    }

    timeChart = new Chart(ctx.getContext('2d'), {
        type: 'line',
        data: {
            labels: displayLabels,
            datasets: [
                { label: 'Beneficio', data: profitData, borderColor: 'rgba(75, 192, 192, 1)', backgroundColor: 'rgba(75, 192, 192, 0.2)', fill: true, tension: 0.2 },
                { label: 'Gastos', data: expenseData, borderColor: 'rgba(255, 99, 132, 1)', backgroundColor: 'rgba(255, 99, 132, 0.2)', fill: true, tension: 0.2 },
                { label: 'Balance', data: netData, borderColor: 'rgba(54, 162, 235, 1)', backgroundColor: 'rgba(54, 162, 235, 0.2)', fill: true, tension: 0.2 }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { callback: value => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', notation: 'compact' }).format(value) } }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        title: (tooltipItems) => {
                            const originalLabel = labels[tooltipItems[0].dataIndex];
                            return new Date(originalLabel + 'T00:00:00').toLocaleDateString('es-AR', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
                        },
                        label: (context) => {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            if (context.parsed.y !== null) label += new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(context.parsed.y);
                            return label;
                        }
                    }
                }
            }
        }
    });
}


/**
 * Renders the four main summary statistic cards.
 * @param {Array} trabajos - The full list of job objects.
 */
function renderStatsCards(trabajos) {
    const totalProfit = trabajos.reduce((sum, job) => sum + parseFloat(job.net_profit), 0);
    const totalExpense = trabajos.reduce((sum, job) => sum + parseFloat(job.gastos), 0);
    const totalNet = totalProfit - totalExpense;
    const avgProfit = trabajos.length > 0 ? totalProfit / trabajos.length : 0;
    
    const jobTypes = trabajos.reduce((acc, job) => {
        const type = job.tipo_trabajo_nombre || 'Sin tipo';
        if(!acc[type]) acc[type] = { count: 0, profit: 0 };
        acc[type].count++;
        acc[type].profit += parseFloat(job.net_profit);
        return acc;
    }, {});
    
    const mostFrequentType = Object.keys(jobTypes).sort((a,b) => jobTypes[b].count - jobTypes[a].count)[0] || 'N/A';
    const mostProfitableType = Object.keys(jobTypes).sort((a,b) => jobTypes[b].profit - jobTypes[a].profit)[0] || 'N/A';

    const formatCurrency = (value) => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value || 0);

    document.getElementById('stats-total-net').textContent = formatCurrency(totalNet);
    document.getElementById('stats-avg-profit').textContent = formatCurrency(avgProfit);
    document.getElementById('stats-most-frequent').textContent = mostFrequentType;
    document.getElementById('stats-most-profitable').textContent = mostProfitableType;

}

/**
 * Renders the timeline of recent jobs.
 * @param {Array} trabajos - The full list of job objects (should be pre-sorted by date).
 */
function renderTimeline(trabajos) {
    const timelineContainer = document.getElementById('timeline-container');
    if (!timelineContainer) return;

    const trabajosByDay = trabajos.reduce((acc, job) => {
        const date = new Date(job.fecha_creacion);
        const dayKey = date.toISOString().slice(0, 10);
        
        if(!acc[dayKey]) {
            acc[dayKey] = {
                displayDate: date.toLocaleDateString('es-AR', { year: 'numeric', month: 'long', day: 'numeric' }),
                jobs: []
            };
        }
        acc[dayKey].jobs.push(job);
        return acc;
    }, {});

    const sortedDays = Object.keys(trabajosByDay).sort().reverse();

    if (sortedDays.length === 0) {
        timelineContainer.innerHTML = '<p class="text-center text-muted">No hay trabajos para mostrar en la línea de tiempo.</p>';
        return;
    }
    
    let timelineHtml = '<ul class="timeline">';
    const formatCurrency = (value) => new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(value || 0);

    sortedDays.forEach(dayKey => {
        const dayData = trabajosByDay[dayKey];
        timelineHtml += `<li><div class="timeline-date">${dayData.displayDate}</div><div class="timeline-content">`;
        dayData.jobs.reverse().forEach(job => {
            const profit = parseFloat(job.net_profit);
            const expense = parseFloat(job.gastos);
            const balance = profit - expense;
            const balanceColor = balance >= 0 ? 'text-success' : 'text-danger';
            
            timelineHtml += `
                <div class="card mb-2">
                    <div class="card-body p-2">
                        <div class="d-flex justify-content-between">
                             <h6 class="card-title mb-1">${job.tipo_trabajo_nombre} - ${job.auto_nombre || job.cliente_patente || 'General'}</h6>
                             <strong class="${balanceColor}">${formatCurrency(balance)}</strong>
                        </div>
                        <small class="text-muted">Beneficio: ${formatCurrency(profit)} / Gasto: ${formatCurrency(expense)}</small>
                    </div>
                </div>
            `;
        });
        timelineHtml += `</div></li>`;
    });
    
    timelineHtml += '</ul>';
    timelineContainer.innerHTML = timelineHtml;
}
