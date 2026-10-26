const dateFormatter = new Intl.DateTimeFormat('es-CL', {
    day: '2-digit',
    month: 'short',
    year: 'numeric',
});

const dateTimeFormatter = new Intl.DateTimeFormat('es-CL', {
    day: '2-digit',
    month: 'short',
    hour: '2-digit',
    minute: '2-digit',
});

const timeFormatter = new Intl.DateTimeFormat('es-CL', {
    hour: '2-digit',
    minute: '2-digit',
});

/**
 * Parse a Y-m-d string as a local date (not UTC) so the day never shifts.
 */
function parse(value: string): Date {
    return /^\d{4}-\d{2}-\d{2}$/.test(value)
        ? new Date(`${value}T00:00:00`)
        : new Date(value);
}

export function formatDate(value: string): string {
    return dateFormatter.format(parse(value));
}

export function formatDateTime(value: string): string {
    return dateTimeFormatter.format(parse(value));
}

export function formatTime(value: string): string {
    return timeFormatter.format(parse(value));
}

export function formatRange(start: string, end: string): string {
    return start === end
        ? formatDate(start)
        : `${formatDate(start)} – ${formatDate(end)}`;
}

/**
 * Current local date-time formatted for a datetime-local input.
 */
export function nowForInput(): string {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());

    return now.toISOString().slice(0, 16);
}

export function toInputDateTime(value: string): string {
    const date = parse(value);
    date.setMinutes(date.getMinutes() - date.getTimezoneOffset());

    return date.toISOString().slice(0, 16);
}

export const periodOptions = [
    { value: 'daily', label: 'Diario' },
    { value: 'weekly', label: 'Semanal' },
    { value: 'monthly', label: 'Mensual' },
] as const;
