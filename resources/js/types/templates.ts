export type TemplateField = {
    id: string;
    kind: 'text' | 'photo' | 'qr';
    label: string;
    enabled: boolean;
    x: number;
    y: number;
    w: number;
    h: number;
    content?: string | null;
    font?: 'display' | 'sans' | 'mono' | null;
    weight?: number | null;
    italic?: boolean | null;
    size?: number | null;
    color?: string | null;
    align?: 'left' | 'center' | 'right' | null;
    uppercase?: boolean | null;
    letter_spacing?: number | null;
    bg?: string | null;
    radius?: number | null;
    border?: string | null;
};

export type TemplateSummary = {
    id: number;
    name: string;
    type: 'id_card' | 'certificate';
    type_label: string;
    applies_to: 'peserta' | 'pembina' | 'all';
    applies_label: string;
    width_mm: number;
    height_mm: number;
    is_active: boolean;
    background_url: string | null;
    updated_at: string | null;
};

export type TemplateSample = {
    id: number | null;
    label: string;
    values: Record<string, string>;
    photo_url: string | null;
};

export type Option = { value: string; label: string };
