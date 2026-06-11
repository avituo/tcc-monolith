<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import {
    create,
    destroy,
    edit,
    index,
    show,
} from '@/actions/App/Http/Controllers/ProductController';
import CrudPagination from '@/components/CrudPagination.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Paginated, Product } from '@/types';

defineProps<{
    products: Paginated<Product>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Products', href: index() }],
    },
});

function remove(product: Product): void {
    if (window.confirm(`Delete "${product.name}"?`)) {
        router.delete(destroy(product.id));
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
    <Head title="Products" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Products"
                description="Manage catalog details, pricing, and inventory."
            />
            <Button as-child>
                <Link :href="create()">
                    <Plus />
                    New product
                </Link>
            </Button>
        </div>

        <div class="overflow-hidden rounded-xl border">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-muted/50 text-left">
                        <tr>
                            <th class="px-4 py-3 font-medium">Product</th>
                            <th class="px-4 py-3 font-medium">SKU</th>
                            <th class="px-4 py-3 font-medium">Price</th>
                            <th class="px-4 py-3 font-medium">Stock</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="product in products.data"
                            :key="product.id"
                            class="border-t"
                        >
                            <td class="px-4 py-3">
                                <Link
                                    :href="show(product.id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ product.name }}
                                </Link>
                                <p
                                    class="max-w-72 truncate text-muted-foreground"
                                >
                                    {{ product.description }}
                                </p>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs">
                                {{ product.sku }}
                            </td>
                            <td class="px-4 py-3">
                                {{ money(product.price) }}
                                <span
                                    v-if="Number(product.discount) > 0"
                                    class="block text-xs text-emerald-600"
                                >
                                    -{{ money(product.discount) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ product.quantity }}</td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="
                                        product.is_active
                                            ? 'default'
                                            : 'secondary'
                                    "
                                >
                                    {{
                                        product.is_active
                                            ? 'Active'
                                            : 'Inactive'
                                    }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <Button
                                        variant="outline"
                                        size="icon-sm"
                                        as-child
                                    >
                                        <Link
                                            :href="edit(product.id)"
                                            aria-label="Edit product"
                                        >
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        size="icon-sm"
                                        aria-label="Delete product"
                                        @click="remove(product)"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="products.data.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-12 text-center text-muted-foreground"
                            >
                                No products have been created.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <CrudPagination :links="products.links" />
    </div>
</template>
