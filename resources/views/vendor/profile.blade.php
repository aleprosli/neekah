@php use App\Enums\PriceUnit; @endphp

<x-layouts.vendor title="Profil vendor" heading="Profil" subheading="Maklumat ini dipaparkan pada kad dan halaman vendor anda.">
    <form method="POST" action="{{ route('vendor.profile.update') }}" enctype="multipart/form-data" class="flex flex-col gap-8">
        @csrf
        @method('PUT')

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-semibold">Maklumat asas</h2>
            <x-form.field label="Nama perniagaan" name="name" :value="$vendor->name" required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select label="Kategori" name="category_id" required>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) old('category_id', $vendor->category_id) === $category->id)>{{ $category->icon }} {{ $category->name }}</option>
                    @endforeach
                </x-form.select>
                <x-form.select label="Negeri" name="state" required>
                    @foreach ($states as $state)
                        <option value="{{ $state }}" @selected(old('state', $vendor->state) === $state)>{{ $state }}</option>
                    @endforeach
                </x-form.select>
            </div>
            <x-form.field label="Bandar" name="city" :value="$vendor->city" required />
            <x-form.field label="Tagline" name="tagline" :value="$vendor->tagline" help="Satu ayat pendek pada kad vendor, maksimum 160 aksara." />
            <x-form.textarea label="Penerangan" name="description" :value="$vendor->description" rows="6" help="Ceritakan perkhidmatan, pengalaman dan apa yang membezakan anda." />
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-semibold">Hubungi & harga</h2>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field label="Telefon" name="phone" type="tel" :value="$vendor->phone" />
                <x-form.field label="WhatsApp" name="whatsapp" type="tel" :value="$vendor->whatsapp" help="Nombor dengan kod negara, contoh 60123456789." />
            </div>
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.field label="Harga bermula (RM)" name="price_from" type="number" step="0.01" min="0" :value="$vendor->price_from" help="Diselaraskan automatik dengan pakej termurah bila anda tambah pakej." required />
                <x-form.select label="Unit harga" name="price_unit" required>
                    @foreach (PriceUnit::cases() as $unit)
                        <option value="{{ $unit->value }}" @selected(old('price_unit', $vendor->price_unit->value) === $unit->value)>Setiap {{ $unit->label() }}</option>
                    @endforeach
                </x-form.select>
            </div>
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-semibold">Rupa kad vendor</h2>
            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium">Warna latar</span>
                <div class="flex flex-wrap gap-2">
                    @foreach ($tones as $tone)
                        <label class="cursor-pointer">
                            <input type="radio" name="cover_tone" value="{{ $tone }}" class="peer sr-only" @checked(old('cover_tone', $vendor->cover_tone) === $tone)>
                            <span class="block size-12 rounded-xl bg-linear-to-br ring-2 ring-transparent ring-offset-2 ring-offset-surface transition peer-checked:ring-brand-600 {{ $tone }}"></span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium">Gambar muka depan</span>
                @if ($vendor->cover_image)
                    <img src="{{ Storage::disk('public')->url($vendor->cover_image) }}" alt="" class="h-40 w-full max-w-xs rounded-xl object-cover">
                @endif
                <input type="file" name="cover_image" accept="image/*" class="text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700">
                <span class="text-xs text-ink-muted">JPG, PNG atau WebP, maksimum 4MB. Jika tiada gambar, warna latar digunakan.</span>
            </div>
        </section>

        <div>
            <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Simpan profil</button>
        </div>
    </form>
</x-layouts.vendor>
