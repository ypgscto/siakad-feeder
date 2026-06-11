<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProdiAccessService;
use App\Services\SiakadApiService;
use App\Services\SiakadAuthApiService;
use App\Services\SiakadUserSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;
use RuntimeException;

class UserManagementController extends Controller
{
    public function index(Request $request, SiakadApiService $siakad): View
    {
        $prodiList = [];
        $prodiError = null;

        try {
            $prodiList = $siakad->fetchStudyPrograms();
        } catch (RuntimeException $e) {
            $prodiError = $e->getMessage();
        }

        return view('admin.users.index', [
            'title' => 'Master Pengguna',
            'users' => User::query()->orderBy('name')->get(),
            'ssoProfile' => $request->session()->get('sso_profile'),
            'lookupError' => $request->session()->get('lookup_error'),
            'assignableRoles' => config('sifeeder_auth.assignable_roles', ['superadmin', 'admin', 'prodi']),
            'roleLabels' => config('sifeeder_auth.role_labels', []),
            'rolesRequiringProdi' => config('sifeeder_auth.roles_requiring_prodi', ['prodi']),
            'prodiList' => $prodiList,
            'prodiError' => $prodiError,
            'prodiAccess' => app(ProdiAccessService::class),
        ]);
    }

    public function lookup(Request $request, SiakadAuthApiService $authApi): RedirectResponse
    {
        $validated = $request->validate([
            'login' => ['required', 'string', 'max:150'],
        ]);

        $login = trim($validated['login']);
        $profile = $authApi->lookupSso($login);

        if ($profile === null) {
            return back()
                ->with('lookup_error', 'Akun SSO tidak ditemukan, tidak aktif, atau belum memiliki email di Siakad.')
                ->withoutInput();
        }

        $siakadUserId = (string) ($profile['siakad_user_id'] ?? $profile['UserID'] ?? '');
        $siakadLogin = trim((string) ($profile['login'] ?? $profile['Login'] ?? $login));
        $email = strtolower(trim((string) ($profile['email'] ?? '')));

        $existing = User::query()
            ->where(function ($query) use ($siakadUserId, $siakadLogin, $email): void {
                if ($siakadUserId !== '') {
                    $query->orWhere('siakad_user_id', $siakadUserId);
                }
                if ($siakadLogin !== '') {
                    $query->orWhere('siakad_login', $siakadLogin);
                }
                if ($email !== '') {
                    $query->orWhere('email', $email);
                }
            })
            ->first();

        if ($existing) {
            return back()
                ->with('lookup_error', 'Akun sudah terdaftar sebagai '.$existing->name.' ('.$existing->email.').')
                ->withoutInput();
        }

        return back()
            ->with('sso_profile', $profile)
            ->withInput(['login' => $login]);
    }

    public function store(Request $request, SiakadUserSyncService $sync): RedirectResponse
    {
        $assignable = config('sifeeder_auth.assignable_roles', ['superadmin', 'admin', 'prodi']);
        $requiresProdi = config('sifeeder_auth.roles_requiring_prodi', ['prodi']);

        $validated = $request->validate([
            'role' => ['required', Rule::in($assignable)],
            'prodi_id' => [
                Rule::requiredIf(in_array($request->input('role'), $requiresProdi, true)),
                'nullable',
                'string',
                'max:120',
            ],
            'siakad_user_id' => ['required', 'string', 'max:100'],
            'siakad_login' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'jenis_user' => ['nullable', 'string', 'max:10'],
        ]);

        $profile = [
            'siakad_user_id' => $validated['siakad_user_id'],
            'login' => $validated['siakad_login'],
            'email' => $validated['email'],
            'nama' => $validated['name'],
            'jenis_user' => $validated['jenis_user'] ?? '',
            'prodi_id' => $validated['prodi_id'] ?? '',
        ];

        try {
            $sync->provisionFromSsoProfile(
                $profile,
                $validated['role'],
                $validated['prodi_id'] ?? null,
            );
        } catch (InvalidArgumentException $e) {
            return back()
                ->with('lookup_error', $e->getMessage())
                ->with('sso_profile', $profile);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Pengguna berhasil ditambahkan. Login memakai akun Siakad (SSO).');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $assignable = config('sifeeder_auth.assignable_roles', ['superadmin', 'admin', 'prodi']);
        $requiresProdi = config('sifeeder_auth.roles_requiring_prodi', ['prodi']);

        $validated = $request->validate([
            'role' => ['required', Rule::in($assignable)],
            'prodi_id' => [
                Rule::requiredIf(in_array($request->input('role'), $requiresProdi, true)),
                'nullable',
                'string',
                'max:120',
            ],
            'is_active' => ['required', 'boolean'],
        ]);

        if ($user->id === $request->user()->id) {
            if (! $validated['is_active']) {
                return back()->with('error', 'Anda tidak dapat menonaktifkan akun sendiri.');
            }

            if ($validated['role'] !== 'superadmin') {
                return back()->with('error', 'Anda tidak dapat mengubah role akun sendiri.');
            }
        }

        if ($user->isSuperadmin() && $validated['role'] !== 'superadmin') {
            $otherSuperadmins = User::query()
                ->where('role', 'superadmin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherSuperadmins === 0) {
                return back()->with('error', 'Harus ada minimal satu superadmin aktif.');
            }
        }

        $user->update([
            'role' => $validated['role'],
            'prodi_id' => in_array($validated['role'], $requiresProdi, true)
                ? ($validated['prodi_id'] ?? null)
                : null,
            'is_active' => $validated['is_active'],
        ]);

        return back()->with('success', 'Data pengguna diperbarui.');
    }
}
