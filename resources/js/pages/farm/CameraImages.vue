<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { camera, show } from '@/routes/cows';

defineProps<{
    cow: {
        animalId: number;
        cowNumber: number | null;
        lifeNumber: string | null;
        name: string | null;
        label: string;
    };
    cows: { animalId: number; label: string }[];
    days: {
        value: string;
        label: string;
        photos: { url: string; time: string }[];
    }[];
    summary: {
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
}>();

function selectCow(animalId: unknown): void {
    if (typeof animalId !== 'number') {
        return;
    }

    router.get(camera.url(animalId));
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
    <Head :title="`Camerabeelden ${cow.label}`" />

    <div class="flex flex-1 flex-col gap-4 p-4">
        <header class="flex flex-wrap items-center gap-2">
            <Button as-child>
                <Link :href="show(cow.animalId)">
                    <ArrowLeft />
                    Terug
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
        </header>

        <section
            v-if="summary"
            class="rounded-lg border bg-background px-4 py-3"
        >
            <h2 class="mb-3 font-medium">{{ days[0]?.label }}</h2>
            <dl
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

        <section
            v-for="day in days"
            :key="day.value"
            class="rounded-lg border bg-background px-4 py-3"
        >
            <h2 class="mb-3 font-medium">{{ day.label }}</h2>
            <div class="flex flex-wrap gap-4">
                <figure
                    v-for="photo in day.photos"
                    :key="photo.url"
                    class="text-center"
                >
                    <img
                        :src="photo.url"
                        :alt="`${cow.label} om ${photo.time}`"
                        loading="lazy"
                        class="h-56 max-w-full object-contain"
                    />
                    <figcaption class="text-xs text-muted-foreground">
                        {{ photo.time }}
                    </figcaption>
                </figure>
            </div>
        </section>

        <p
            v-if="days.length === 0"
            class="rounded-lg border bg-background py-12 text-center text-muted-foreground"
        >
            Geen foto's voor deze koe.
        </p>
    </div>
</template>
