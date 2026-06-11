<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index, store } from '@/actions/App/Http/Controllers/OrderController';
import Heading from '@/components/Heading.vue';
import OrderForm from '@/components/orders/OrderForm.vue';
import type { Product } from '@/types';

defineProps<{
    products: Product[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Orders', href: index() },
            { title: 'Create', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Create order" />

    <div class="mx-auto flex w-full max-w-5xl flex-col gap-6 p-4">
        <Heading
            title="Create order"
            description="Create an order and add its product line items."
        />
        <div class="rounded-xl border bg-card p-6">
            <OrderForm
                :action="store.form()"
                :products="products"
                submit-label="Create order"
            />
        </div>
    </div>
</template>
