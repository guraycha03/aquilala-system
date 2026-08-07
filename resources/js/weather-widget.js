/**
 * Weather Widget Controller
 * 
 * Handles Chart.js rendering for climate charts, hourly temperature trends,
 * and other interactive weather visualizations across all dashboards.
 */
import { Chart, registerables } from 'chart.js';
Chart.register(...registerables);

/**
 * Initialize all climate charts on the page.
 */
export function initClimateCharts() {
    document.querySelectorAll('canvas[data-labels]').forEach(canvas => {
        const ctx = canvas.getContext('2d');
        if (!ctx) return;

        const labels = JSON.parse(canvas.dataset.labels || '[]');
        const rainfall = JSON.parse(canvas.dataset.rainfall || '[]');
        const yieldData = JSON.parse(canvas.dataset.yield || '[]');
        const compact = canvas.dataset.compact === 'true';

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Rainfall (mm)',
                        data: rainfall,
                        backgroundColor: 'rgba(2, 132, 199, 0.6)',
                        borderColor: 'rgba(2, 132, 199, 1)',
                        borderWidth: 1,
                        borderRadius: 3,
                        order: 2,
                        yAxisID: 'y',
                    },
                    {
                        label: 'Yield (tons/ha)',
                        data: yieldData,
                        type: 'line',
                        borderColor: '#16a34a',
                        backgroundColor: 'rgba(22, 163, 74, 0.1)',
                        borderWidth: 2,
                        pointBackgroundColor: '#16a34a',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: compact ? 2 : 4,
                        pointHoverRadius: compact ? 4 : 6,
                        fill: true,
                        tension: 0.3,
                        order: 1,
                        yAxisID: 'y1',
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                plugins: {
                    legend: {
                        display: !compact,
                        position: 'top',
                        labels: {
                            boxWidth: 12,
                            padding: 12,
                            font: { size: 11 },
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e2922',
                        titleFont: { size: 13 },
                        bodyFont: { size: 12 },
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function(context) {
                                let label = context.dataset.label || '';
                                let value = context.parsed.y;
                                if (label.includes('Rainfall')) {
                                    return label + ': ' + value.toFixed(0) + ' mm';
                                }
                                return label + ': ' + value.toFixed(1) + ' tons/ha';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: {
                            font: { size: compact ? 9 : 11 },
                            maxRotation: 0,
                        }
                    },
                    y: {
                        beginAtZero: true,
                        position: 'left',
                        title: {
                            display: !compact,
                            text: 'Rainfall (mm)',
                            font: { size: 11 },
                        },
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { font: { size: 10 } }
                    },
                    y1: {
                        beginAtZero: true,
                        position: 'right',
                        title: {
                            display: !compact,
                            text: 'Yield (tons/ha)',
                            font: { size: 11 },
                        },
                        grid: { display: false },
                        ticks: { font: { size: 10 } }
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeOutQuart',
                }
            }
        });
    });
}

/**
 * Initialize hourly temperature trend chart.
 */
export function initHourlyChart(canvasId, hourlyData) {
    const canvas = document.getElementById(canvasId);
    if (!canvas) return;

    const ctx = canvas.getContext('2d');
    if (!ctx) return;

    const hours = hourlyData.map(d => d.time);
    const temps = hourlyData.map(d => d.temperature);
    const rainProbs = hourlyData.map(d => d.rain_probability);

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: hours,
            datasets: [
                {
                    label: 'Temperature (°C)',
                    data: temps,
                    borderColor: '#ef4444',
                    backgroundColor: 'rgba(239, 68, 68, 0.05)',
                    borderWidth: 2,
                    pointBackgroundColor: temps.map(t => t > 32 ? '#dc2626' : t > 28 ? '#f97316' : '#16a34a'),
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true,
                    tension: 0.4,
                    yAxisID: 'y',
                },
                {
                    label: 'Rain Probability (%)',
                    data: rainProbs,
                    borderColor: '#0284c7',
                    backgroundColor: 'rgba(2, 132, 199, 0.1)',
                    borderWidth: 2,
                    borderDash: [5, 3],
                    pointBackgroundColor: '#0284c7',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 1,
                    pointRadius: 2,
                    pointHoverRadius: 4,
                    fill: false,
                    tension: 0.3,
                    yAxisID: 'y1',
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index',
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        padding: 12,
                        font: { size: 11 },
                        usePointStyle: true,
                    }
                },
                tooltip: {
                    backgroundColor: '#1e2922',
                    titleFont: { size: 12 },
                    bodyFont: { size: 11 },
                    padding: 8,
                    cornerRadius: 6,
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: {
                        font: { size: 10 },
                        maxTicksLimit: 12,
                    }
                },
                y: {
                    beginAtZero: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: '°C',
                        font: { size: 10 },
                    },
                    grid: { color: 'rgba(0,0,0,0.04)' },
                    ticks: { font: { size: 10 } }
                },
                y1: {
                    beginAtZero: true,
                    position: 'right',
                    max: 100,
                    title: {
                        display: true,
                        text: '%',
                        font: { size: 10 },
                    },
                    grid: { display: false },
                    ticks: { font: { size: 10 } }
                }
            },
            animation: {
                duration: 1200,
                easing: 'easeOutQuart',
            }
        }
    });
}

/**
 * Auto-refresh weather data display (simulated).
 */
export function startWeatherAutoRefresh(intervalMs = 60000) {
    // Update the "last updated" timestamp
    const updateTimestamp = () => {
        document.querySelectorAll('.weather-updated-at').forEach(el => {
            el.textContent = new Date().toLocaleTimeString('en-PH', {
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        });
    };
    updateTimestamp();
    setInterval(updateTimestamp, intervalMs);
}

// Auto-initialize on DOMContentLoaded
document.addEventListener('DOMContentLoaded', () => {
    initClimateCharts();
    startWeatherAutoRefresh();
});
