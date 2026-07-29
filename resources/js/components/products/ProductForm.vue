<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/products';
import type { Product } from '@/types';

withDefaults(
    defineProps<{
        action: { action: string; method: 'post' };
        product?: Product;
        submitLabel: string;
    }>(),
    {
        product: undefined,
    },
);
</script>

<template>
    <Form v-bind="action" class="grid gap-6" v-slot="{ errors, processing }">
        <div class="grid gap-2">
            <Label for="name">Name</Label>
            <Input
                id="name"
                name="name"
                :default-value="product?.name"
                required
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="description">Description</Label>
            <textarea
                id="description"
                name="description"
                required
                :value="product?.description"
                class="min-h-28 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-3 focus-visible:ring-ring/50 dark:bg-input/30"
            />
            <InputError :message="errors.description" />
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            <div class="grid gap-2">
                <Label for="slug">Slug</Label>
                <Input
                    id="slug"
                    name="slug"
                    :default-value="product?.slug"
                    required
                />
                <InputError :message="errors.slug" />
            </div>

            <div class="grid gap-2">
                <Label for="sku">SKU</Label>
                <Input
                    id="sku"
                    name="sku"
                    :default-value="product?.sku"
                    required
                />
                <InputError :message="errors.sku" />
            </div>
        </div>

        <div class="grid gap-6 md:grid-cols-3">
            <div class="grid gap-2">
                <Label for="price">Price</Label>
                <Input
                    id="price"
                    name="price"
                    type="number"
                    min="0"
                    step="0.01"
                    :default-value="product?.price ?? 0"
                    required
                />
                <InputError :message="errors.price" />
            </div>

            <div class="grid gap-2">
                <Label for="discount">Discount</Label>
                <Input
                    id="discount"
                    name="discount"
                    type="number"
                    min="0"
                    step="0.01"
                    :default-value="product?.discount ?? 0"
                    required
                />
                <InputError :message="errors.discount" />
            </div>

            <div class="grid gap-2">
                <Label for="quantity">Quantity</Label>
                <Input
                    id="quantity"
                    name="quantity"
                    type="number"
                    min="0"
                    step="1"
                    :default-value="product?.quantity ?? 0"
                    required
                />
                <InputError :message="errors.quantity" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="image">Image URL</Label>
            <Input
                id="image"
                name="image"
                type="url"
                :default-value="product?.image ?? ''"
                placeholder="https://example.com/product.jpg"
            />
            <InputError :message="errors.image" />
        </div>

        <label class="flex items-center gap-3 text-sm font-medium">
            <input type="hidden" name="is_active" value="0" />
            <input
                type="checkbox"
                name="is_active"
                value="1"
                :checked="product?.is_active ?? true"
                class="size-4 rounded border-input accent-primary"
            />
            Active product
        </label>
        <InputError :message="errors.is_active" />

        <div class="flex flex-wrap gap-3">
            <Button type="submit" :disabled="processing">
                {{ processing ? 'Saving...' : submitLabel }}
            </Button>
            <Button variant="outline" as-child>
                <Link :href="index()">Cancel</Link>
            </Button>
        </div>
    </Form>
</template>
