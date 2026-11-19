/** Tautan pertama di halaman untuk pengguna keyboard dan pembaca layar. */
export function SkipLink({ target = 'konten' }: { target?: string }) {
    return (
        <a href={`#${target}`} className="skip-link">
            Lewati ke konten utama
        </a>
    );
}
