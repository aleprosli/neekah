@php
    $editing = $package->exists;
    $submitted = old('features');
    $features = collect(is_string($submitted) ? preg_split('/\r\n|\r|\n/', $submitted) : $submitted ?? $package->features ?? [])
        ->map(fn ($feature) => trim((string) $feature))
        ->filter()
        ->values();

    if ($features->isEmpty()) {
        $features = collect(['']);
    }
@endphp

<x-layouts.vendor
    :title="$editing ? 'Edit pakej' : 'Tambah pakej'"
    :heading="$editing ? 'Edit pakej' : 'Tambah pakej'"
    subheading="Pakej yang jelas memudahkan pengantin membandingkan dan terus menempah."
>
    <form
        method="POST"
        action="{{ $editing ? route('vendor.packages.update', $package) : route('vendor.packages.store') }}"
        class="flex max-w-3xl flex-col gap-6"
    >
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <div>
                <h2 class="font-semibold">Maklumat asas</h2>
                <p class="text-sm text-ink-muted">Nama, harga dan tempoh yang dipaparkan pada profil anda.</p>
            </div>

            <x-form.field label="Nama pakej" name="name" :value="$package->name" placeholder="Cth: Pakej Premium" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field label="Harga (RM)" name="price" type="number" step="0.01" min="0" :value="$package->price" required />
                <x-form.field label="Tempoh" name="duration" :value="$package->duration" placeholder="10 jam / 1 hari / per pax" />
            </div>
            <x-form.textarea
                label="Penerangan ringkas"
                name="description"
                :value="$package->description"
                rows="3"
                placeholder="Satu atau dua ayat tentang pakej ini."
            />
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6" data-feature-list>
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Kandungan pakej</h2>
                    <p class="text-sm text-ink-muted">Satu item satu baris. Seret <span aria-hidden="true">⠿</span> untuk susun semula.</p>
                </div>
                <span class="rounded-full bg-surface-muted px-3 py-1 text-xs font-medium text-ink-muted">
                    <span data-feature-count>{{ $features->count() }}</span> item
                </span>
            </div>

            <ul class="flex flex-col gap-2" data-feature-items>
                @foreach ($features as $feature)
                    <li class="flex items-center gap-2 rounded-xl border border-line bg-surface px-2 py-1.5 transition focus-within:border-brand-400 focus-within:ring-2 focus-within:ring-brand-400/40" data-feature-item>
                        <span class="cursor-grab px-1 text-ink-muted select-none" data-feature-handle aria-hidden="true">⠿</span>
                        <span class="text-brand-600" aria-hidden="true">✓</span>
                        <input
                            type="text"
                            name="features[]"
                            value="{{ $feature }}"
                            maxlength="200"
                            aria-label="Item pakej"
                            placeholder="Cth: 2 jurugambar sepanjang majlis"
                            class="min-w-0 flex-1 bg-transparent py-1.5 text-sm focus:outline-none"
                        >
                        <button
                            type="button"
                            data-feature-remove
                            aria-label="Buang item"
                            class="rounded-full px-2 py-1 text-ink-muted transition hover:bg-surface-muted hover:text-ink"
                        >&times;</button>
                    </li>
                @endforeach
            </ul>

            <div class="flex flex-wrap items-center gap-3">
                <button
                    type="button"
                    data-feature-add
                    class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400 hover:text-brand-700"
                >+ Tambah item</button>
                <span class="text-xs text-ink-muted">Tekan Enter untuk terus tambah baris seterusnya.</span>
            </div>
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <label class="flex items-start gap-3">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="mt-1 accent-brand-600" @checked(old('is_active', $package->is_active))>
                <span>
                    <span class="text-sm font-medium">Aktif dan boleh ditempah</span>
                    <span class="block text-xs text-ink-muted">Pakej tidak aktif disembunyikan daripada pengantin.</span>
                </span>
            </label>
        </section>

        <div class="flex flex-wrap gap-2">
            <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $editing ? 'Simpan' : 'Tambah pakej' }}</button>
            <a href="{{ route('vendor.packages.index') }}" class="rounded-full px-6 py-3 text-sm font-medium text-ink-muted transition hover:bg-surface-muted">Batal</a>
        </div>
    </form>
</x-layouts.vendor>
