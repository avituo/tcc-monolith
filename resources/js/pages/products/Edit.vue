<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import {
    index,
    update,
} from '@/actions/App/Http/Controllers/ProductController';
import Heading from '@/components/Heading.vue';
import ProductForm from '@/components/products/ProductForm.vue';
import type { Product } from '@/types';

const props = defineProps<{
    product: Product;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Products', href: index() },
            { title: 'Edit', href: '#' },
        ],
    },
});
</script>

<template>
    <Head :title="`Edit ${product.name}`" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
        <Heading
            :title="`Edit ${product.name}`"
            description="Update product details, pricing, and inventory."
        />
        <div class="rounded-xl border bg-card p-6">
            <ProductForm
                :action="update.form(props.product.id)"
                :product="product"
                submit-label="Save product"
            />
        </div>
    </div>
</template>
