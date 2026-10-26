<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Camera } from '@lucide/vue';
import CowChart from '@/components/farm/CowChart.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dayOverview } from '@/routes';
import { camera, show } from '@/routes/cows';
import type { ChartBcs, ChartDay, ChartMilkSample } from '@/types';

const props = defineProps<{
    cow: {
        animalId: number;
        cowNumber: number | null;
        lifeNumber: string | null;
        name: string | null;
        label: string;
    };
    cows: { animalId: number; label: string }[];
    lactations: { value: string; label: string }[];
    selectedLactation: string;
    summary: {
        date: string;
        dateLabel: string;
        daysInMilk: number | null;
        lactationNumber: number | null;
        milkProduction: number | null;
        fat: number | null;
        protein: number | null;
        urea: number | null;
        feedN1: number | null;
        feedN1Delta: number | null;
        feedN3: number | null;
        feedN3Delta: number | null;
    } | null;
    chart: {
        days: ChartDay[];
        bcs: ChartBcs[];
        milkSamples: ChartMilkSample[];
    };
}>();

function selectCow(animalId: unknown): void {
    if (typeof animalId !== 'number') {
        return;
    }

    router.get(show.url(animalId));
}

function selectLactation(lactation: unknown): void {
    if (typeof lactation !== 'string') {
        return;
    }

    router.get(
        show.url(props.cow.animalId, { query: { lactatie: lactation } }),
        {},
        { preserveScroll: true },
    );
}

function decimal(value: number | null, digits: number, unit = ''): string {
    return value === null ? '–' : `${value.toFixed(digits)}${unit}`;
}

function feed(amount: number | null, delta: number | null): string {
    if (amount === null) {
        return '–';
    }

    if (delta === null) {
        return `${amount.toFixed(2)} kg`;
    }

    const sign = delta > 0 ? '+' : '-';

    return `${amount.toFixed(2)} kg (${sign}${Math.abs(delta).toFixed(2)})`;
}

function feedClass(delta: number | null): string {
    if (delta === null) {
        return '';
    }

    return delta > 0
        ? 'text-green-600 dark:text-green-400'
        : 'text-red-600 dark:text-red-400';
}
</script>

<template>
    <Head :title="cow.label" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <header class="flex flex-wrap items-center gap-2">
            <Button as-child>
                <Link :href="dayOverview()">
                    <ArrowLeft />
                    Terug
                </Link>
            </Button>
            <Button as-child>
                <Link :href="camera(cow.animalId)">
                    <Camera />
                    Foto's
                </Link>
            </Button>

            <div class="ml-2 flex items-center rounded-md border bg-background">
                <span class="border-r px-3 text-sm text-muted-foreground"
                    >Koe</span
                >
                <Select
                    :model-value="cow.animalId"
                    @update:model-value="selectCow"
                >
                    <SelectTrigger
                        class="w-72 border-0 shadow-none"
                        aria-label="Koe"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in cows"
                            :key="option.animalId"
                            :value="option.animalId"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <div class="flex items-center rounded-md border bg-background">
                <span class="border-r px-3 text-sm text-muted-foreground"
                    >Lactatie</span
                >
                <Select
                    :model-value="selectedLactation"
                    @update:model-value="selectLactation"
                >
                    <SelectTrigger
                        class="w-80 border-0 shadow-none"
                        aria-label="Lactatie"
                    >
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="lactation in lactations"
                            :key="lactation.value"
                            :value="lactation.value"
                        >
                            {{ lactation.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
        </header>

        <section class="rounded-lg border bg-background px-4 py-3">
            <h2 class="mb-3 font-medium">
                {{ summary?.dateLabel ?? 'Geen melkdag in deze periode' }}
            </h2>
            <dl
                v-if="summary"
                class="grid grid-cols-2 gap-4 tabular-nums sm:grid-cols-4 lg:grid-cols-8"
            >
                <div>
                    <dt class="text-sm text-muted-foreground">Lactatiedagen</dt>
                    <dd class="text-xl">{{ summary.daysInMilk ?? '–' }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">Lactatienr.</dt>
                    <dd class="text-xl">
                        {{ summary.lactationNumber ?? '–' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">Melkproductie</dt>
                    <dd class="text-xl">
                        {{ decimal(summary.milkProduction, 1, ' kg') }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">Vet %</dt>
                    <dd class="text-xl">{{ decimal(summary.fat, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">Eiwit %</dt>
                    <dd class="text-xl">{{ decimal(summary.protein, 2) }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">Ureum</dt>
                    <dd class="text-xl">{{ decimal(summary.urea, 0) }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">Krachtvoer n1</dt>
                    <dd class="text-xl" :class="feedClass(summary.feedN1Delta)">
                        {{ feed(summary.feedN1, summary.feedN1Delta) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">Krachtvoer n3</dt>
                    <dd class="text-xl" :class="feedClass(summary.feedN3Delta)">
                        {{ feed(summary.feedN3, summary.feedN3Delta) }}
                    </dd>
                </div>
            </dl>
        </section>

        <section class="rounded-lg border bg-background px-4 py-3">
            <h2 class="mb-2 font-medium">{{ cow.label }}</h2>
            <CowChart
                v-if="chart.days.length"
                :days="chart.days"
                :bcs="chart.bcs"
                :milk-samples="chart.milkSamples"
            />
            <p v-else class="py-12 text-center text-muted-foreground">
                Geen gegevens in deze periode.
            </p>
        </section>
    </div>
</template>
