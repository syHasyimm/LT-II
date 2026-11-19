<?php

use App\Enums\GudepStatus;

it('memuat konfigurasi kuota sesuai PRD', function () {
    expect(config('camp.peserta_per_regu'))->toBe(8)
        ->and(config('camp.pembina_min'))->toBe(1)
        ->and(config('camp.pembina_max'))->toBeNull()
        ->and(config('camp.max_upload_kb'))->toBe(2048)
        ->and(config('app.timezone'))->toBe('Asia/Jakarta');
});

it('hanya mengizinkan ubah data saat draft atau revisi', function () {
    expect(GudepStatus::Draft->isEditable())->toBeTrue()
        ->and(GudepStatus::Revision->isEditable())->toBeTrue()
        ->and(GudepStatus::Submitted->isEditable())->toBeFalse()
        ->and(GudepStatus::Verified->isEditable())->toBeFalse();
});
