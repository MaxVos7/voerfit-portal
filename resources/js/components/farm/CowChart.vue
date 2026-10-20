<script setup lang="ts">
import { CustomChart, LineChart, ScatterChart } from 'echarts/charts';
import {
    DataZoomComponent,
    GridComponent,
    LegendComponent,
    MarkLineComponent,
    TooltipComponent,
} from 'echarts/components';
import type { EChartsCoreOption } from 'echarts/core';
import type { CustomSeriesRenderItem } from 'echarts';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { computed } from 'vue';
import VChart from 'vue-echarts';
import { useAppearance } from '@/composables/useAppearance';
import type { ChartBcs, ChartDay, ChartMilkSample } from '@/types';

use([
    CanvasRenderer,
    CustomChart,
    DataZoomComponent,
    GridComponent,
    LegendComponent,
    LineChart,
    MarkLineComponent,
    ScatterChart,
    TooltipComponent,
]);

const props = defineProps<{
    days: ChartDay[];
    bcs: ChartBcs[];
    milkSamples: ChartMilkSample[];
}>();

const { resolvedAppearance } = useAppearance();

// Same palette as the Grafana panel.
const MILK = '#3274D9';
const FEED_N1 = '#E0C000';
const FEED_N3 = '#B877D9';
const MORE = 'rgba(61,154,80,0.45)';
const LESS = 'rgba(196,22,42,0.38)';
const BCS = '#FF9830';
const MPR = '#0E6E5B';

const MONTHS = [
    'januari',
    'februari',
    'maart',
    'april',
    'mei',
    'juni',
    'juli',
    'augustus',
    'september',
    'oktober',
    'november',
    'december',
];

/** Dates are plain YYYY-MM-DD days; plot them at local midnight. */
function timestamp(date: string): number {
    const [year, month, day] = date.split('-').map(Number);

    return new Date(year, month - 1, day).getTime();
}

