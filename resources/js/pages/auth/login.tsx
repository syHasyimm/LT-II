import { Form, Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

/** Masuk Admin dengan email + kata sandi (FR-AUTH-02). */
export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Masuk Admin" />

            <PasskeyVerify />

            {status && (
                <p className="mb-4 rounded-xl bg-success-soft px-4 py-2.5 text-sm text-success">
                    {status}
                </p>
            )}

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-5">
                        <div className="grid gap-2">
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                required
                                autoFocus
                                tabIndex={1}
                                autoComplete="email"
                                placeholder="panitia@contoh.id"
                                className="h-12 rounded-xl bg-white"
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <div className="flex items-center">
                                <Label htmlFor="password">Kata sandi</Label>
                                {canResetPassword && (
                                    <TextLink
                                        href={request()}
                                        className="ml-auto text-sm"
                                        tabIndex={5}
                                    >
                                        Lupa kata sandi?
                                    </TextLink>
                                )}
                            </div>
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                tabIndex={2}
                                autoComplete="current-password"
                                placeholder="Kata sandi"
                                className="h-12 rounded-xl bg-white"
                            />
                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="remember"
                                name="remember"
                                tabIndex={3}
                            />
                            <Label htmlFor="remember">Ingat saya</Label>
                        </div>

                        <Button
                            type="submit"
                            size="xl"
                            className="mt-2 w-full"
                            tabIndex={4}
                            disabled={processing}
                            data-test="login-button"
                        >
                            {processing && <Spinner />}
                            Masuk
                        </Button>
                    </div>
                )}
            </Form>

            <div className="my-5 h-px bg-line" />
            <Link
                href="/login"
                className="flex items-center justify-center gap-1.5 text-[13px] font-semibold text-maroon-700"
            >
                Pembina? Masuk dengan Google
                <ArrowRight className="size-3.5" />
            </Link>
        </>
    );
}

Login.layout = {
    title: 'Masuk Admin',
    description: 'Khusus panitia. Gunakan email dan kata sandi akun admin.',
};
