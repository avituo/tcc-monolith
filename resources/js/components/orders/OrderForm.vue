<script setup lang="ts">
import { Form, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index } from '@/routes/orders';
import type { Order, Product } from '@/types';

type SelectedItem = {
    product_id: number;
    quantity: number;
};

const props = withDefaults(
    defineProps<{
        action: { action: string; method: 'post' };
        order?: Order;
        products: Product[];
        submitLabel: string;
    }>(),
    {
        order: undefined,
    },
);

const selectedItems = ref<SelectedItem[]>(
    props.order?.products?.map((product) => ({
        product_id: product.id,
        quantity: product.pivot.quantity,
    })) ?? [],
);

const availableProducts = computed(() =>
    props.products.filter(
        (product) =>
            !selectedItems.value.some((item) => item.product_id === product.id),
    ),
);

const estimatedTotal = computed(() =>
    selectedItems.value.reduce((total, item) => {
        const product = props.products.find(
            (candidate) => candidate.id === item.product_id,
        );

        if (!product) {
            return total;
        }

        const unitPrice = Math.max(
            Number(product.price) - Number(product.discount),
            0,
        );

        return total + unitPrice * Number(item.quantity || 0);
    }, 0),
);

function addProduct(productId: number): void {
    if (!productId) {
        return;
    }

    selectedItems.value.push({ product_id: productId, quantity: 1 });
}

function removeProduct(productId: number): void {
    selectedItems.value = selectedItems.value.filter(
        (item) => item.product_id !== productId,
    );
}

function productFor(productId: number): Product | undefined {
    return props.products.find((product) => product.id === productId);
}

function money(value: string | number): string {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
    }).format(Number(value));
}
</script>

<template>
    <Form v-bind="action" class="grid gap-6" v-slot="{ errors, processing }">
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="name">Order name</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="order?.name"
                    required
                />
                <InputError :message="errors.name" />
            </div>

            <input type="hidden" name="status" value="pending" />
        </div>

        <section class="grid gap-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="font-medium">Products</h2>
                    <p class="text-sm text-muted-foreground">
                        Select products and set their quantities.
                    </p>
                </div>

                <select
                    :value="0"
                    class="h-9 min-w-64 rounded-md border border-input bg-background px-3 text-sm"
                    @change="
                        addProduct(
                            Number(($event.target as HTMLSelectElement).value),
                        )
                    "
                >
                    <option :value="0" disabled>Add a product</option>
                    <option
                        v-for="product in availableProducts"
                        :key="product.id"
                        :value="product.id"
                    >
                        {{ product.name }} ({{ product.sku }})
                    </option>
                </select>
            </div>

            <InputError :message="errors.items" />

            <div
                v-if="selectedItems.length === 0"
                class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
            >
                No products selected.
            </div>

            <div v-else class="overflow-hidden rounded-lg border">
                <div
                    v-for="(item, index) in selectedItems"
                    :key="item.product_id"
                    class="grid gap-4 border-b p-4 last:border-b-0 md:grid-cols-[1fr_9rem_8rem]"
                >
                    <div>
                        <p class="font-medium">
                            {{ productFor(item.product_id)?.name }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ productFor(item.product_id)?.sku }} ·
                            {{
                                money(
                                    Math.max(
                                        Number(
                                            productFor(item.product_id)?.price,
                                        ) -
                                            Number(
                                                productFor(item.product_id)
                                                    ?.discount,
                                            ),
                                        0,
                                    ),
                                )
                            }}
                        </p>
                        <input
                            type="hidden"
                            :name="`items[${index}][product_id]`"
                            :value="item.product_id"
                        />
                        <InputError
                            :message="errors[`items.${index}.product_id`]"
                        />
                    </div>

                    <div class="grid gap-2">
                        <Label :for="`quantity-${item.product_id}`"
                            >Quantity</Label
                        >
                        <Input
                            :id="`quantity-${item.product_id}`"
                            v-model="item.quantity"
                            :name="`items[${index}][quantity]`"
                            type="number"
                            min="1"
                            max="9999"
                            required
                        />
                        <InputError
                            :message="errors[`items.${index}.quantity`]"
                        />
                    </div>

                    <Button
                        type="button"
                        variant="outline"
                        class="self-end"
                        @click="removeProduct(item.product_id)"
                    >
                        Remove
                    </Button>
                </div>
            </div>

            <div class="flex justify-end text-lg font-semibold">
                Estimated total: {{ money(estimatedTotal) }}
            </div>
        </section>

        <div class="flex flex-wrap gap-3">
            <Button
                type="submit"
                :disabled="processing || selectedItems.length === 0"
            >
                {{ processing ? 'Saving...' : submitLabel }}
            </Button>
            <Button variant="outline" as-child>
                <Link :href="index()">Cancel</Link>
            </Button>
        </div>
    </Form>
</template>
