<?php

declare(strict_types=1);

namespace App\Http\Requests\Api;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Permintaan `API Provisioning URL` dari PLD (kontrak provisioning v1.0 §4.1).
 *
 * Satu endpoint, tiga aksi, dibedakan `action`. Yang divalidasi di sini hanya
 * BENTUK badan; keputusan domain — apakah akunnya sudah ada, apakah perannya
 * dikenal — ada di Action masing-masing.
 *
 * -------------------------------------------------------------------------
 * KENAPA FIELD TAK DIKENAL TIDAK DITOLAK
 * -------------------------------------------------------------------------
 * Kontrak §11 menyatakan penambahan field opsional TIDAK menaikkan versi mayor,
 * dan implementasi wajib mengabaikan field yang tak dikenal alih-alih
 * menolaknya. Karena itu tak ada aturan `prohibited`/`exclude_unknown` di sini:
 * menolak payload karena PLD menambah satu field baru akan mematikan
 * provisioning seluruh member sampai aplikasi ini di-deploy ulang.
 *
 * -------------------------------------------------------------------------
 * KENAPA `subject.nip` TIDAK WAJIB
 * -------------------------------------------------------------------------
 * Kontrak §5 mengisinya hanya untuk `accountKind` INTERNAL. Mewajibkannya akan
 * menolak seluruh member eksternal — justru mayoritas pengguna aplikasi ini.
 */
final class ProvisioningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'contractVersion' => ['required', 'string', 'max:16'],
            'action' => ['required', 'string', 'in:createAccount,setStatus,setRole'],
            'requestId' => ['required', 'string', 'max:64'],

            'subject' => ['required', 'array'],
            'subject.pldUserId' => ['required', 'string', 'max:64'],
            'subject.email' => ['required', 'string', 'email', 'max:190'],
            'subject.fullName' => ['required', 'string', 'max:200'],
            'subject.accountKind' => ['required', 'string', 'in:INTERNAL,EXTERNAL,OSS'],
            'subject.nip' => ['nullable', 'string', 'max:32'],
            'subject.organization' => ['nullable', 'string', 'max:200'],
            'subject.jobTitle' => ['nullable', 'string', 'max:200'],

            // `present_if`, BUKAN `required_if`. Aturan `required` menganggap array
            // kosong sebagai "tidak diisi" dan menolaknya — padahal daftar kosong
            // justru cara kontrak §4.1.3 melepas SELURUH peran seseorang. Dengan
            // required_if, satu-satunya cara mencabut semua peran dijawab 400.
            //
            // Pada createAccount roles boleh absen sama sekali: aplikasi ini memakai
            // peran bawaannya sendiri (config pld.provisioning.default_role).
            'roles' => ['present_if:action,setRole', 'array', 'max:20'],
            'roles.*' => ['string', 'max:64'],

            'status' => ['required_if:action,setStatus', 'string', 'in:ACTIVE,DISABLED'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Pesan Bahasa Indonesia, sejalan dengan endpoint kontrak lainnya — `message`
     * inilah yang dibawa PLD ke log dan ke layar pengelola saat permintaan cacat.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'action.in' => 'action harus salah satu dari: createAccount, setStatus, setRole.',
            'requestId.required' => 'requestId wajib diisi; ia kunci idempotensi permintaan ini.',
            'subject.required' => 'subject wajib diisi.',
            'subject.pldUserId.required' => 'subject.pldUserId wajib diisi.',
            'subject.email.email' => 'subject.email bukan alamat surel yang sah.',
            'subject.accountKind.in' => 'subject.accountKind harus INTERNAL, EXTERNAL, atau OSS.',
            'roles.present_if' => 'roles wajib disertakan untuk aksi setRole (boleh berupa daftar kosong).',
            'status.required_if' => 'status wajib disertakan untuk aksi setStatus.',
        ];
    }

    /**
     * 400 dengan satu kalimat, bukan 422 berisi peta galat.
     *
     * Kontrak §4.1.4 memetakan 400 ke "permintaan cacat — berhenti, JANGAN
     * diulang". 422 tidak ada dalam peta itu, dan pld-integration akan
     * membacanya sebagai gangguan sesaat lalu mengulang selamanya sesuatu yang
     * tak akan pernah berubah.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(new JsonResponse(
            ['message' => $validator->errors()->first()],
            Response::HTTP_BAD_REQUEST,
        ));
    }
}
