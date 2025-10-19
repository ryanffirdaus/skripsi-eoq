<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StorePenugasanProduksiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Hanya supervisor/manager yang bisa membuat penugasan
        $user = Auth::user();
        return $user && in_array($user->role_id, ['ROLE001', 'ROLE002']);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'pengadaan_detail_id' => [
                'required',
                'exists:pengadaan_detail,pengadaan_detail_id',
            ],
            'user_id' => [
                'required',
                'exists:users,user_id',
                // Pastikan user adalah production worker
                function ($attribute, $value, $fail) {
                    $user = \App\Models\User::find($value);
                    if ($user && $user->role_id !== 'ROLE003') {
                        $fail('User harus menjadi Production Worker');
                    }
                },
            ],
            'jumlah_produksi' => [
                'required',
                'integer',
                'min:1',
                function ($attribute, $value, $fail) {
                    $pengadaanDetail = \App\Models\PengadaanDetail::find(
                        $this->input('pengadaan_detail_id')
                    );
                    if ($pengadaanDetail) {
                        $maxQty = $pengadaanDetail->qty_disetujui ?? $pengadaanDetail->qty_diminta;
                        if ($value > $maxQty) {
                            $fail("Jumlah produksi tidak boleh melebihi $maxQty");
                        }
                    }
                },
            ],
            'deadline' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
            'catatan' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'pengadaan_detail_id' => 'Item Pengadaan',
            'user_id' => 'Worker',
            'jumlah_produksi' => 'Jumlah Produksi',
            'deadline' => 'Tenggat Waktu',
            'catatan' => 'Catatan',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'pengadaan_detail_id.required' => 'Item Pengadaan harus dipilih',
            'pengadaan_detail_id.exists' => 'Item Pengadaan tidak ditemukan',
            'user_id.required' => 'Worker harus dipilih',
            'user_id.exists' => 'Worker tidak ditemukan',
            'jumlah_produksi.required' => 'Jumlah Produksi harus diisi',
            'jumlah_produksi.integer' => 'Jumlah Produksi harus berupa angka',
            'jumlah_produksi.min' => 'Jumlah Produksi minimal 1',
            'deadline.required' => 'Tenggat Waktu harus diisi',
            'deadline.date' => 'Tenggat Waktu harus berupa tanggal',
            'deadline.after_or_equal' => 'Tenggat Waktu tidak boleh di masa lalu',
            'catatan.string' => 'Catatan harus berupa teks',
            'catatan.max' => 'Catatan maksimal 500 karakter',
        ];
    }
}
