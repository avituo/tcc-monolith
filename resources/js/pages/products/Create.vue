<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { index, store } from '@/actions/App/Http/Controllers/ProductController';
import Heading from '@/components/Heading.vue';
import ProductForm from '@/components/products/ProductForm.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Products', href: index() },
            { title: 'Create', href: '#' },
        ],
    },
});
</script>

<template>
    <Head title="Create product" />

    <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-4">
        <Heading
            title="Create product"
            description="Add a product to the catalog."
        />
        <div class="rounded-xl border bg-card p-6">
            <ProductForm :action="store.form()" submit-label="Create product" />
        </div>
    </div>
</template>
