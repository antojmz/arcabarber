export type UserRole = 'admin' | 'barber';

export type ClosingPeriod = 'daily' | 'weekly' | 'monthly';

export type Totals = {
    services_count: number;
    gross_total: number;
    tips_total: number;
    atm_fee_total: number;
    barber_total: number;
    shop_total: number;
    payout_total?: number;
};

export type PaymentMethodLine = Totals & {
    payment_method_id: number;
    payment_method_name: string | null;
};

export type ServiceLine = Totals & {
    service_id: number;
    service_name: string | null;
};

export type BarberLine = Totals & {
    barber_id: number;
    barber_name: string | null;
};

export type Summary = {
    totals: Required<Totals>;
    by_payment_method: PaymentMethodLine[];
    by_service: ServiceLine[];
    by_barber: BarberLine[];
};

export type ServiceCount = {
    service_id: number;
    payment_method_id: number;
    services_count: number;
};

export type DateRange = {
    start: string;
    end: string;
};

export type ServiceOption = {
    id: number;
    name: string;
    base_price: number;
};

export type PaymentMethodOption = {
    id: number;
    name: string;
    code?: string;
    atm_fee_rate?: string | number;
};

export type BarberOption = {
    id: number;
    name: string;
    commission_rate: string | number;
    is_active?: boolean;
};

export type Transaction = {
    id: number;
    performed_at: string;
    barber_id: number;
    barber_name?: string;
    service_id: number;
    service_name: string;
    service_base_price: number;
    payment_method_id: number;
    payment_method_name: string;
    atm_fee_rate: number;
    barber_commission_rate: number;
    amount: number;
    tip: number;
    atm_fee_amount: number;
    barber_amount: number;
    shop_amount: number;
    notes: string | null;
    voided: boolean;
    void_reason: string | null;
    locked: boolean;
    can_update: boolean;
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
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type CashClosing = Required<Totals> & {
    id: number;
    period_type: ClosingPeriod;
    period_label: string;
    period_start: string;
    period_end: string;
    barber_id: number | null;
    barber_name: string | null;
    closed_by_name: string;
    paid_at: string | null;
    created_at: string | null;
};
