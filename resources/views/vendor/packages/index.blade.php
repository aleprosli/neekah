<x-layouts.vendor title="Pakej" heading="Pakej" subheading="Pengantin memilih salah satu pakej ini semasa menempah.">
    <x-slot:actions>
        <a href="{{ route('vendor.packages.create') }}" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">+ Tambah pakej</a>
    </x-slot:actions>

    @if ($packages->isEmpty())
        <div class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-line px-6 py-16 text-center">
            <span class="text-4xl">📦</span>
            <h2 class="text-lg font-semibold">Belum ada pakej</h2>
            <p class="max-w-sm text-sm text-ink-muted">Tambah sekurang-kurangnya satu pakej supaya pengantin boleh menempah.</p>
            <a href="{{ route('vendor.packages.create') }}" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Tambah pakej pertama</a>
        </div>
    @else
        <ul class="grid gap-4 md:grid-cols-2">
            @foreach ($packages as $package)
                <li class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-semibold">{{ $package->name }}</h2>
                            <p class="text-sm text-ink-muted">{{ $package->duration }}</p>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold">RM{{ number_format((float) $package->price, 2) }}</p>
                            @unless ($package->is_active)
                                <span class="text-xs text-ink-muted">Tidak aktif</span>
                            @endunless
                        </div>
                    </div>
                    <ul class="flex flex-col gap-1 text-sm text-ink-muted">
                        @foreach ($package->features as $feature)
                            <li class="flex gap-2"><span class="text-brand-600">✓</span>{{ $feature }}</li>
                        @endforeach
                    </ul>
                    <div class="mt-auto flex gap-2 border-t border-line pt-3 text-sm">
                        <a href="{{ route('vendor.packages.edit', $package) }}" class="rounded-full border border-line px-4 py-1.5 font-medium transition hover:border-brand-400">Edit</a>
                        <form method="POST" action="{{ route('vendor.packages.destroy', $package) }}" onsubmit="return confirm('Padam pakej {{ $package->name }}?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-full px-4 py-1.5 font-medium text-ink-muted transition hover:bg-surface-muted hover:text-ink">Padam</button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.vendor>