const option = computed<EChartsCoreOption>(() => {
    const isDark = resolvedAppearance.value === 'dark';
    const text = isDark ? '#d4d4d8' : '#3f3f46';
    const gridLine = isDark ? '#3f3f46' : '#e4e4e7';

    const times = props.days.map((day) => timestamp(day.date));
    const series = (value: (day: ChartDay) => number | null) =>
        props.days.map((day, index) => [times[index], value(day)]);
    const samplesByDate = new Map(
        props.milkSamples.map((sample) => [sample.date, sample]),
    );

    // Per feed: a transparent base plus a coloured band stacked on top of it fills
    // the gap between the original and the applied amount; the line shows the applied amount.
    const feedSeries = (
        key: 'n1' | 'n3',
        colour: string,
        applied: (day: ChartDay) => number,
        original: (day: ChartDay) => number,
    ) => {
        const band = (
            name: string,
            stack: string,
            value: (day: ChartDay) => number,
            fill: string,
        ) => ({
            name,
            type: 'line',
            yAxisIndex: 1,
            stack,
            data: series(value),
            lineStyle: { width: 0 },
            showSymbol: false,
            symbol: 'none',
            areaStyle: { color: fill, opacity: 1 },
            itemStyle: { color: fill },
            silent: true,
            tooltip: { show: false },
            z: 1,
        });
        const lower = (day: ChartDay) => Math.min(applied(day), original(day));

        return [
            band(`_${key}_base_more`, `${key}-more`, lower, 'transparent'),
            band(
                'Meer dan origineel',
                `${key}-more`,
                (day) => Math.max(applied(day) - original(day), 0),
                MORE,
            ),
            band(`_${key}_base_less`, `${key}-less`, lower, 'transparent'),
            band(
                'Minder dan origineel',
                `${key}-less`,
                (day) => Math.max(original(day) - applied(day), 0),
                LESS,
            ),
            {
                name: `Krachtvoer ${key}`,
                type: 'line',
                yAxisIndex: 1,
                data: series(applied),
                showSymbol: false,
                lineStyle: { color: colour, width: 2 },
                itemStyle: { color: colour },
                z: 3,
            },
        ];
    };

    const bcsWhiskers: CustomSeriesRenderItem = (params, api) => {
        const x = api.coord([api.value(0), api.value(1)])[0];
        const high = api.coord([api.value(0), api.value(3)])[1];
        const low = api.coord([api.value(0), api.value(2)])[1];
        const halfWidth = 3;
        const style = { stroke: BCS, lineWidth: 1 };

        return {
            type: 'group',
            children: [
                {
                    type: 'line',
                    shape: { x1: x, y1: high, x2: x, y2: low },
                    style,
                },
                {
                    type: 'line',
                    shape: {
                        x1: x - halfWidth,
                        y1: high,
                        x2: x + halfWidth,
                        y2: high,
                    },
                    style,
                },
                {
                    type: 'line',
                    shape: {
                        x1: x - halfWidth,
                        y1: low,
                        x2: x + halfWidth,
                        y2: low,
                    },
                    style,
                },
            ],
        };
    };

    const tooltip = (params: unknown): string => {
        const first = (params as { dataIndex: number }[])[0];
        const day = props.days[first.dataIndex];

        if (!day) {
            return '';
        }

        const [year, month, date] = day.date.split('-').map(Number);
        const muted = (value: string) =>
            `<span style="color:#888">${value}</span>`;
        let html = `<b>${date} ${MONTHS[month - 1]} ${year}</b>`;

        if (day.daysInMilk !== null) {
            html += ` ${muted(`&nbsp;lactatiedag ${day.daysInMilk}`)}`;
        }

        html += '<br/>';

        if (day.milkProduction !== null) {
            html += `Melkproductie: <b>${day.milkProduction.toFixed(1)} kg</b><br/>`;
        }

        // the applied amount plus its deviation from the original, as in the day overview
        const feed = (label: string, applied: number, original: number) => {
            let deviation = '';

            if (Math.abs(applied - original) >= 0.005) {
                const colour = applied > original ? '#3d9a50' : '#c4162a';
                const sign = applied > original ? '+' : '-';
                deviation = ` <span style="color:${colour}">(${sign}${Math.abs(applied - original).toFixed(2)})</span>`;
            }

            return `${label}: <b>${applied.toFixed(2)} kg</b>${deviation}<br/>`;
        };

        html += feed('Krachtvoer n1', day.feedN1, day.feedN1Original);
        html += feed('Krachtvoer n3', day.feedN3, day.feedN3Original);

        const restFeed = day.restFeedN1 + day.restFeedN3;

        if (restFeed > 0) {
            html += `Restvoer: <b>${restFeed.toFixed(2)} kg</b><br/>`;
        }

        const bcs = props.bcs.find((point) => point.date === day.date);

        if (bcs) {
            html += `BCS: <b>${bcs.bcs.toFixed(2)}</b>`;
            html += bcs.sem !== null ? ` &plusmn; ${bcs.sem.toFixed(2)}` : '';
            html += '<br/>';
        }

        const counts = [];

        if (day.milkings !== null) {
            counts.push(`${day.milkings} melkingen`);
        }

        if (day.refusals !== null) {
            counts.push(`${day.refusals} weigeringen`);
        }

        if (counts.length) {
            html += `<span style="color:#888;font-size:11px">${counts.join(' &middot; ')}</span>`;
        }

        const sample = samplesByDate.get(day.date);

        if (sample) {
            html += `<div style="margin-top:4px;border-top:1px solid #ccc;padding-top:3px"><b style="color:${MPR}">MPR-monster</b><br/>`;
            html +=
                sample.milk !== null
                    ? `Melk: <b>${sample.milk.toFixed(1)} kg</b><br/>`
                    : '';
            html +=
                sample.fpcm !== null
                    ? `Meetmelk: <b>${sample.fpcm.toFixed(1)} kg</b><br/>`
                    : '';
            html +=
                sample.fat !== null
                    ? `Vet: <b>${sample.fat.toFixed(2)}%</b><br/>`
                    : '';
            html +=
                sample.protein !== null
                    ? `Eiwit: <b>${sample.protein.toFixed(2)}%</b><br/>`
                    : '';
            html +=
                sample.urea !== null
                    ? `Ureum: <b>${sample.urea.toFixed(0)}</b><br/>`
                    : '';
            html +=
                sample.cellCount !== null
                    ? `Celgetal: <b>${sample.cellCount}</b>`
                    : '';
            html += '</div>';
        }

        return html;
    };

    return {
        backgroundColor: 'transparent',
        textStyle: { color: text },
        tooltip: {
            trigger: 'axis',
            axisPointer: { type: 'line' },
            formatter: tooltip,
        },
        legend: {
            bottom: 0,
            textStyle: { color: text },
            data: [
                'Melkproductie',
                'Krachtvoer n1',
                'Krachtvoer n3',
                'Meer dan origineel',
                'Minder dan origineel',
                'Restvoer n1',
                'Restvoer n3',
                'BCS',
            ],
        },
        grid: { left: 60, right: 120, bottom: 110, top: 30 },
        dataZoom: [
            { type: 'inside' },
            { type: 'slider', bottom: 40, height: 20 },
        ],
        xAxis: {
            type: 'time',
            axisLabel: {
                color: text,
                formatter: (value: number) => {
                    const date = new Date(value);

                    return `${String(date.getDate()).padStart(2, '0')}-${String(date.getMonth() + 1).padStart(2, '0')}`;
                },
            },
        },
        yAxis: [
            {
                type: 'value',
                name: 'Melk (L)',
                position: 'left',
                min: 0,
                nameRotate: 90,
                nameLocation: 'middle',
                nameGap: 45,
                axisLabel: { color: text },
                splitLine: { lineStyle: { color: gridLine } },
            },
            {
                type: 'value',
                name: 'Voer (kg)',
                position: 'right',
                min: 0,
                max: 13,
                nameRotate: -90,
                nameLocation: 'middle',
                nameGap: 45,
                axisLabel: { color: text },
                splitLine: { show: false },
            },
            {
                type: 'value',
                name: 'BCS',
                position: 'right',
                offset: 60,
                min: 2,
                max: 5.5,
                nameRotate: -90,
                nameLocation: 'middle',
                nameGap: 40,
                axisLabel: {
                    color: text,
                    formatter: (value: number) => value.toFixed(1),
                },
                splitLine: { show: false },
            },
        ],
        series: [
            {
                name: 'Melkproductie',
                type: 'line',
                yAxisIndex: 0,
                data: series((day) => day.milkProduction),
                itemStyle: { color: MILK },
                lineStyle: { color: MILK, width: 2 },
                showSymbol: false,
                markLine: {
                    symbol: 'none',
                    silent: false,
                    animation: false,
                    lineStyle: {
                        type: 'dashed',
                        color: MPR,
                        width: 1.5,
                        opacity: 0.9,
                    },
                    label: {
                        show: true,
                        formatter: 'MPR',
                        position: 'insideEndTop',
                        color: MPR,
                        fontSize: 10,
                    },
                    data: props.milkSamples.map((sample) => ({
                        xAxis: timestamp(sample.date),
                    })),
                },
            },
            ...feedSeries(
                'n1',
                FEED_N1,
                (day) => day.feedN1,
                (day) => day.feedN1Original,
            ),
            ...feedSeries(
                'n3',
                FEED_N3,
                (day) => day.feedN3,
                (day) => day.feedN3Original,
            ),
            {
                name: 'Restvoer n1',
                type: 'line',
                yAxisIndex: 1,
                stack: 'rest',
                data: series((day) => day.restFeedN1),
                itemStyle: { color: 'rgba(220,50,50,0.7)' },
                areaStyle: { color: 'rgba(220,50,50,0.5)' },
                lineStyle: { width: 0 },
                showSymbol: false,
            },
            {
                name: 'Restvoer n3',
                type: 'line',
                yAxisIndex: 1,
                stack: 'rest',
                data: series((day) => day.restFeedN3),
                itemStyle: { color: 'rgba(255,165,0,0.7)' },
                areaStyle: { color: 'rgba(255,165,0,0.5)' },
                lineStyle: { width: 0 },
                showSymbol: false,
            },
            {
                name: 'BCS',
                type: 'scatter',
                yAxisIndex: 2,
                data: props.bcs.map((point) => [
                    timestamp(point.date),
                    point.bcs,
                ]),
                symbolSize: 5,
                itemStyle: { color: BCS },
                z: 10,
            },
            {
                name: '_bcs_whiskers',
                type: 'custom',
                yAxisIndex: 2,
                data: props.bcs.map((point) => [
                    timestamp(point.date),
                    point.bcs,
                    point.bcs - (point.sem ?? 0),
                    point.bcs + (point.sem ?? 0),
                ]),
                encode: { x: 0, y: [1, 2, 3] },
                renderItem: bcsWhiskers,
                tooltip: { show: false },
                legendHoverLink: false,
                z: 10,
            },
        ],
    };
});
</script>

<template>
    <!--
        vue-echarts injects an unlayered `height: 100%` on its element, which beats
        Tailwind's layered utilities, so the height has to live on a wrapper.
    -->
    <div class="h-[520px] w-full">
        <VChart :option="option" autoresize />
    </div>
</template>
