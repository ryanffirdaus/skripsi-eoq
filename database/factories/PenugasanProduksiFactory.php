<?php

namespace Database\Factories;

use App\Models\PenugasanProduksi;
use App\Models\PengadaanDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PenugasanProduksi>
 */
class PenugasanProduksiFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<\App\Models\PenugasanProduksi>
     */
    protected $model = PenugasanProduksi::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'pengadaan_detail_id' => PengadaanDetail::factory(),
            'user_id' => User::factory(['role_id' => 'ROLE003']), // Production worker
            'jumlah_produksi' => $this->faker->numberBetween(1, 100),
            'status' => $this->faker->randomElement(['ditugaskan', 'proses', 'selesai', 'dibatalkan']),
            'deadline' => Carbon::now()->addDays($this->faker->numberBetween(1, 30)),
            'catatan' => $this->faker->sentence(),
            'created_by' => User::factory(['role_id' => 'ROLE002']), // Supervisor
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }

    /**
     * Indicate that the penugasan is assigned.
     */
    public function assigned(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'ditugaskan',
                'updated_by' => null,
                'deleted_by' => null,
            ];
        });
    }

    /**
     * Indicate that the penugasan is in progress.
     */
    public function inProgress(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'proses',
                'updated_by' => $attributes['user_id'], // Worker yang update
            ];
        });
    }

    /**
     * Indicate that the penugasan is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'selesai',
                'updated_by' => $attributes['user_id'], // Worker yang complete
            ];
        });
    }

    /**
     * Indicate that the penugasan is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'dibatalkan',
                'updated_by' => $attributes['created_by'], // Supervisor yang cancel
            ];
        });
    }

    /**
     * Indicate that the penugasan is overdue.
     */
    public function overdue(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'deadline' => Carbon::now()->subDays($this->faker->numberBetween(1, 10)),
                'status' => $this->faker->randomElement(['ditugaskan', 'proses']),
            ];
        });
    }

    /**
     * Set a specific pengadaan_detail.
     */
    public function forPengadaanDetail(PengadaanDetail $pengadaanDetail): static
    {
        return $this->state(function (array $attributes) use ($pengadaanDetail) {
            return [
                'pengadaan_detail_id' => $pengadaanDetail->pengadaan_detail_id,
            ];
        });
    }

    /**
     * Set a specific user as worker.
     */
    public function forWorker(User $user): static
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->user_id,
            ];
        });
    }

    /**
     * Set a specific user as supervisor/creator.
     */
    public function createdBy(User $supervisor): static
    {
        return $this->state(function (array $attributes) use ($supervisor) {
            return [
                'created_by' => $supervisor->user_id,
            ];
        });
    }
}
