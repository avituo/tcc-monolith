<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Boxes, ShoppingCart } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { index as ordersIndex } from '@/routes/orders';
import { index as productsIndex } from '@/routes/products';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Products',
        href: productsIndex(),
        icon: Boxes,
    },
    {
        title: 'Orders',
        href: ordersIndex(),
        icon: ShoppingCart,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="productsIndex()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
