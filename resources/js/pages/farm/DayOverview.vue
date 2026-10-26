<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, FileText, TriangleAlert } from '@lucide/vue';
import { computed, ref } from 'vue';
import ColumnSettings from '@/components/farm/ColumnSettings.vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dayOverview, feedRules } from '@/routes';
import { show } from '@/routes/cows';

type Cow = {
    animalId: number;
    cowNumber: number | null;
    lifeNumber: string | null;
    daysInMilk: number | null;
    lactationNumber: number | null;
    milkProduction: number | null;
    bcs: number | null;
    bcsAgeDays: number | null;
    bcsIsOutdated: boolean;
    feedN1: number | null;
    feedN1Delta: number | null;
    feedN3: number | null;
    feedN3Delta: number | null;
    fpcm: number | null;
    fat: number | null;
    protein: number | null;
    lactose: number | null;
    urea: number | null;
    cellCount: number | null;
    sampleDate: string | null;
};

type SortKey = Exclude<keyof Cow, 'bcsIsOutdated'>;

type Column = {
    key: SortKey;
    label: string;
    align: 'left' | 'right';
    isMilkSample?: boolean;
};

const props = defineProps<{
    days: { value: string; label: string }[];
    selectedDay: string | null;
    selectedDayLabel: string | null;
    columns: { key: SortKey; visible: boolean }[];
    cows: Cow[];
}>();

const columnDefinitions: Column[] = [
    { key: 'cowNumber', label: 'Koenr.', align: 'right' },
    { key: 'lifeNumber', label: 'Levensnummer', align: 'left' },
    { key: 'daysInMilk', label: 'Lactatiedagen', align: 'right' },
    { key: 'lactationNumber', label: 'Lactatienr.', align: 'right' },
    { key: 'milkProduction', label: 'Melkproductie', align: 'right' },
    { key: 'bcs', label: 'BCS', align: 'left' },
    { key: 'feedN1', label: 'Krachtvoer n1', align: 'left' },
    { key: 'feedN3', label: 'Krachtvoer n3', align: 'left' },
    { key: 'fpcm', label: 'Meetmelk', align: 'right', isMilkSample: true },
    { key: 'fat', label: 'Vet %', align: 'right', isMilkSample: true },
    { key: 'protein', label: 'Eiwit %', align: 'right', isMilkSample: true },
    { key: 'lactose', label: 'Lactose %', align: 'right', isMilkSample: true },
    { key: 'urea', label: 'Ureum', align: 'right', isMilkSample: true },
    { key: 'cellCount', label: 'Celgetal', align: 'right', isMilkSample: true },
    {
        key: 'sampleDate',
        label: 'MPR datum',
        align: 'left',
        isMilkSample: true,
    },
];

const columnLabels = Object.fromEntries(
    columnDefinitions.map((column) => [column.key, column.label]),
);

const visibleColumns = computed(() =>
    props.columns
        .filter((column) => column.visible)
        .map(
            (column) =>
                columnDefinitions.find(
                    (definition) => definition.key === column.key,
                ) as Column,
        ),
);

// The cow number links to the cow; when it is hidden, the first visible column takes over.
const linkColumnKey = computed(
    () =>
        visibleColumns.value.find((column) => column.key === 'cowNumber')
            ?.key ?? visibleColumns.value[0]?.key,
);

const sortKey = ref<SortKey>('cowNumber');
const sortAscending = ref(true);

const sortedCows = computed(() => {
    const direction = sortAscending.value ? 1 : -1;

    return [...props.cows].sort((a, b) => {
        const left = a[sortKey.value];
        const right = b[sortKey.value];

        // Missing values always sink to the bottom, whatever the direction.
        if (left === null && right === null) {
            return 0;
        }

        if (left === null) {
            return 1;
        }

        if (right === null) {
            return -1;
        }

        return left < right ? -direction : left > right ? direction : 0;
    });
});

function sortBy(key: SortKey): void {
    if (sortKey.value === key) {
        sortAscending.value = !sortAscending.value;

        return;
    }

    sortKey.value = key;
    sortAscending.value = true;
}

function selectDay(day: unknown): void {
    if (typeof day !== 'string') {
        return;
    }

    router.get(
        dayOverview.url({ query: { dag: day } }),
        {},
        { preserveState: true },
    );
}

function decimal(value: number | null, digits: number, unit = ''): string {
    return value === null ? '' : `${value.toFixed(digits)}${unit}`;
}

