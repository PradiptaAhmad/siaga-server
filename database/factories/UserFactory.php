<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nik'               => fake()->unique()->numerify('################'),
            'nama_lengkap'      => fake()->name(),
            'tempat_lahir'      => fake()->city(),
            'tanggal_lahir'     => fake()->date(),
            'alamat'            => fake()->address(),
            'kelamin'           => fake()->randomElement(['L', 'P']),
            'nama_keluarga'     => fake()->lastName(),
            'poin_keaktifan'    => 0,
            'role'              => 'warga',
            'email'             => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'          => static::$password ??= Hash::make('password'),
            'remember_token'    => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
