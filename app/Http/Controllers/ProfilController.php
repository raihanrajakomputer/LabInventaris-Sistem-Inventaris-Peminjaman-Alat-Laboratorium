<?php

namespace App\Http\Controllers;

use App\Models\Peminjam;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProfilController extends Controller
{
    public function index(Request $request): View
    {
        $peminjam = Peminjam::findOrFail((int) $request->session()->get('auth_user.id'));

        // Pastikan session selalu sinkron dengan foto_profil dan email terbaru
        $authUser = $request->session()->get('auth_user', []);
        $authUser['foto_profil'] = $peminjam->foto_profil;
        $authUser['email'] = $peminjam->email;
        $request->session()->put('auth_user', $authUser);

        return view('profil.index', compact('peminjam'));
    }

    public function updatePhoto(Request $request): RedirectResponse|JsonResponse
    {
        $peminjam = Peminjam::findOrFail((int) $request->session()->get('auth_user.id'));
        $oldPath = $peminjam->foto_profil;
        $newPath = null;

        if ($request->hasFile('foto_profil')) {
            $request->validate([
                'foto_profil' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
            ], [
                'foto_profil.required' => 'Silakan pilih file foto profil terlebih dahulu.',
                'foto_profil.image' => 'File yang diunggah harus berupa gambar.',
                'foto_profil.mimes' => 'Format foto profil harus JPG, JPEG, atau PNG.',
                'foto_profil.max' => 'Ukuran foto profil maksimal 2 MB.',
            ]);

            $newPath = $request->file('foto_profil')->store('profil', 'public');
        } elseif ($request->filled('cropped_image')) {
            $request->validate([
                'cropped_image' => ['required', 'string'],
            ], [
                'cropped_image.required' => 'Data hasil potong foto tidak ditemukan.',
            ]);

            $base64 = (string) $request->input('cropped_image');

            if (! preg_match('/^data:image\/(jpeg|jpg|png);base64,(.+)$/s', $base64, $matches)) {
                $errorMessage = 'Format hasil potong foto harus JPG, JPEG, atau PNG.';

                return $request->expectsJson()
                    ? response()->json(['message' => $errorMessage, 'errors' => ['foto_profil' => [$errorMessage]]], 422)
                    : redirect()->route('profil.index')->withErrors(['foto_profil' => $errorMessage]);
            }

            $extension = $matches[1] === 'png' ? 'png' : 'jpg';
            $binary = base64_decode($matches[2], true);

            if ($binary === false || strlen($binary) > 2 * 1024 * 1024 || @getimagesizefromstring($binary) === false) {
                $errorMessage = 'File gambar hasil potong tidak valid atau ukurannya melebihi 2 MB.';

                return $request->expectsJson()
                    ? response()->json(['message' => $errorMessage, 'errors' => ['foto_profil' => [$errorMessage]]], 422)
                    : redirect()->route('profil.index')->withErrors(['foto_profil' => $errorMessage]);
            }

            $newPath = 'profil/' . Str::random(40) . '.' . $extension;
            Storage::disk('public')->put($newPath, $binary);
        } else {
            $errorMessage = 'Silakan pilih file foto profil terlebih dahulu.';

            return $request->expectsJson()
                ? response()->json(['message' => $errorMessage, 'errors' => ['foto_profil' => [$errorMessage]]], 422)
                : redirect()->route('profil.index')->withErrors(['foto_profil' => $errorMessage]);
        }

        try {
            $peminjam->update([
                'foto_profil' => $newPath,
            ]);
        } catch (\Throwable $e) {
            // Bersihkan file baru agar tidak menjadi orphan file jika update DB gagal
            if (! empty($newPath) && Storage::disk('public')->exists($newPath)) {
                Storage::disk('public')->delete($newPath);
            }

            throw $e;
        }

        // Hapus foto lama di disk public jika ada
        if (! empty($oldPath) && $oldPath !== $newPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        // Perbarui session agar foto mini di sidebar/header langsung berubah
        $authUser = $request->session()->get('auth_user', []);
        $authUser['foto_profil'] = $newPath;
        $request->session()->put('auth_user', $authUser);

        $pesan = empty($peminjam->email)
            ? 'Foto profil berhasil disimpan. Anda juga disarankan melengkapi alamat email pribadi agar memudahkan verifikasi akun.'
            : 'Foto profil berhasil diperbarui. Profil Anda kini sudah lengkap.';

        if ($request->expectsJson()) {
            $request->session()->flash('success', $pesan);

            return response()->json([
                'success' => true,
                'message' => $pesan,
                'foto_profil_url' => $peminjam->fresh()->foto_profil_url,
                'redirect' => route('profil.index'),
            ]);
        }

        return redirect()
            ->route('profil.index')
            ->with('success', $pesan);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $peminjam = Peminjam::findOrFail((int) $request->session()->get('auth_user.id'));

        // Normalisasi email & email_confirmation (trim + lowercase) agar konfirmasi tidak gagal karena spasi/huruf besar
        $request->merge([
            'email' => strtolower(trim((string) $request->input('email'))),
            'email_confirmation' => strtolower(trim((string) $request->input('email_confirmation'))),
        ]);

        // Bebas semua domain email valid (@gmail.com, @yahoo.com, dll.), wajib konfirmasi & unik
        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:100',
                'confirmed',
                Rule::unique('peminjam', 'email')->ignore($peminjam->id_peminjam, 'id_peminjam'),
            ],
            'no_hp' => ['required', 'string', 'max:20'],
        ], [
            'email.required' => 'Alamat email pribadi wajib diisi.',
            'email.email' => 'Format alamat email tidak valid (contoh: nama@gmail.com).',
            'email.confirmed' => 'Konfirmasi email tidak cocok. Silakan ketik ulang alamat email yang sama persis.',
            'email.unique' => 'Alamat email tersebut sudah digunakan oleh akun lain.',
            'no_hp.required' => 'Nomor HP wajib diisi.',
        ]);

        $peminjam->update([
            'email' => $validated['email'],
            'no_hp' => trim($validated['no_hp']),
        ]);

        $authUser = $request->session()->get('auth_user', []);
        $authUser['email'] = $validated['email'];
        $request->session()->put('auth_user', $authUser);

        $pesan = empty($peminjam->foto_profil)
            ? 'Email dan Nomor HP berhasil disimpan. Anda juga disarankan mengunggah foto profil agar memudahkan verifikasi akun.'
            : 'Email dan Nomor HP berhasil disimpan. Profil Anda kini sudah lengkap.';

        return redirect()
            ->route('profil.index')
            ->with('success', $pesan);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $peminjam = Peminjam::findOrFail((int) $request->session()->get('auth_user.id'));

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:4', 'max:100', 'confirmed'],
        ], [
            'current_password.required' => 'Password lama wajib diisi.',
            'new_password.required' => 'Password baru wajib diisi.',
            'new_password.min' => 'Password baru minimal 4 karakter.',
            'new_password.confirmed' => 'Konfirmasi password baru tidak cocok.',
        ]);

        if (! Hash::check($validated['current_password'], (string) $peminjam->password)) {
            return redirect()
                ->route('profil.index')
                ->withErrors(['current_password' => 'Password lama yang Anda masukkan tidak sesuai.']);
        }

        $peminjam->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        // Regenerasi session ID setelah perubahan password (session fixation protection)
        $request->session()->regenerate();

        return redirect()
            ->route('profil.index')
            ->with('success', 'Password akun Anda berhasil diperbarui.');
    }
}
