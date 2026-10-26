<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { LogOut } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { logout } from '@/routes';
</script>

<template>
    <Button variant="outline" class="ml-auto" as-child>
        <Link :href="logout()" method="post" as="button">
            <LogOut />
            Uitloggen
        </Link>
    </Button>
</template>
