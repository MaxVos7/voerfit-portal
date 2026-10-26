<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ArrowDown, ArrowUp, Columns3, GripVertical } from '@lucide/vue';
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

const dragIndex = ref<number | null>(null);
const overIndex = ref<number | null>(null);

function moveTo(from: number, to: number): void {
    if (from === to || to < 0 || to >= draft.value.length) {
        return;
    }

    const next = [...draft.value];
    const [column] = next.splice(from, 1);
    next.splice(to, 0, column);
    draft.value = next;
}

function onDragStart(event: DragEvent, index: number): void {
    dragIndex.value = index;

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        // Firefox only starts a drag when some data is set.
        event.dataTransfer.setData('text/plain', String(index));
    }
}

function onDragOver(event: DragEvent, index: number): void {
    if (dragIndex.value === null) {
        return;
    }

    event.preventDefault();
    overIndex.value = index;
}

function onDrop(index: number): void {
    if (dragIndex.value !== null) {
        moveTo(dragIndex.value, index);
    }

    onDragEnd();
}

function onDragEnd(): void {
    dragIndex.value = null;
    overIndex.value = null;
}

function dropIndicator(index: number): string {
    if (dragIndex.value === null || overIndex.value !== index) {
        return '';
    }

    return dragIndex.value < index
        ? 'border-b-2 border-b-primary'
        : 'border-t-2 border-t-primary';
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
                    Kies welke kolommen je ziet en sleep ze in de gewenste
                    volgorde.
                </DialogDescription>
            </DialogHeader>

            <ul class="divide-y rounded-md border">
                <li
                    v-for="(column, index) in draft"
                    :key="column.key"
                    class="flex items-center gap-3 px-3 py-1.5"
                    :class="[
                        dropIndicator(index),
                        { 'opacity-50': dragIndex === index },
                    ]"
                    draggable="true"
                    @dragstart="onDragStart($event, index)"
                    @dragover="onDragOver($event, index)"
                    @drop.prevent="onDrop(index)"
                    @dragend="onDragEnd"
                >
                    <GripVertical
                        class="size-4 shrink-0 cursor-grab text-muted-foreground active:cursor-grabbing"
                        aria-hidden="true"
                    />
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
                        @click="moveTo(index, index - 1)"
                    >
                        <ArrowUp />
                    </Button>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="size-7"
                        :disabled="index === draft.length - 1"
                        :aria-label="`${labels[column.key]} omlaag`"
                        @click="moveTo(index, index + 1)"
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
