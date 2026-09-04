@php use App\Enums\UserRole; @endphp

<x-layouts.admin title="Pengguna" heading="Pengguna" subheading="Semua akaun pengantin, vendor dan admin.">
    <form method="GET" action="{{ route('admin.users.index') }}" class="mb-6 flex flex-col gap-3">
        @if ($role)
            <input type="hidden" name="role" value="{{ $role->value }}">
        @endif
        <div class="flex gap-2">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Cari nama atau emel…" class="flex-1 rounded-full border border-line bg-surface px-5 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            <button type="submit" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Cari</button>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.users.index') }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium', 'border-brand-600 bg-brand-600 text-white' => ! $role, 'border-line hover:border-brand-400' => $role])>Semua ({{ $counts->sum() }})</a>
            @foreach (UserRole::cases() as $case)
                <a href="{{ route('admin.users.index', ['role' => $case->value]) }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium', 'border-brand-600 bg-brand-600 text-white' => $role === $case, 'border-line hover:border-brand-400' => $role !== $case])>{{ $case->label() }} ({{ $counts[$case->value] ?? 0 }})</a>
            @endforeach
        </div>
    </form>

    <div class="overflow-x-auto rounded-2xl border border-line">
        <table class="w-full text-sm">
            <thead class="bg-surface-muted text-left text-xs tracking-wide text-ink-muted uppercase">
                <tr>
                    <th class="px-4 py-3 font-semibold">Nama</th>
                    <th class="px-4 py-3 font-semibold">Emel</th>
                    <th class="px-4 py-3 font-semibold">Peranan</th>
                    <th class="px-4 py-3 text-right font-semibold">Tempahan</th>
                    <th class="px-4 py-3 font-semibold">Daftar</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @foreach ($users as $user)
                    <tr class="transition hover:bg-surface-muted/60">
                        <td class="px-4 py-3 font-medium">{{ $user->name }}</td>
                        <td class="px-4 py-3 text-ink-muted">{{ $user->email }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $user->role->label() }}</td>
                        <td class="px-4 py-3 text-right">{{ $user->bookings_count }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-ink-muted">{{ $user->created_at->translatedFormat('j M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            @if ($user->canBeImpersonated())
                                <form method="POST" action="{{ route('admin.users.impersonate', $user) }}" onsubmit="return confirm('Log masuk sebagai {{ $user->name }}? Tindakan ini direkod dalam log.')">
                                    @csrf
                                    <button type="submit" class="rounded-full border border-line px-3 py-1.5 text-xs font-medium whitespace-nowrap transition hover:border-brand-400 hover:text-brand-700">Impersonate</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $users->links() }}</div>
</x-layouts.admin>
