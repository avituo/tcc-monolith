<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import {
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/ProductController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Product } from '@/types';

const props = defineProps<{
    product: Product;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Products', href: index() },
            { title: 'Details', href: '#' },
        ],
    },
});

function remove(): void {
    if (window.confirm(`Delete "${props.product.name}"?`)) {
        router.delete(destroy(props.product.id));
    }
}

function money(value: string): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(Number(value));
}
</script>

<template>
    <Head :title="product.name" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                :title="product.name"
                :description="`SKU ${product.sku}`"
            />
            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="edit(product.id)">
                        <Pencil />
                        Edit
                    </Link>
                </Button>
                <Button variant="destructive" @click="remove">
                    <Trash2 />
                    Delete
                </Button>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[20rem_1fr]">
            <div
                class="flex aspect-square items-center justify-center overflow-hidden rounded-xl border bg-muted"
            >
                <img
                    v-if="product.image"
                    :src="product.image"
                    :alt="product.name"
                    class="size-full object-cover"
                />
                <span v-else class="text-sm text-muted-foreground">
                    No image
                </span>
            </div>

            <div class="grid gap-6 rounded-xl border bg-card p-6">
                <div class="flex flex-wrap gap-2">
                    <Badge
                        :variant="product.is_active ? 'default' : 'secondary'"
                    >
                        {{ product.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                    <Badge variant="outline"
                        >{{ product.quantity }} in stock</Badge
                    >
                </div>

                <dl class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-muted-foreground">Price</dt>
                        <dd class="text-lg font-semibold">
                            {{ money(product.price) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted-foreground">Discount</dt>
                        <dd class="text-lg font-semibold">
                            {{ money(product.discount) }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted-foreground">Slug</dt>
                        <dd class="font-mono text-sm">{{ product.slug }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm text-muted-foreground">
                            Final price
                        </dt>
                        <dd class="text-lg font-semibold">
                            {{
                                money(
                                    String(
                                        Math.max(
                                            Number(product.price) -
                                                Number(product.discount),
                                            0,
                                        ),
                                    ),
                                )
                            }}
                        </dd>
                    </div>
                </dl>

                <div>
                    <h2 class="mb-2 font-medium">Description</h2>
                    <p
                        class="text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ product.description }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
