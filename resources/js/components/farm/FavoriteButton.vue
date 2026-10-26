<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { Star } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { destroy, store } from '@/routes/cows/favorite';

const props = defineProps<{
    animalId: number;
    withLabel?: boolean;
}>();

const page = usePage();

const isFavorite = computed(() =>
    page.props.favoriteCows.some((cow) => cow.animalId === props.animalId),
);

function toggle(): void {
    const options = { preserveScroll: true, preserveState: true };

    if (isFavorite.value) {
        router.delete(destroy.url(props.animalId), options);

        return;
    }

    router.post(store.url(props.animalId), {}, options);
}
</script>

<template>
    <Button
        :variant="withLabel ? 'outline' : 'ghost'"
        :size="withLabel ? 'default' : 'icon'"
        :class="withLabel ? '' : 'size-7'"
        :aria-pressed="isFavorite"
        :aria-label="isFavorite ? 'Verwijder uit favorieten' : 'Maak favoriet'"
        :title="isFavorite ? 'Verwijder uit favorieten' : 'Maak favoriet'"
        @click="toggle"
    >
        <Star
            :class="
                isFavorite
                    ? 'fill-amber-400 text-amber-400'
                    : 'text-muted-foreground'
            "
        />
        <template v-if="withLabel">Favoriet</template>
    </Button>
</template>
