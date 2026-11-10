<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Event;
use App\Models\Guest;
use App\Models\GuestCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Concerns\InteractsWithApi;
use Tests\TestCase;

class GuestImportExportTest extends TestCase
{
    use InteractsWithApi, RefreshDatabase;

    private Event $event;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event = Event::factory()->create(['name' => 'Acara Impor']);
        $this->admin = User::factory()->eventAdmin()->create();
        $this->assign($this->event, $this->admin);

        $vip = new GuestCategory(['name' => 'VIP']);
        $vip->event_id = $this->event->id;
        $vip->save();
    }

    private function csv(): UploadedFile
    {
        $content = implode("\n", [
            'nama,nama_keluarga,telepon,email,kategori,kuota',
            'Budi Santoso,Keluarga Santoso,081234567890,budi@contoh.com,VIP,4',
            ',,,,,',
            'Siti,,abc,,Kategori Ngawur,0',
            'budi  santoso,,+6281234567890,,,',
        ]);

        return UploadedFile::fake()->createWithContent('tamu.csv', $content);
    }

    public function test_import_saves_valid_rows_reports_errors_and_skips_duplicates(): void
    {
        $this->asUser($this->admin)
            ->post("/api/v1/events/{$this->event->ulid}/guests/import", ['file' => $this->csv()], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.imported', 1)
            ->assertJsonCount(1, 'data.errors')
            ->assertJsonCount(1, 'data.skipped');

        $this->assertDatabaseCount('guests', 1);
        $this->assertDatabaseHas('guests', ['name' => 'Budi Santoso', 'quota' => 4, 'phone_normalized' => '6281234567890']);
    }

    public function test_dry_run_saves_nothing(): void
    {
        $this->asUser($this->admin)
            ->post("/api/v1/events/{$this->event->ulid}/guests/import", ['file' => $this->csv(), 'dry_run' => '1'], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('data.dry_run', true)
            ->assertJsonPath('data.valid', 1)
            ->assertJsonPath('data.imported', 0);

        $this->assertDatabaseCount('guests', 0);
    }

    public function test_import_rejects_wrong_file_type_and_other_events(): void
    {
        $this->asUser($this->admin)
            ->post("/api/v1/events/{$this->event->ulid}/guests/import", ['file' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])
            ->assertStatus(422)->assertJsonValidationErrors(['file']);

        $other = Event::factory()->create();

        $this->asUser($this->admin)
            ->post("/api/v1/events/{$other->ulid}/guests/import", ['file' => $this->csv()], ['Accept' => 'application/json'])
            ->assertStatus(404);
    }

    public function test_template_and_export_are_downloadable(): void
    {
        Guest::factory()->create(['event_id' => $this->event->id]);

        $this->asUser($this->admin)->get("/api/v1/events/{$this->event->ulid}/guests/import-template")
            ->assertOk()->assertDownload('template-tamu.xlsx');

        $this->asUser($this->admin)->get("/api/v1/events/{$this->event->ulid}/guests/export")
            ->assertOk()->assertDownload('tamu-acara-impor.xlsx');
    }
}
