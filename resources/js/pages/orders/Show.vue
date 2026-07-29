<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CircleCheckBig, CircleX, Pencil, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import {
    destroy,
    edit,
    index,
} from '@/actions/App/Http/Controllers/OrderController';
import UpdateOrderStatusController from '@/actions/App/Http/Controllers/UpdateOrderStatusController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Order } from '@/types';

const props = defineProps<{
    order: Order;
}>();
const updatingStatus = ref<Order['status'] | null>(null);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Orders', href: index() },
            { title: 'Details', href: '#' },
        ],
    },
});

function remove(): void {
    if (window.confirm(`Delete "${props.order.name}"?`)) {
        router.delete(destroy(props.order.id));
    }
}

function updateStatus(status: 'paid' | 'cancelled'): void {
    const action = status === 'paid' ? 'mark as paid' : 'cancel';

    if (!window.confirm(`Are you sure you want to ${action} this order?`)) {
        return;
    }

    router.patch(
        UpdateOrderStatusController(props.order.id),
        { status },
        {
            preserveScroll: true,
            onStart: () => {
                updatingStatus.value = status;
            },
            onFinish: () => {
                updatingStatus.value = null;
            },
        },
    );
}

function money(value: string | number): string {
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
    <Head :title="order.name" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading :title="order.name" :description="`Order #${order.id}`" />
            <div v-if="order.status === 'pending'" class="flex flex-wrap gap-2">
                <Button
                    :disabled="updatingStatus !== null"
                    @click="updateStatus('paid')"
                >
                    <CircleCheckBig />
                    {{
                        updatingStatus === 'paid' ? 'Updating...' : 'Mark paid'
                    }}
                </Button>
                <Button
                    variant="destructive"
                    :disabled="updatingStatus !== null"
                    @click="updateStatus('cancelled')"
                >
                    <CircleX />
                    {{
                        updatingStatus === 'cancelled'
                            ? 'Cancelling...'
                            : 'Cancel order'
                    }}
                </Button>
                <Button variant="outline" as-child>
                    <Link :href="edit(order.id)">
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

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border bg-card p-5">
                <p class="text-sm text-muted-foreground">Status</p>
                <Badge class="mt-2" :variant="statusVariant(order.status)">
                    {{ order.status }}
                </Badge>
            </div>
            <div class="rounded-xl border bg-card p-5">
                <p class="text-sm text-muted-foreground">Customer</p>
                <p class="mt-1 font-medium">
                    {{ order.user_name_snapshot ?? 'Unknown' }}
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ order.user_email_snapshot }}
                </p>
            </div>
            <div class="rounded-xl border bg-card p-5">
                <p class="text-sm text-muted-foreground">Total</p>
                <p class="mt-1 text-2xl font-semibold">
                    {{ money(order.total_price) }}
                </p>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border">
            <div class="border-b bg-muted/30 px-5 py-4">
                <h2 class="font-medium">Line items</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="text-left">
                        <tr>
                            <th class="px-5 py-3 font-medium">Product</th>
                            <th class="px-5 py-3 font-medium">Unit price</th>
                            <th class="px-5 py-3 font-medium">Quantity</th>
                            <th class="px-5 py-3 text-right font-medium">
                                Subtotal
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="product in order.products"
                            :key="product.id"
                            class="border-t"
                        >
                            <td class="px-5 py-4">
                                <p class="font-medium">{{ product.name }}</p>
                                <p class="text-xs text-muted-foreground">
                                    {{ product.sku }}
                                </p>
                            </td>
                            <td class="px-5 py-4">
                                {{ money(product.pivot.unit_price) }}
                            </td>
                            <td class="px-5 py-4">
                                {{ product.pivot.quantity }}
                            </td>
                            <td class="px-5 py-4 text-right font-medium">
                                {{ money(product.pivot.subtotal) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
