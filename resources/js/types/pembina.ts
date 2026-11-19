/** Kunci langkah alur pembina, urut sesuai timeline. */
export type StepKey =
    | 'gudep'
    | 'peserta_pa'
    | 'peserta_pi'
    | 'pembina'
    | 'berkas'
    | 'kirim';

export type PembinaStepData = {
    key: StepKey;
    title: string;
    subtitle: string;
    value: string;
    href: string;
    state: 'done' | 'current' | 'todo';
};
