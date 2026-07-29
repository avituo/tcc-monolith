export type Product = {
    id: number;
    name: string;
    description: string;
    slug: string;
    image: string | null;
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
        product_name: string;
        product_sku: string;
        list_price: string;
        discount: string;
        unit_price: string;
        subtotal: string;
    };
};

export type Order = {
    id: number;
    name: string;
    user_id: number;
    user_name_snapshot: string;
    user_email_snapshot: string;
    total_price: string;
    status: 'pending' | 'paid' | 'cancelled';
    products_count?: number;
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
