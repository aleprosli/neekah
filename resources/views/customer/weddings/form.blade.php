@php $editing = $wedding->exists; @endphp

<x-layouts.customer :title="$editing ? 'Edit majlis' : 'Cipta wedding project'" :heading="$editing ? 'Edit majlis' : 'Cipta wedding project'" subheading="Maklumat ini digunakan untuk cadangan vendor, bajet dan tarikh tempahan.">
    <form method="POST" action="{{ $editing ? route('weddings.update', $wedding) : route('weddings.store') }}" class="flex max-w-2xl flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <x-form.field label="Nama majlis" name="title" :value="$wedding->title" placeholder="Aina & Hakim" required />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.field label="Tarikh majlis" name="event_date" type="date" :value="$wedding->event_date?->toDateString()" required />
            <x-form.field label="Bajet (RM)" name="budget" type="number" step="100" min="0" :value="$wedding->budget ?? 30000" required />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.field label="Bandar" name="city" :value="$wedding->city" placeholder="Alor Setar" required />
            <x-form.select label="Negeri" name="state" required>
                <option value="">Pilih negeri</option>
                @foreach ($states as $state)
                    <option value="{{ $state }}" @selected(old('state', $wedding->state) === $state)>{{ $state }}</option>
                @endforeach
            </x-form.select>
        </div>
        <x-form.textarea label="Nota (pilihan)" name="notes" :value="$wedding->notes" rows="3" placeholder="Tema, jumlah tetamu, permintaan khas" />

        <div class="flex gap-2 pt-2">
            <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $editing ? 'Simpan' : 'Cipta majlis' }}</button>
            <a href="{{ route('dashboard') }}" class="rounded-full px-6 py-3 text-sm font-medium text-ink-muted transition hover:bg-surface-muted">Batal</a>
        </div>
    </form>
</x-layouts.customer>
