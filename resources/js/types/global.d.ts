import type { RegistrationWindow } from '@/components/registration-pill';
import type { Auth } from '@/types/auth';
import type { PembinaStepData, StepKey } from '@/types/pembina';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            registration: RegistrationWindow & { now: string };
            pembinaSteps: PembinaStepData[] | null;
            pembinaStep: StepKey | 'ringkasan' | null;
            [key: string]: unknown;
        };
    }
}
