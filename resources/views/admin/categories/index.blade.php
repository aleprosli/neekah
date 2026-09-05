<x-layouts.admin title="Kategori" heading="Kategori" subheading="Kategori menentukan bar carian dan penapis di marketplace.">
    <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
        <div class="overflow-x-auto rounded-2xl border border-line">
            <table class="w-full text-sm">
                <thead class="bg-surface-muted text-left text-xs tracking-wide text-ink-muted uppercase">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Kategori</th>
                        <th class="px-4 py-3 text-right font-semibold">Vendor</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($categories as $category)
                        <tr>
                            <td class="px-4 py-3">
                                <form method="POST" action="{{ route('admin.categories.update', $category) }}" class="flex flex-wrap items-center gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="icon" value="{{ $category->icon }}" class="w-14 rounded-lg border border-line bg-surface px-2 py-1.5 text-center" aria-label="Ikon">
                                    <input type="text" name="name" value="{{ $category->name }}" class="w-32 sm:w-40 rounded-lg border border-line bg-surface px-3 py-1.5" aria-label="Nama">
                                    <input type="text" name="examples" value="{{ $category->examples }}" class="w-40 sm:w-56 rounded-lg border border-line bg-surface px-3 py-1.5 text-xs" aria-label="Contoh">
                                    <input type="number" name="sort_order" value="{{ $category->sort_order }}" class="w-16 rounded-lg border border-line bg-surface px-2 py-1.5" aria-label="Susunan">
                                    <label class="flex items-center gap-1 text-xs">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" class="accent-brand-600" @checked($category->is_active)>
                                        Aktif
                                    </label>
                                    <button type="submit" class="rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400">Simpan</button>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-right">{{ $category->vendors_count }}</td>
                            <td class="px-4 py-3">
                                <span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-100 text-emerald-800' => $category->is_active, 'bg-surface-muted text-ink-muted' => ! $category->is_active])>{{ $category->is_active ? 'Aktif' : 'Tidak aktif' }}</span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($category->vendors_count === 0)
                                    <x-confirm-action
                                        :action="route('admin.categories.destroy', $category)"
                                        method="DELETE"
                                        tone="danger"
                                        :title="'Padam kategori '.$category->name.'?'"
                                        message="Kategori ini tiada vendor, jadi ia selamat dipadam. Tindakan ini tidak boleh dibatalkan."
                                        confirm="Padam kategori"
                                        trigger-class="text-xs font-medium text-ink-muted hover:text-brand-700"
                                    >Padam</x-confirm-action>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <form method="POST" action="{{ route('admin.categories.store') }}" class="flex h-fit flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
            @csrf
            <h2 class="font-semibold">Tambah kategori</h2>
            <x-form.field label="Nama" name="name" placeholder="Kereta Pengantin" required />
            <x-form.field label="Ikon (emoji)" name="icon" placeholder="🚗" required />
            <x-form.field label="Contoh" name="examples" placeholder="Sewa kereta, deco kereta" />
            <x-form.field label="Susunan" name="sort_order" type="number" value="99" />
            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="accent-brand-600" checked>
                Aktif
            </label>
            <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Tambah</button>
        </form>
    </div>
</x-layouts.admin>
