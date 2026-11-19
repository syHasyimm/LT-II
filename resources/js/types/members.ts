export type Regu = 'pa' | 'pi' | 'pembina';

export type MemberData = {
    id: number;
    role: 'peserta' | 'pembina_pendamping';
    group: 'pa' | 'pi' | null;
    name: string;
    gender: 'L' | 'P' | null;
    birth_date: string | null;
    age: number | null;
    kta_number: string | null;
    phone: string | null;
    accompanies: 'pa' | 'pi' | 'keduanya' | null;
    accompanies_label: string | null;
    is_owner: boolean;
    photo_url: string | null;
    missing: string[];
};
