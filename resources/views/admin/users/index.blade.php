<x-app-layout :title="$title">
    <div class="max-w-6xl mx-auto space-y-6">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Master Pengguna</h2>
            <p class="text-sm text-slate-500 mt-1">
                Tambah pengguna dari akun SSO Siakad · Login ke Siakad-Feeder memakai kredensial Siakad yang sama
            </p>
        </div>

        <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm space-y-4">
            <h3 class="text-sm font-semibold text-slate-800">Cari akun SSO Siakad</h3>
            <p class="text-xs text-slate-500">Masukkan email atau username Siakad yang sudah terdaftar SSO.</p>

            @if ($lookupError)
                <div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-4 py-3 text-sm">{{ $lookupError }}</div>
            @endif

            <form method="POST" action="{{ route('admin.users.lookup') }}" class="flex flex-col sm:flex-row gap-3">
                @csrf
                <input
                    type="text"
                    name="login"
                    value="{{ old('login') }}"
                    placeholder="email@kampus.ac.id atau username"
                    class="flex-1 rounded-lg border-slate-300 text-sm"
                    required
                >
                <button type="submit" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold rounded-lg shrink-0">
                    Cari SSO
                </button>
            </form>

            @if ($ssoProfile)
                @php
                    $pid = (string) ($ssoProfile['siakad_user_id'] ?? $ssoProfile['UserID'] ?? '');
                    $plogin = (string) ($ssoProfile['login'] ?? $ssoProfile['Login'] ?? '');
                    $pname = (string) ($ssoProfile['nama'] ?? $ssoProfile['Nama'] ?? $plogin);
                    $pemail = (string) ($ssoProfile['email'] ?? '');
                    $pjenis = (string) ($ssoProfile['jenis_user'] ?? $ssoProfile['JenisUser'] ?? '');
                @endphp
                <div class="rounded-lg border border-teal-200 bg-teal-50/50 p-4 space-y-4">
                    <div class="text-sm text-slate-700">
                        <p><strong>Nama:</strong> {{ $pname }}</p>
                        <p><strong>Login Siakad:</strong> {{ $plogin }}</p>
                        <p><strong>Email SSO:</strong> {{ $pemail }}</p>
                        @if ($plogin !== $pemail && $pemail !== '')
                            <p class="text-xs text-amber-700 mt-1">Saat login, coba username <strong>{{ $plogin }}</strong> atau email <strong>{{ $pemail }}</strong> dengan password Siakad.</p>
                        @endif
                        <p><strong>ID Siakad:</strong> {{ $pid ?: '—' }}</p>
                        @if ($pjenis !== '')
                            <p><strong>Jenis user Siakad:</strong> {{ $pjenis }}</p>
                        @endif
                    </div>

                    @php
                        $ssoProdiId = (string) ($ssoProfile['prodi_id'] ?? '');
                        $defaultRole = $pjenis === '6' ? 'prodi' : 'admin';
                    @endphp
                    <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-3"
                          x-data="{ role: @js(old('role', $defaultRole)) }">
                        @csrf
                        <input type="hidden" name="siakad_user_id" value="{{ $pid }}">
                        <input type="hidden" name="siakad_login" value="{{ $plogin }}">
                        <input type="hidden" name="email" value="{{ $pemail }}">
                        <input type="hidden" name="name" value="{{ $pname }}">
                        <input type="hidden" name="jenis_user" value="{{ $pjenis }}">

                        <div class="grid gap-3 sm:grid-cols-2">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Role di Siakad-Feeder</label>
                                <select name="role" x-model="role" class="w-full rounded-lg border-slate-300 text-sm" required>
                                    @foreach ($assignableRoles as $role)
                                        <option value="{{ $role }}">{{ $roleLabels[$role] ?? ucfirst($role) }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-slate-400 mt-1">Ketua Prodi hanya melihat data prodi yang ditugaskan.</p>
                            </div>
                            <div x-show="role === 'prodi'" x-cloak>
                                <label class="block text-xs font-medium text-slate-600 mb-1">Program Studi</label>
                                @if ($prodiError)
                                    <p class="text-xs text-red-600">{{ $prodiError }}</p>
                                @else
                                    <select name="prodi_id" class="w-full rounded-lg border-slate-300 text-sm" :required="role === 'prodi'">
                                        <option value="">— Pilih prodi —</option>
                                        @foreach ($prodiList as $row)
                                            @php $id = (string) ($row['id'] ?? ''); @endphp
                                            <option value="{{ $id }}" @selected(old('prodi_id', $ssoProdiId) === $id)>{{ $row['nama'] ?? $id }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        </div>

                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg">
                            Simpan Pengguna
                        </button>
                    </form>
                </div>
            @endif
        </div>

        <div class="rounded-xl bg-white border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-slate-200">
                <span class="text-sm font-medium text-slate-700">{{ $users->count() }} pengguna terdaftar</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                        <tr>
                            <th class="px-3 py-3">Nama</th>
                            <th class="px-3 py-3">Email / Login</th>
                            <th class="px-3 py-3">Role</th>
                            <th class="px-3 py-3">Prodi</th>
                            <th class="px-3 py-3">Status</th>
                            <th class="px-3 py-3 w-56">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($users as $user)
                            <tr class="hover:bg-slate-50">
                                <td class="px-3 py-2">{{ $user->name }}</td>
                                <td class="px-3 py-2">
                                    <div class="font-mono text-xs">{{ $user->email }}</div>
                                    @if ($user->siakad_login)
                                        <div class="text-xs text-slate-400">{{ $user->siakad_login }}</div>
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <span @class([
                                        'inline-flex px-2 py-0.5 rounded-full text-xs font-medium',
                                        'bg-violet-100 text-violet-800' => $user->isSuperadmin(),
                                        'bg-sky-100 text-sky-800' => $user->isAdmin(),
                                        'bg-emerald-100 text-emerald-800' => $user->isProdi(),
                                    ])>
                                        {{ $roleLabels[$user->role] ?? $user->role }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 text-xs text-slate-600">
                                    @if ($user->isProdi() && $user->prodi_id)
                                        {{ $prodiAccess->prodiLabel($prodiList, (string) $user->prodi_id) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-3 py-2">
                                    <span @class([
                                        'inline-flex px-2 py-0.5 rounded-full text-xs',
                                        'bg-emerald-100 text-emerald-800' => $user->is_active,
                                        'bg-slate-100 text-slate-600' => ! $user->is_active,
                                    ])>
                                        {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                    </span>
                                </td>
                                <td class="px-3 py-2">
                                    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="flex flex-wrap items-center gap-2"
                                          x-data="{ role: @js($user->role) }">
                                        @csrf
                                        @method('PATCH')
                                        <select name="role" x-model="role" class="rounded border-slate-300 text-xs py-1">
                                            @foreach ($assignableRoles as $role)
                                                <option value="{{ $role }}">{{ $roleLabels[$role] ?? $role }}</option>
                                            @endforeach
                                        </select>
                                        <select name="prodi_id" x-show="role === 'prodi'" x-cloak class="rounded border-slate-300 text-xs py-1 max-w-[10rem]">
                                            <option value="">— Prodi —</option>
                                            @foreach ($prodiList as $row)
                                                @php $id = (string) ($row['id'] ?? ''); @endphp
                                                <option value="{{ $id }}" @selected($user->prodi_id === $id)>{{ $row['nama'] ?? $id }}</option>
                                            @endforeach
                                        </select>
                                        <select name="is_active" class="rounded border-slate-300 text-xs py-1">
                                            <option value="1" @selected($user->is_active)>Aktif</option>
                                            <option value="0" @selected(! $user->is_active)>Nonaktif</option>
                                        </select>
                                        <button type="submit" class="text-xs text-teal-700 font-medium hover:underline">Simpan</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-500">Belum ada pengguna. Cari akun SSO di atas.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
