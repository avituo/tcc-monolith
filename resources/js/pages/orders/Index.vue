<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Trash2 } from '@lucide/vue';
import {
    create,
    destroy,
    edit,
    index,
    show,
} from '@/actions/App/Http/Controllers/OrderController';
import CrudPagination from '@/components/CrudPagination.vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Order, Paginated } from '@/types';

defineProps<{
    orders: Paginated<Order>;
    filters: {
        name: string | null;
        status: Order['status'] | null;
        per_page: number;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Orders', href: index() }],
    },
});

function remove(order: Order): void {
    if (window.confirm(`Delete "${order.name}"?`)) {
        router.delete(destroy(order.id));
    }
}

function money(value: string): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(Number(value));
}

function statusVariant(
    status: Order['status'],
): 'default' | 'secondary' | 'destructive' {
    if (status === 'paid') {
        return 'default';
    }

    return status === 'cancelled' ? 'destructive' : 'secondary';
}
</script>

<template>
    <Head title="Orders" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                title="Orders"
                description="Manage orders and their product line items."
            />
            <Button as-child>
                <Link :href="create()">
                    <Plus />
                    New order
                </Link>
            </Button>
        </div>

        <Form
            v-bind="index.form()"
            class="grid gap-4 rounded-xl border bg-card p-4 md:grid-cols-[minmax(0,1fr)_12rem_8rem_auto] md:items-end"
            #default="{ processing }"
        >
            <div class="grid gap-2">
                <Label for="order-name">Name</Label>
                <Input
                    id="order-name"
                    name="name"
                    type="search"
                    :default-value="filters.name ?? ''"
                    placeholder="Search orders"
                />
            </div>

            <div class="grid gap-2">
                <Label for="order-status">Status</Label>
                <select
                    id="order-status"
                    name="status"
                    :value="filters.status ?? ''"
                    class="h-9 rounded-md border border-input bg-background px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 dark:bg-input/30"
                >
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="paid">Paid</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>

            <div class="grid gap-2">
                <Label for="order-page-size">Per page</Label>
                <select
                    id="order-page-size"
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
                            <th class="px-4 py-3 font-medium">Order</th>
                            <th class="px-4 py-3 font-medium">Customer</th>
                            <th class="px-4 py-3 font-medium">Items</th>
                            <th class="px-4 py-3 font-medium">Total</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="order in orders.data"
                            :key="order.id"
                            class="border-t"
                        >
                            <td class="px-4 py-3">
                                <Link
                                    :href="show(order.id)"
                                    class="font-medium hover:underline"
                                >
                                    {{ order.name }}
                                </Link>
                                <p class="text-xs text-muted-foreground">
                                    #{{ order.id }}
                                </p>
                            </td>
                            <td class="px-4 py-3">
                                {{ order.user_name_snapshot ?? 'Unknown' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ order.products_count ?? 0 }}
                            </td>
                            <td class="px-4 py-3 font-medium">
                                {{ money(order.total_price) }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge :variant="statusVariant(order.status)">
                                    {{ order.status }}
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
                                            :href="show(order.id)"
                                            aria-label="View order"
                                        >
                                            <Eye />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="order.status === 'pending'"
                                        variant="outline"
                                        size="icon-sm"
                                        as-child
                                    >
                                        <Link
                                            :href="edit(order.id)"
                                            aria-label="Edit order"
                                        >
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button
                                        v-if="order.status === 'pending'"
                                        variant="destructive"
                                        size="icon-sm"
                                        aria-label="Delete order"
                                        @click="remove(order)"
                                    >
                                        <Trash2 />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="orders.data.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-12 text-center text-muted-foreground"
                            >
                                No orders match the selected filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <CrudPagination :links="orders.links" />
    </div>
</template>
