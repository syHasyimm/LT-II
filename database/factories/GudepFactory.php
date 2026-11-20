<?php

namespace Database\Factories;

use App\Enums\GudepStatus;
use App\Enums\TeamGroup;
use App\Models\Gudep;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gudep>
 */
class GudepFactory extends Factory
{
    public function definition(): array
    {
        $number = fake()->numerify('##.###');

        return [
            'user_id' => User::factory()->pembina(),
            'name' => "Gudep {$number}",
            'gudep_number' => $number,
            'npsn' => fake()->numerify('########'),
            'institution' => 'SMP Negeri '.fake()->numberBetween(1, 20).' '.fake()->city(),
            'pic_name' => fake()->name(),
            'address' => fake()->streetAddress().', '.fake()->city(),
            'status' => GudepStatus::Draft,
        ];
    }

    public function status(GudepStatus $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'submitted_at' => $status === GudepStatus::Draft ? null : now(),
            'verified_at' => $status === GudepStatus::Verified ? now() : null,
        ]);
    }

    /** Gudep dengan pembina pemilik akun (BR-03), seperti hasil CreateGudep. */
    public function withOwner(): static
    {
        return $this->afterCreating(function (Gudep $gudep) {
            Member::factory()->for($gudep)->pembina()->create([
                'name' => $gudep->user->name,
                'is_owner' => true,
            ]);
        });
    }

    /** Gudep dengan kuota penuh: 8 Pa, 8 Pi, dan pembina pemilik. */
    public function complete(): static
    {
        return $this->withOwner()->afterCreating(function (Gudep $gudep) {
            $perRegu = (int) config('camp.peserta_per_regu');

            Member::factory()->for($gudep)->peserta(TeamGroup::Pa)->count($perRegu)->create();
            Member::factory()->for($gudep)->peserta(TeamGroup::Pi)->count($perRegu)->create();
        });
    }
}
