<?php

namespace Database\Factories;

use App\Enums\Accompanies;
use App\Enums\Gender;
use App\Enums\MemberRole;
use App\Enums\TeamGroup;
use App\Models\Gudep;
use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'gudep_id' => Gudep::factory(),
            'role' => MemberRole::Peserta,
            'group' => TeamGroup::Pa,
            'name' => fake()->name('male'),
            'gender' => Gender::L,
            'birth_date' => fake()->dateTimeBetween('-15 years', '-12 years'),
            'kta_number' => fake()->numerify('##.##.##.###'),
            'photo_path' => null,
            'is_owner' => false,
        ];
    }

    public function peserta(TeamGroup $group): static
    {
        return $this->state(fn () => [
            'role' => MemberRole::Peserta,
            'group' => $group,
            'gender' => $group === TeamGroup::Pa ? Gender::L : Gender::P,
            'name' => fake()->name($group === TeamGroup::Pa ? 'male' : 'female'),
        ]);
    }

    public function pembina(): static
    {
        return $this->state(fn () => [
            'role' => MemberRole::Pembina,
            'group' => null,
            'birth_date' => fake()->dateTimeBetween('-50 years', '-22 years'),
            'phone' => fake()->numerify('08##########'),
            'accompanies' => Accompanies::Keduanya,
        ]);
    }

    public function withPhoto(): static
    {
        return $this->state(fn () => [
            'photo_path' => 'photos/'.fake()->uuid().'.jpg',
        ]);
    }
}
