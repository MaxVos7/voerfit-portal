<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Columns3 } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { destroy, update } from '@/routes/day-overview/columns';

type ColumnSetting = { key: string; visible: boolean };

const props = defineProps<{
    columns: ColumnSetting[];
    labels: Record<string, string>;
}>();

const open = ref(false);
const draft = ref<ColumnSetting[]>([]);
const processing = ref(false);

const hasVisibleColumn = computed(() =>
    draft.value.some((column) => column.visible),
);

watch(open, (isOpen) => {
    if (isOpen) {
        draft.value = props.columns.map((column) => ({ ...column }));
    }
});

function move(index: number, offset: -1 | 1): void {
    const target = index + offset;

    if (target < 0 || target >= draft.value.length) {
        return;
    }

    const next = [...draft.value];
    [next[index], next[target]] = [next[target], next[index]];
    draft.value = next;
}

function setVisible(index: number, visible: boolean | 'indeterminate'): void {
    draft.value[index].visible = visible === true;
}

function finish(): void {
    processing.value = false;
    open.value = false;
}

function save(): void {
    router.put(
        update.url(),
        { columns: draft.value },
        {
            preserveScroll: true,
            onStart: () => (processing.value = true),
            onSuccess: finish,
            onError: () => (processing.value = false),
        },
    );
}

function restoreDefaults(): void {
    router.delete(destroy.url(), {
        preserveScroll: true,
        onStart: () => (processing.value = true),
        onSuccess: finish,
        onError: () => (processing.value = false),
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogTrigger as-child>
            <Button variant="outline">
                <Columns3 />
                Kolommen
            </Button>
        </DialogTrigger>
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Kolommen</DialogTitle>
                <DialogDescription>
                    Kies welke kolommen je ziet en in welke volgorde.
                </DialogDescription>
            </DialogHeader>

            <ul class="divide-y rounded-md border">
                <li
                    v-for="(column, index) in draft"
                    :key="column.key"
                    class="flex items-center gap-3 px-3 py-1.5"
                >
                    <Checkbox
                        :id="`column-${column.key}`"
                        :model-value="column.visible"
                        :aria-label="`${labels[column.key]} tonen`"
                        @update:model-value="setVisible(index, $event)"
                    />
                    <label
                        :for="`column-${column.key}`"
                        class="flex-1 text-sm"
                        :class="{ 'text-muted-foreground': !column.visible }"
                    >
                        {{ labels[column.key] }}
                    </label>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-7"
                        :disabled="index === 0"
                        :aria-label="`${labels[column.key]} omhoog`"
                        @click="move(index, -1)"
                    >
                        <ArrowUp />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-7"
                        :disabled="index === draft.length - 1"
                        :aria-label="`${labels[column.key]} omlaag`"
                        @click="move(index, 1)"
                    >
                        <ArrowDown />
                    </Button>
                </li>
            </ul>

            <p v-if="!hasVisibleColumn" class="text-sm text-destructive">
                Laat minstens één kolom zichtbaar.
            </p>

            <DialogFooter class="gap-2 sm:justify-between">
                <Button
                    variant="ghost"
                    :disabled="processing"
                    @click="restoreDefaults"
                >
                    Standaard herstellen
                </Button>
                <Button
                    :disabled="processing || !hasVisibleColumn"
                    @click="save"
                >
                    Opslaan
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
