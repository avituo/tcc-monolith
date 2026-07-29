<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Paginated, Product } from '@/types';

defineProps<{
    products: Paginated<Product>;
    filters: {
        name: string | null;
        is_active: boolean | null;
        per_page: number;
    };
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

        <Form
            v-bind="index.form()"
            class="grid gap-4 rounded-xl border bg-card p-4 md:grid-cols-[minmax(0,1fr)_12rem_8rem_auto] md:items-end"
            #default="{ processing }"
        >
            <div class="grid gap-2">
                <Label for="product-name">Name</Label>
                <Input
                    id="product-name"
                    name="name"
                    type="search"
                    :default-value="filters.name ?? ''"
                    placeholder="Search products"
                />
            </div>

            <div class="grid gap-2">
                <Label for="product-status">Status</Label>
                <select
                    id="product-status"
                    name="is_active"
                    :value="
                        filters.is_active === null
                            ? ''
                            : filters.is_active
                              ? '1'
                              : '0'
                    "
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 dark:bg-input/30"
                >
                    <option value="">All statuses</option>
                    <option value="1">Active</option>
                    <option value="0">Inactive</option>
                </select>
            </div>

            <div class="grid gap-2">
                <Label for="product-page-size">Per page</Label>
                <select
                    id="product-page-size"
                    name="per_page"
                    :value="filters.per_page"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 dark:bg-input/30"
                >
                    <option :value="10">10</option>
                    <option :value="25">25</option>
                    <option :value="50">50</option>
                </select>
            </div>

            <div class="flex gap-2">
                <Button type="submit" :disabled="processing">
                    {{ processing ? 'Filtering...' : 'Filter' }}
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="index()">Clear</Link>
                </Button>
            </div>
        </Form>

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
                                No products match the selected filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <CrudPagination :links="products.links" />
    </div>
</template>
