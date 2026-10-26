<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Star } from '@lucide/vue';
import { computed } from 'vue';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { show } from '@/routes/cows';

const page = usePage();
const favorites = computed(() => page.props.favoriteCows);

const { isCurrentUrl } = useCurrentUrl();
</script>

<template>
    <SidebarGroup class="px-2 py-0">
        <SidebarGroupLabel>Favorieten</SidebarGroupLabel>
        <SidebarMenu>
            <SidebarMenuItem v-for="cow in favorites" :key="cow.animalId">
                <SidebarMenuButton
                    as-child
                    :is-active="isCurrentUrl(show(cow.animalId))"
                    :tooltip="cow.label"
                >
                    <Link :href="show(cow.animalId)">
                        <Star class="fill-amber-400 text-amber-400" />
                        <span>{{ cow.label }}</span>
                    </Link>
                </SidebarMenuButton>
            </SidebarMenuItem>
            <p
                v-if="favorites.length === 0"
                class="px-2 py-1 text-xs text-muted-foreground group-data-[collapsible=icon]:hidden"
            >
                Markeer koeien met de ster om ze hier te zien.
            </p>
        </SidebarMenu>
    </SidebarGroup>
</template>
