export type DocumentData = {
    id: number;
    status: 'pending' | 'approved' | 'rejected';
    original_name: string;
    size_label: string;
    mime: string;
    note: string | null;
    uploaded_at: string | null;
    url: string;
};

export type DocumentColumn = {
    id: number;
    code: string;
    label: string;
    short: string;
};

export type DocumentCell = {
    required: boolean;
    document: DocumentData | null;
};

export type DocumentRow = {
    member: {
        id: number;
        name: string;
        short_name: string;
        role_label: string;
        is_pembina: boolean;
        group: 'pa' | 'pi' | 'pembina';
        kta_number: string | null;
        birth_label: string | null;
        is_owner: boolean;
        has_photo: boolean;
        photo_url: string | null;
    };
    cells: Record<string, DocumentCell>;
};

export type GudepDocument = {
    type: { id: number; code: string; label: string };
    document: DocumentData | null;
};

export type DocumentCounts = {
    uploaded: number;
    missing: number;
    rejected: number;
    approved: number;
    total: number;
};

/** Slot dokumen yang sedang dibuka di dialog. */
export type DocumentSlot = {
    typeId: number;
    typeLabel: string;
    memberId: number | null;
    ownerName: string;
    document: DocumentData | null;
};
