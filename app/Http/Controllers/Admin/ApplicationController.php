<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\AdvanceApplicationStageAction;
use App\Actions\ChangeApplicationCategoryAction;
use App\Actions\CreateApplicationAction;
use App\Enums\TrackingCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ChangeCategoryRequest;
use App\Http\Requests\Admin\StoreApplicationRequest;
use App\Models\Application;
use App\Repositories\Contracts\ApplicationRepositoryContract;
use App\Repositories\Contracts\MemberRepositoryContract;
use App\Services\Contracts\TrackingPayloadServiceContract;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

/**
 * Panel operator: membuat proses dan menggerakkannya.
 *
 * Setiap tombol di sini mengubah apa yang akan dijawab `API Tracking URL` pada
 * sinkronisasi berikutnya — dan karenanya menentukan notifikasi apa yang terbit
 * di portal PLD. Itulah gunanya panel ini: menguji integrasi tanpa menunggu
 * proses perizinan sungguhan berjalan berminggu-minggu.
 */
final class ApplicationController extends Controller
{
    public function __construct(
        private readonly ApplicationRepositoryContract $applications,
        private readonly MemberRepositoryContract $members,
        private readonly TrackingPayloadServiceContract $payload,
    ) {}

    public function index(): View
    {
        return view('admin.applications.index', [
            'applications' => $this->applications->paginateForAdmin(),
        ]);
    }

    public function create(): View
    {
        return view('admin.applications.create', [
            'members' => $this->members->all(),
        ]);
    }

    public function store(
        StoreApplicationRequest $request,
        CreateApplicationAction $createApplication,
    ): RedirectResponse {
        $member = $this->members->all()->firstWhere('id', (int) $request->validated('member_id'));

        if ($member === null) {
            return back()->with('error', 'Member tidak ditemukan.');
        }

        $application = $createApplication(
            member: $member,
            label: (string) $request->validated('label'),
            stageNames: $request->stageNames(),
            contactUnit: $request->validated('contact_unit'),
            contactEmail: $request->validated('contact_email'),
            contactPhone: $request->validated('contact_phone'),
        );

        return redirect()
            ->route('admin.applications.show', $application)
            ->with('status', "Proses {$application->external_ref} dibuat. Ia akan ikut terkirim pada sinkronisasi PLD berikutnya.");
    }

    public function show(Application $application): View
    {
        $application->load(['member', 'stages', 'documents', 'attributes']);

        return view('admin.applications.show', [
            'application' => $application,
            'categories' => TrackingCategory::cases(),
            // Pratinjau payload PERSIS seperti yang akan diterima PLD. Ini cara
            // tercepat menemukan salah pemetaan — jauh lebih cepat daripada
            // menunggu siklus 30 menit lalu menebak kenapa item tak muncul.
            'preview' => $this->payload->buildItem($application)->toContractArray(),
        ]);
    }

    public function advance(
        Application $application,
        AdvanceApplicationStageAction $advanceStage,
    ): RedirectResponse {
        try {
            $advanceStage(
                application: $application,
                note: request()->string('note')->value() ?: null,
                actor: request()->string('actor')->value() ?: null,
            );
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Tahap dinaikkan. Notifikasi kemajuan akan muncul di lonceng portal PLD (tanpa email).');
    }

    public function changeCategory(
        ChangeCategoryRequest $request,
        Application $application,
        ChangeApplicationCategoryAction $changeCategory,
    ): RedirectResponse {
        $category = $request->category();

        $changeCategory(
            application: $application,
            category: $category,
            statusLabel: (string) $request->validated('status_label'),
            actionRequired: $request->actionRequired(),
            note: $request->validated('note'),
        );

        return back()->with('status', $this->outcomeMessage($category));
    }

    /**
     * Menjelaskan konsekuensi perubahan ini di sisi PLD.
     *
     * Bukan basa-basi: operator perlu tahu perpindahan mana yang mengirim EMAIL
     * ke member dan mana yang cukup muncul di lonceng, karena itulah beda antara
     * "member diberi tahu" dan "member menunggu sesuatu yang tak akan datang".
     */
    private function outcomeMessage(TrackingCategory $category): string
    {
        return match (true) {
            $category->requiresAction() => 'Kategori diubah ke ACTION_REQUIRED. Pada sinkronisasi berikutnya PLD menerbitkan notifikasi in-app DAN email berisi instruksi tindakan.',
            $category === TrackingCategory::Completed => 'Proses ditandai selesai. PLD akan menerbitkan notifikasi in-app DAN email, memuat nama dokumen hasil bila ada.',
            $category->isTerminal() => 'Proses ditandai berakhir. PLD akan menerbitkan notifikasi in-app DAN email, memuat catatan pada tahap terakhir sebagai alasannya.',
            default => 'Kategori diubah. Perpindahan ke IN_PROGRESS hanya muncul di lonceng portal, tanpa email.',
        };
    }
}
