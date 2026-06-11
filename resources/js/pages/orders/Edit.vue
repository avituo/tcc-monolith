<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index, update } from '@/actions/App/Http/Controllers/OrderController';
import Heading from '@/components/Heading.vue';
import OrderForm from '@/components/orders/OrderForm.vue';
import type { Order, Product } from '@/types';

const props = defineProps<{
    order: Order;
    products: Product[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Orders', href: index() },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${order.name}`" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4">
        <Heading
            :title="`Edit ${order.name}`"
            description="Update the order status and product line items."
        />
        <div class="rounded-xl border bg-card p-6">
            <OrderForm
                :action="update.form(props.order.id)"
                :order="order"
                :products="products"
                submit-label="Save order"
            />
        </div>
    </div>
</template>