function feed(amount: number | null, delta: number | null): string {
    if (amount === null) {
        return '';
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

function dutchDate(isoDate: string | null): string {
    if (isoDate === null) {
        return '';
    }

    const [year, month, day] = isoDate.split('-');

    return `${day}-${month}-${year}`;
}

function cell(cow: Cow, key: SortKey): string {
    switch (key) {
        case 'milkProduction':
        case 'fpcm':
            return decimal(cow[key], 1, ' kg');
        case 'fat':
        case 'protein':
        case 'lactose':
            return decimal(cow[key], 2);
        case 'urea':
            return decimal(cow.urea, 0);
        case 'sampleDate':
            return dutchDate(cow.sampleDate);
        default:
            return cow[key] === null ? '' : String(cow[key]);
    }
}
</script>

<template>
    <Head title="Dagoverzicht koeien" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <header class="flex flex-wrap items-center gap-2">
            <h1 class="mr-4 text-lg font-semibold">Dagoverzicht koeien</h1>

            <div class="flex items-center rounded-md border bg-background">
                <span class="border-r px-3 text-sm text-muted-foreground"
                    >Dag</span
                >
                <Select
                    :model-value="selectedDay ?? undefined"
                    @update:model-value="selectDay"
                >
                    <SelectTrigger
                        class="w-40 border-0 shadow-none"
                        aria-label="Dag"
                    >
                        <SelectValue placeholder="Geen data" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="day in days"
                            :key="day.value"
                            :value="day.value"
                        >
                            {{ day.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>

            <Button variant="outline" as-child>
                <Link :href="feedRules()">
                    <FileText />
                    Voerregels
                </Link>
            </Button>
            <ColumnSettings :columns="columns" :labels="columnLabels" />
        </header>

        <section
            class="flex min-h-0 flex-1 flex-col rounded-lg border bg-background"
        >
            <h2 class="px-4 pt-3 pb-2 font-medium">
                Lacterende koeien
                <template v-if="selectedDayLabel">
                    - {{ selectedDayLabel }}
                </template>
            </h2>

            <div class="max-h-[calc(100vh-14rem)] overflow-auto px-2 pb-2">
                <table class="w-full border-collapse text-sm tabular-nums">
                    <thead class="sticky top-0 z-10 bg-background">
                        <tr>
                            <th
                                v-for="column in visibleColumns"
                                :key="column.key"
                                scope="col"
                                :aria-sort="
                                    sortKey === column.key
                                        ? sortAscending
                                            ? 'ascending'
                                            : 'descending'
                                        : 'none'
                                "
                                class="border-b font-medium whitespace-nowrap"
                                :class="
                                    column.align === 'right'
                                        ? 'text-right'
                                        : 'text-left'
                                "
                            >
                                <button
                                    type="button"
                                    class="inline-flex w-full items-center gap-1 px-2 py-2 hover:text-foreground"
                                    :class="
                                        column.align === 'right'
                                            ? 'justify-end'
                                            : 'justify-start'
                                    "
                                    @click="sortBy(column.key)"
                                >
                                    {{ column.label }}
                                    <ArrowUp
                                        v-if="
                                            sortKey === column.key &&
                                            sortAscending
                                        "
                                        class="size-3.5"
                                    />
                                    <ArrowDown
                                        v-else-if="sortKey === column.key"
                                        class="size-3.5"
                                    />
                                </button>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="cow in sortedCows"
                            :key="cow.animalId"
                            class="hover:bg-muted/50"
                        >
                            <td
                                v-for="column in visibleColumns"
                                :key="column.key"
                                class="border-b px-2 py-1.5 whitespace-nowrap"
                                :class="[
                                    column.align === 'right'
                                        ? 'text-right'
                                        : 'text-left',
                                    column.isMilkSample
                                        ? 'bg-blue-50 dark:bg-blue-950/40'
                                        : '',
                                ]"
                            >
                                <Link
                                    v-if="column.key === linkColumnKey"
                                    :href="show(cow.animalId)"
                                    class="text-blue-600 hover:underline dark:text-blue-400"
                                >
                                    {{
                                        column.key === 'cowNumber'
                                            ? (cow.cowNumber ?? '–')
                                            : cell(cow, column.key)
                                    }}
                                </Link>
                                <span
                                    v-else-if="
                                        column.key === 'bcs' &&
                                        cow.bcsIsOutdated
                                    "
                                    class="inline-flex items-center gap-1 text-orange-600 dark:text-orange-400"
                                    :title="`${cow.bcsAgeDays} dagen oud, niet actueel`"
                                >
                                    <TriangleAlert class="size-3.5" />
                                    {{ decimal(cow.bcs, 2) }}
                                </span>
                                <template v-else-if="column.key === 'bcs'">
                                    {{ decimal(cow.bcs, 2) }}
                                </template>
                                <span
                                    v-else-if="column.key === 'feedN1'"
                                    :class="feedClass(cow.feedN1Delta)"
                                >
                                    {{ feed(cow.feedN1, cow.feedN1Delta) }}
                                </span>
                                <span
                                    v-else-if="column.key === 'feedN3'"
                                    :class="feedClass(cow.feedN3Delta)"
                                >
                                    {{ feed(cow.feedN3, cow.feedN3Delta) }}
                                </span>
                                <template v-else>
                                    {{ cell(cow, column.key) }}
                                </template>
                            </td>
                        </tr>
                        <tr v-if="cows.length === 0">
                            <td
                                :colspan="visibleColumns.length"
                                class="px-2 py-8 text-center text-muted-foreground"
                            >
                                Geen lacterende koeien voor deze dag.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
