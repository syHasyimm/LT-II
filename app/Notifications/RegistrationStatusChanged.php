<?php

namespace App\Notifications;

use App\Enums\GudepStatus;
use App\Models\Gudep;
use App\Models\User;
use App\Services\RegistrationWindow;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** FR-VER-05: email ke pembina saat status pendaftaran berubah (lewat queue). */
class RegistrationStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Gudep $gudep) {}

    /** @return list<string> */
    public function via(User $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        $name = $this->gudep->displayName();
        $mail = (new MailMessage)
            ->greeting("Halo, {$notifiable->name}")
            ->salutation('Salam Pramuka, Panitia Perkemahan LT 2');

        return match ($this->gudep->status) {
            GudepStatus::Submitted => $mail
                ->subject("Pendaftaran {$this->gudep->name} terkirim")
                ->line("Pendaftaran {$name} sudah kami terima. Data gudep kini terkunci sampai panitia selesai memverifikasi.")
                ->action('Lihat status pendaftaran', route('pembina.submission.show')),
            GudepStatus::Verified => $mail
                ->subject("Pendaftaran {$this->gudep->name} terverifikasi")
                ->line("Selamat! Seluruh data dan dokumen {$name} sudah disetujui panitia.")
                ->line('ID Card dan sertifikat akan diterbitkan panitia untuk semua anggota.')
                ->action('Buka beranda pembina', route('pembina.dashboard')),
            GudepStatus::Revision => $this->revisionMail($mail, $name),
            GudepStatus::Draft => $mail
                ->subject("Status pendaftaran {$this->gudep->name} berubah")
                ->action('Buka beranda pembina', route('pembina.dashboard')),
        };
    }

    private function revisionMail(MailMessage $mail, string $name): MailMessage
    {
        $mail->subject("Pendaftaran {$this->gudep->name} perlu revisi")
            ->line("Panitia meminta perbaikan untuk pendaftaran {$name}.")
            ->line('**Catatan panitia:** '.($this->gudep->admin_note ?? '-'));

        if ($this->gudep->revision_deadline !== null) {
            $deadline = CarbonImmutable::parse($this->gudep->revision_deadline)->setTimezone((string) config('camp.timezone'));
            $mail->line('Perbaikan dapat dilakukan sampai '.RegistrationWindow::label($deadline).'.');
        }

        return $mail->action('Perbaiki pendaftaran', route('pembina.submission.show'));
    }
}
