<script setup>
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { Chart, LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip } from 'chart.js';

Chart.register(LineController, LineElement, PointElement, LinearScale, CategoryScale, Filler, Tooltip);

const props = defineProps({
    labels: { type: Array, required: true },
    values: { type: Array, required: true },
    color: { type: String, default: '#4f46e5' },
    seriesLabel: { type: String, default: 'Leads' },
    height: { type: Number, default: 240 },
});

const canvas = ref(null);
let chart;

function draw() {
    chart?.destroy();
    const ctx = canvas.value.getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, props.height);
    gradient.addColorStop(0, props.color + '38');
    gradient.addColorStop(1, props.color + '00');

    chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: props.labels,
            datasets: [{
                label: props.seriesLabel, data: props.values, borderColor: props.color, backgroundColor: gradient, fill: true,
                cubicInterpolationMode: 'monotone', borderWidth: 2.5, pointRadius: 0, pointHoverRadius: 5, pointHoverBackgroundColor: props.color,
                pointHoverBorderColor: '#fff', pointHoverBorderWidth: 2,
            }],
        },
        options: {
            responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
            plugins: { legend: { display: false }, tooltip: { backgroundColor: '#0f172a', padding: 10, cornerRadius: 8, displayColors: false } },
            scales: {
                x: { grid: { display: false }, border: { display: false }, ticks: { color: '#94a3b8', maxTicksLimit: 7, maxRotation: 0, font: { size: 11 } } },
                y: { beginAtZero: true, border: { display: false }, grid: { color: '#f1f5f9' }, ticks: { color: '#94a3b8', precision: 0, maxTicksLimit: 5, font: { size: 11 } } },
            },
        },
    });
}

onMounted(draw);
watch(() => [props.labels, props.values, props.color], draw);
onBeforeUnmount(() => chart?.destroy());
</script>

<template>
    <div :style="{ height: height + 'px' }"><canvas ref="canvas" role="img" :aria-label="seriesLabel + ' chart'" /></div>
</template>
