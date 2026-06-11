<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
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
import type { Order, Paginated } from '@/types';

defineProps<{
    orders: Paginated<Order>;
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
                                {{ order.user?.name ?? 'Unknown' }}
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
                                            :href="edit(order.id)"
                                            aria-label="Edit order"
                                        >
                                            <Pencil />
                                        </Link>
                                    </Button>
                                    <Button
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
                                No orders have been created.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <CrudPagination :links="orders.links" />
    </div>
</template>
