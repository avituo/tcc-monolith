import type { User } from './auth';

export type Product = {
    id: number;
    name: string;
    description: string;
    slug: string;
    image: string;
    price: string;
    discount: string;
    quantity: number;
    sku: string;
    is_active: boolean;
    created_at: string;
    updated_at: string;
};

export type OrderProduct = Product & {
    pivot: {
        quantity: number;
        unit_price: string;
        subtotal: string;
    };
};

export type Order = {
    id: number;
    name: string;
    user_id: number;
    total_price: string;
    status: 'pending' | 'paid' | 'cancelled';
    products_count?: number;
    user?: Pick<User, 'id' | 'name' | 'email'>;
    products?: OrderProduct[];
    created_at: string;
    updated_at: string;
};

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    from: number | null;
    last_page: number;
    per_page: number;
    to: number | null;
    total: number;
};
