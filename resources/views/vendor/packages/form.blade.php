@php $editing = $package->exists; @endphp

<x-layouts.vendor :title="$editing ? 'Edit pakej' : 'Tambah pakej'" :heading="$editing ? 'Edit pakej' : 'Tambah pakej'">
    <form method="POST" action="{{ $editing ? route('vendor.packages.update', $package) : route('vendor.packages.store') }}" class="flex max-w-2xl flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
        @csrf
        @if ($editing)
            @method('PUT')
        @endif

        <x-form.field label="Nama pakej" name="name" :value="$package->name" placeholder="Premium Package" required />
        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.field label="Harga (RM)" name="price" type="number" step="0.01" min="0" :value="$package->price" required />
            <x-form.field label="Tempoh" name="duration" :value="$package->duration" placeholder="10 jam / 1 hari / per pax" />
        </div>
        <x-form.textarea label="Penerangan ringkas" name="description" :value="$package->description" rows="3" />
        <x-form.textarea label="Kandungan pakej" name="features" :value="implode(PHP_EOL, $package->features ?? [])" rows="6" placeholder="2 photographers&#10;500 edited photos&#10;Highlight video" help="Satu item setiap baris." required />

        <label class="flex items-center gap-2 text-sm">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="accent-brand-600" @checked(old('is_active', $package->is_active))>
            Aktif dan boleh ditempah
        </label>

        <div class="flex gap-2 pt-2">
            <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $editing ? 'Simpan' : 'Tambah pakej' }}</button>
            <a href="{{ route('vendor.packages.index') }}" class="rounded-full px-6 py-3 text-sm font-medium text-ink-muted transition hover:bg-surface-muted">Batal</a>
        </div>
    </form>
</x-layouts.vendor>
