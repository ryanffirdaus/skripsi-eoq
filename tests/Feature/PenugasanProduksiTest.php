<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\PenugasanProduksi;
use App\Models\PengadaanDetail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PenugasanProduksiTest extends TestCase
{
    use RefreshDatabase;

    private $supervisor;
    private $worker;
    private $pengadaanDetail;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test users
        $this->supervisor = User::factory()->create(['role_id' => 'ROLE002']); // Supervisor
        $this->worker = User::factory()->create(['role_id' => 'ROLE003']); // Worker

        // Create test data
        $this->pengadaanDetail = PengadaanDetail::factory()->create();
    }

    /** @test */
    public function supervisor_can_create_penugasan()
    {
        $this->actingAs($this->supervisor);

        $response = $this->post(route('penugasan-produksi.store'), [
            'pengadaan_detail_id' => $this->pengadaanDetail->pengadaan_detail_id,
            'user_id' => $this->worker->user_id,
            'jumlah_produksi' => 10,
            'deadline' => now()->addDay(),
            'catatan' => 'Test penugasan',
        ]);

        $response->assertRedirect(route('penugasan-produksi.index'));
        $this->assertCount(1, PenugasanProduksi::all());
        $penugasan = PenugasanProduksi::first();
        $this->assertEquals('ditugaskan', $penugasan->status);
        $this->assertEquals($this->supervisor->user_id, $penugasan->created_by);
    }

    /** @test */
    public function worker_cannot_create_penugasan()
    {
        $this->actingAs($this->worker);

        $response = $this->post(route('penugasan-produksi.store'), [
            'pengadaan_detail_id' => $this->pengadaanDetail->pengadaan_detail_id,
            'user_id' => $this->worker->user_id,
            'jumlah_produksi' => 10,
            'deadline' => now()->addDay(),
        ]);

        // Should be denied or redirected
        $this->assertFalse($response->getStatusCode() === 200 || $response->getStatusCode() === 201);
    }

    /** @test */
    public function worker_can_view_only_their_assignments()
    {
        $otherWorker = User::factory()->create(['role_id' => 'ROLE003']);

        // Create penugasan untuk this worker
        $penugasan1 = PenugasanProduksi::factory()->create([
            'user_id' => $this->worker->user_id,
            'created_by' => $this->supervisor->user_id,
        ]);

        // Create penugasan untuk other worker
        $penugasan2 = PenugasanProduksi::factory()->create([
            'user_id' => $otherWorker->user_id,
            'created_by' => $this->supervisor->user_id,
        ]);

        $this->actingAs($this->worker);

        $response = $this->get(route('penugasan-produksi.show', $penugasan1));
        $response->assertOk();

        // Should not be able to view other worker's assignment
        $this->assertFalse($this->worker->can('view', $penugasan2));
    }

    /** @test */
    public function worker_can_update_status()
    {
        $penugasan = PenugasanProduksi::factory()->create([
            'user_id' => $this->worker->user_id,
            'created_by' => $this->supervisor->user_id,
            'status' => 'ditugaskan',
        ]);

        $this->actingAs($this->worker);

        $response = $this->patch(route('penugasan-produksi.update-status', $penugasan), [
            'status' => 'proses',
        ]);

        $response->assertOk();
        $penugasan->refresh();
        $this->assertEquals('proses', $penugasan->status);
    }

    /** @test */
    public function worker_cannot_update_status_completed_to_assigned()
    {
        $penugasan = PenugasanProduksi::factory()->create([
            'user_id' => $this->worker->user_id,
            'created_by' => $this->supervisor->user_id,
            'status' => 'selesai',
        ]);

        $this->actingAs($this->worker);

        $response = $this->patch(route('penugasan-produksi.update-status', $penugasan), [
            'status' => 'ditugaskan',
        ]);

        // Should fail
        $this->assertTrue($response->getStatusCode() >= 400);
        $penugasan->refresh();
        $this->assertEquals('selesai', $penugasan->status);
    }

    /** @test */
    public function supervisor_can_view_their_created_assignments()
    {
        $penugasan = PenugasanProduksi::factory()->create([
            'user_id' => $this->worker->user_id,
            'created_by' => $this->supervisor->user_id,
        ]);

        $this->actingAs($this->supervisor);

        $response = $this->get(route('penugasan-produksi.show', $penugasan));
        $response->assertOk();
    }

    /** @test */
    public function supervisor_cannot_view_assignments_created_by_others()
    {
        $otherSupervisor = User::factory()->create(['role_id' => 'ROLE002']);

        $penugasan = PenugasanProduksi::factory()->create([
            'user_id' => $this->worker->user_id,
            'created_by' => $otherSupervisor->user_id,
        ]);

        $this->actingAs($this->supervisor);

        // Should not be able to view
        $this->assertFalse($this->supervisor->can('view', $penugasan));
    }

    /** @test */
    public function supervisor_can_soft_delete_assignment()
    {
        $penugasan = PenugasanProduksi::factory()->create([
            'user_id' => $this->worker->user_id,
            'created_by' => $this->supervisor->user_id,
            'status' => 'ditugaskan',
        ]);

        $this->actingAs($this->supervisor);

        $response = $this->delete(route('penugasan-produksi.destroy', $penugasan));

        $response->assertRedirect(route('penugasan-produksi.index'));
        $penugasan->refresh();
        $this->assertNotNull($penugasan->deleted_at);
        $this->assertEquals($this->supervisor->user_id, $penugasan->deleted_by);
    }

    /** @test */
    public function cannot_delete_completed_assignment()
    {
        $penugasan = PenugasanProduksi::factory()->create([
            'user_id' => $this->worker->user_id,
            'created_by' => $this->supervisor->user_id,
            'status' => 'selesai',
        ]);

        $this->actingAs($this->supervisor);

        // Should not be able to delete
        $this->assertFalse($this->supervisor->can('delete', $penugasan));
    }

    /** @test */
    public function valid_status_transitions()
    {
        $penugasan = PenugasanProduksi::factory()->create([
            'status' => 'ditugaskan',
        ]);

        // ditugaskan -> proses should be valid
        $this->assertTrue($penugasan->isValidStatusTransition('proses'));

        // ditugaskan -> selesai should be invalid (must go through proses)
        $this->assertFalse($penugasan->isValidStatusTransition('selesai'));

        // ditugaskan -> dibatalkan should be valid
        $this->assertTrue($penugasan->isValidStatusTransition('dibatalkan'));

        // proses -> selesai should be valid
        $penugasan->status = 'proses';
        $this->assertTrue($penugasan->isValidStatusTransition('selesai'));

        // selesai -> anything should be invalid
        $penugasan->status = 'selesai';
        $this->assertFalse($penugasan->isValidStatusTransition('proses'));
    }

    /** @test */
    public function worker_cannot_exceed_max_qty()
    {
        $this->actingAs($this->supervisor);

        $maxQty = $this->pengadaanDetail->qty_disetujui ?? $this->pengadaanDetail->qty_diminta;

        $response = $this->post(route('penugasan-produksi.store'), [
            'pengadaan_detail_id' => $this->pengadaanDetail->pengadaan_detail_id,
            'user_id' => $this->worker->user_id,
            'jumlah_produksi' => $maxQty + 1, // Exceed limit
            'deadline' => now()->addDay(),
        ]);

        $response->assertSessionHasErrors('jumlah_produksi');
    }

    /** @test */
    public function get_outstanding_assignments()
    {
        // Create multiple assignments
        $penugasan1 = PenugasanProduksi::factory()->create([
            'pengadaan_detail_id' => $this->pengadaanDetail->pengadaan_detail_id,
            'status' => 'ditugaskan',
        ]);

        $penugasan2 = PenugasanProduksi::factory()->create([
            'pengadaan_detail_id' => $this->pengadaanDetail->pengadaan_detail_id,
            'status' => 'proses',
        ]);

        $penugasan3 = PenugasanProduksi::factory()->create([
            'pengadaan_detail_id' => $this->pengadaanDetail->pengadaan_detail_id,
            'status' => 'selesai',
        ]);

        $outstanding = PenugasanProduksi::byPengadaanDetail($this->pengadaanDetail->pengadaan_detail_id)
            ->outstanding()
            ->get();

        $this->assertCount(2, $outstanding);
        $this->assertTrue($outstanding->contains($penugasan1));
        $this->assertTrue($outstanding->contains($penugasan2));
        $this->assertFalse($outstanding->contains($penugasan3));
    }

    /** @test */
    public function supervisor_can_get_statistics()
    {
        // Create assignments
        PenugasanProduksi::factory(3)->create(['created_by' => $this->supervisor->user_id, 'status' => 'ditugaskan']);
        PenugasanProduksi::factory(2)->create(['created_by' => $this->supervisor->user_id, 'status' => 'proses']);
        PenugasanProduksi::factory(2)->create(['created_by' => $this->supervisor->user_id, 'status' => 'selesai']);

        $this->actingAs($this->supervisor);

        $response = $this->get(route('penugasan-produksi.statistics'));

        $response->assertOk();
        $data = $response->json();
        $this->assertEquals(3, $data['ditugaskan']);
        $this->assertEquals(2, $data['proses']);
        $this->assertEquals(2, $data['selesai']);
    }
}
