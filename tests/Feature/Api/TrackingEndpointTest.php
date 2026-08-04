<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\TrackingCategory;
use App\Models\Application;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `API Tracking URL` — kontrak §3–§4.
 *
 * Uji di berkas ini adalah salinan aturan penolakan PLD (`tracking_contract.go`
 * di pld-user). Melanggarnya tidak menghasilkan galat yang terlihat di sisi kita:
 * PLD membuang item diam-diam dan member sekadar tidak melihat permohonannya.
 * Itulah kenapa aturannya diikat di sini, bukan dipercayakan pada kehati-hatian.
 */
final class TrackingEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const KEY = 'kunci-uji-integrasi-pld';

    #[Test]
    public function menolak_permintaan_tanpa_api_key(): void
    {
        $this->postJson('/api/pld/tracking', [
            'contractVersion' => '1.0',
            'userLogins' => ['budi'],
        ])->assertStatus(400);
    }

    #[Test]
    public function membalas_items_kosong_untuk_member_tak_dikenal(): void
    {
        // 200 dengan items kosong — BUKAN 400/404. Kontrak §6 membaca ini sebagai
        // "member memang tak punya proses", dan itu jawaban yang benar.
        $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/tracking', [
                'contractVersion' => '1.0',
                'userLogins' => ['tidak-ada'],
            ])
            ->assertOk()
            ->assertExactJson([
                'contractVersion' => '1.0',
                'items' => [],
            ]);
    }

    #[Test]
    public function item_berjalan_punya_tepat_satu_tahap_current(): void
    {
        $this->makeApplication(TrackingCategory::InProgress, currentStage: 2, totalStages: 4);

        $items = $this->fetchItems();

        $this->assertCount(1, $items);
        $this->assertSame(1, $this->countStatus($items[0]['timeline'], 'CURRENT'));
        $this->assertSame(1, $this->countStatus($items[0]['timeline'], 'DONE'));
        $this->assertSame(2, $this->countStatus($items[0]['timeline'], 'PENDING'));
    }

    #[Test]
    public function item_terminal_tidak_punya_tahap_current_sama_sekali(): void
    {
        foreach (TrackingCategory::terminal() as $index => $category) {
            Application::query()->delete();
            $this->makeApplication($category, currentStage: 3, totalStages: 4, ref: 'REF-'.$index);

            $items = $this->fetchItems();

            $this->assertSame(
                0,
                $this->countStatus($items[0]['timeline'], 'CURRENT'),
                "Kategori terminal {$category->value} tidak boleh punya tahap CURRENT",
            );
        }
    }

    #[Test]
    public function action_required_terisi_hanya_untuk_kategori_action_required(): void
    {
        $this->makeApplication(
            TrackingCategory::ActionRequired,
            currentStage: 2,
            totalStages: 3,
            instruction: 'Unggah ulang sertifikat.',
        );

        $items = $this->fetchItems();
        $this->assertNotNull($items[0]['actionRequired']);
        $this->assertSame('Unggah ulang sertifikat.', $items[0]['actionRequired']['instruction']);
    }

    #[Test]
    public function action_required_null_walau_kolomnya_masih_terisi_dari_status_sebelumnya(): void
    {
        // Cabang yang paling mudah luput: instruksi tertinggal di basis data
        // setelah kategori berpindah. Kalau ikut terkirim, PLD menolak SELURUH
        // item — bukan cuma mengabaikan field-nya.
        $application = $this->makeApplication(
            TrackingCategory::ActionRequired,
            currentStage: 2,
            totalStages: 3,
            instruction: 'Instruksi lama yang belum dibersihkan.',
        );

        $application->forceFill(['category' => TrackingCategory::Completed])->save();

        $items = $this->fetchItems();
        $this->assertNull($items[0]['actionRequired']);
    }

    #[Test]
    public function tahap_done_dan_current_selalu_punya_occurred_at(): void
    {
        // Sengaja dibuat cacat: tahap yang sudah tercapai tapi occurred_at-nya
        // kosong. Payload tetap harus sah — satu null di sini membuang seluruh item.
        $application = $this->makeApplication(TrackingCategory::InProgress, currentStage: 3, totalStages: 4);
        $application->stages()->update(['occurred_at' => null]);

        $items = $this->fetchItems();

        foreach ($items[0]['timeline'] as $entry) {
            if (in_array($entry['status'], ['DONE', 'CURRENT'], true)) {
                $this->assertNotNull($entry['occurredAt'], "Tahap {$entry['stage']} berstatus {$entry['status']} tanpa occurredAt");
            } else {
                $this->assertNull($entry['occurredAt'], 'Tahap PENDING tidak boleh membawa occurredAt');
            }
        }
    }

    #[Test]
    public function waktu_diserialkan_sebagai_rfc3339(): void
    {
        // PLD memakai time.Parse(time.RFC3339). Format lain gagal diurai dan
        // itemnya ditolak dengan alasan "submittedAt bukan waktu ISO-8601 yang sah".
        $this->makeApplication(TrackingCategory::InProgress, currentStage: 1, totalStages: 2);

        $items = $this->fetchItems();

        foreach (['submittedAt', 'updatedAt'] as $field) {
            $this->assertMatchesRegularExpression(
                '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/',
                $items[0][$field],
                "{$field} bukan RFC3339",
            );
        }
    }

    #[Test]
    public function menolak_user_logins_melebihi_batas_200(): void
    {
        $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/tracking', [
                'contractVersion' => '1.0',
                'userLogins' => array_fill(0, 201, 'budi'),
            ])
            ->assertStatus(400)
            ->assertJsonStructure(['message']);
    }

    #[Test]
    public function contract_version_wajib_ada(): void
    {
        $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/tracking', ['userLogins' => ['budi']])
            ->assertStatus(400);
    }

    #[Test]
    public function member_nonaktif_tidak_ikut_dilaporkan(): void
    {
        $application = $this->makeApplication(TrackingCategory::InProgress, currentStage: 1, totalStages: 2);
        $application->member->forceFill(['is_active' => false])->save();

        $this->assertSame([], $this->fetchItems());
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchItems(): array
    {
        return $this->withHeader('Api-Key', self::KEY)
            ->postJson('/api/pld/tracking', [
                'contractVersion' => '1.0',
                'userLogins' => ['budi'],
            ])
            ->assertOk()
            ->json('items');
    }

    /**
     * @param  array<int, array{status: string}>  $timeline
     */
    private function countStatus(array $timeline, string $status): int
    {
        return count(array_filter($timeline, static fn (array $e): bool => $e['status'] === $status));
    }

    private function makeApplication(
        TrackingCategory $category,
        int $currentStage,
        int $totalStages,
        ?string $instruction = null,
        string $ref = 'PRZ-2026-00001',
    ): Application {
        $member = Member::query()->firstOrCreate(
            ['user_login' => 'budi'],
            ['name' => 'Budi', 'password' => 'rahasia123', 'is_active' => true],
        );

        $application = Application::query()->create([
            'external_ref' => $ref,
            'member_id' => $member->id,
            'label' => 'Permohonan Uji',
            'category' => $category,
            'status_label' => 'Status uji',
            'submitted_at' => Carbon::parse('2026-07-01 08:00:00'),
            'status_changed_at' => Carbon::parse('2026-07-10 08:00:00'),
            'current_stage' => $currentStage,
            'total_stages' => $totalStages,
            'action_instruction' => $instruction,
        ]);

        for ($stage = 1; $stage <= $totalStages; $stage++) {
            $application->stages()->create([
                'stage' => $stage,
                'name' => "Tahap {$stage}",
                'occurred_at' => $stage <= $currentStage ? Carbon::parse('2026-07-05 08:00:00') : null,
            ]);
        }

        return $application->refresh();
    }
}
