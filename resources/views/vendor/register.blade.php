<x-layouts.app title="Daftar sebagai vendor">
    <x-site.header />

    <main class="mx-auto max-w-3xl px-4 pt-24 pb-24 sm:px-6 lg:pt-28">
        <div class="text-center">
            <p class="text-sm font-semibold tracking-wide text-brand-600 uppercase">Untuk vendor</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight sm:text-4xl">Sertai Neekah sebagai vendor</h1>
            <p class="mx-auto mt-3 max-w-xl text-ink-muted">Daftar percuma. Selepas admin meluluskan profil anda, perniagaan anda akan dipaparkan di marketplace dan pengantin boleh tempah terus.</p>
        </div>

        <form method="POST" action="{{ route('vendor.register') }}" class="mt-10 flex flex-col gap-8">
            @csrf

            @if ($errors->any())
                <ul class="flex flex-col gap-1 rounded-2xl bg-brand-50 p-4 text-sm text-brand-800 dark:bg-brand-900/40 dark:text-brand-100">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <section class="flex flex-col gap-4 rounded-3xl border border-line bg-surface-raised p-6 sm:p-8">
                <h2 class="font-display text-xl font-semibold">Perniagaan anda</h2>
                <x-form.field label="Nama perniagaan" name="business_name" placeholder="ABC Wedding Photography" required />
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.select label="Kategori" name="category_id" required>
                        <option value="">Pilih kategori</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('category_id') === $category->id)>{{ $category->icon }} {{ $category->name }}</option>
                        @endforeach
                    </x-form.select>
                    <x-form.select label="Negeri" name="state" required>
                        <option value="">Pilih negeri</option>
                        @foreach ($states as $state)
                            <option value="{{ $state }}" @selected(old('state') === $state)>{{ $state }}</option>
                        @endforeach
                    </x-form.select>
                </div>
                <x-form.field label="Bandar" name="city" placeholder="Alor Setar" required />
                <x-form.field label="Tagline" name="tagline" placeholder="Candid, natural light wedding photography." help="Satu ayat pendek yang dipaparkan pada kad vendor." />
            </section>

            <section class="flex flex-col gap-4 rounded-3xl border border-line bg-surface-raised p-6 sm:p-8">
                <h2 class="font-display text-xl font-semibold">Akaun pemilik</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field label="Nama penuh" name="name" autocomplete="name" required />
                    <x-form.field label="Nombor telefon" name="phone" type="tel" autocomplete="tel" placeholder="012-345 6789" required />
                </div>
                <x-form.field label="Emel" name="email" type="email" autocomplete="email" required />
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field label="Kata laluan" name="password" type="password" autocomplete="new-password" required />
                    <x-form.field label="Sahkan kata laluan" name="password_confirmation" type="password" autocomplete="new-password" required />
                </div>
            </section>

            <div class="flex flex-col items-center gap-3">
                <button type="submit" class="w-full rounded-full bg-brand-600 py-3.5 text-sm font-semibold text-white transition hover:bg-brand-700 sm:w-auto sm:px-10">Daftar sebagai vendor</button>
                <p class="text-sm text-ink-muted">Sudah ada akaun? <a href="{{ route('login') }}" class="font-medium text-brand-600 underline underline-offset-4">Log masuk</a></p>
            </div>
        </form>
    </main>

    <x-site.footer />
</x-layouts.app>
