<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { PaginationLink } from '@/types';

defineProps<{
    links: PaginationLink[];
}>();
</script>

<template>
    <nav v-if="links.length > 3" class="flex flex-wrap gap-2">
        <Link
            v-for="link in links"
            :key="link.label"
            :href="link.url ?? '#'"
            preserve-scroll
            class="rounded-md border px-3 py-1.5 text-sm transition-colors"
            :class="{
                'pointer-events-none opacity-50': !link.url,
                'border-primary bg-primary text-primary-foreground':
                    link.active,
                'border-border bg-background hover:bg-accent': !link.active,
            }"
        >
            <span v-html="link.label" />
        </Link>
    </nav>
</template>
