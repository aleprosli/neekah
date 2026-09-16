@php
    $sections = [
        'perhubungan' => ['label' => 'Perhubungan', 'icon' => '📞'],
        'seo' => ['label' => 'SEO', 'icon' => '🔍'],
        'keselamatan' => ['label' => 'Keselamatan', 'icon' => '🛡️'],
        'telegram' => ['label' => 'Telegram', 'icon' => '📣'],
        'gambar' => ['label' => 'Gambar', 'icon' => '🖼️'],
    ];
@endphp

<x-layouts.admin title="Tetapan" heading="Tetapan" subheading="Maklumat perhubungan, SEO, keselamatan borang, makluman Telegram dan pemprosesan gambar untuk seluruh laman.">
    <div class="flex max-w-3xl min-w-0 flex-col gap-6">
        {{-- A scrollable row of jump links on a phone, a plain row on a laptop. --}}
        <nav class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:flex-wrap sm:px-0" aria-label="Bahagian tetapan">
            @foreach ($sections as $id => $section)
                <a href="#{{ $id }}" class="flex shrink-0 items-center gap-2 rounded-full border border-line px-4 py-2 text-sm font-medium whitespace-nowrap transition hover:border-brand-400 hover:text-brand-700">
                    <span aria-hidden="true">{{ $section['icon'] }}</span>{{ $section['label'] }}
                </a>
            @endforeach
        </nav>

        <section id="perhubungan" class="scroll-mt-28 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6">
            <h2 class="font-semibold">Maklumat perhubungan</h2>
            <p class="mt-1 text-sm text-ink-muted">Dipaparkan di footer setiap halaman awam. Biarkan kosong untuk menyembunyikan satu-satu maklumat.</p>

            <form method="POST" action="{{ route('admin.settings.contact') }}" class="mt-5 flex flex-col gap-4">
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field label="Nombor telefon" name="phone" type="tel" :value="$contact['phone']" placeholder="03-1234 5678" help="Dipaparkan sebagai pautan panggilan." />
                    <x-form.field label="Nombor WhatsApp" name="whatsapp" type="tel" :value="$contact['whatsapp']" placeholder="60123456789" help="Dengan kod negara. Kosong bermakna nombor telefon digunakan." />
                    <x-form.field label="Emel" name="email" type="email" :value="$contact['email']" placeholder="hello@neekah.my" />
                    <x-form.field label="Waktu operasi" name="hours" :value="$contact['hours']" placeholder="Isnin – Jumaat, 9 pagi – 6 petang" />
                </div>

                <x-form.textarea label="Alamat" name="address" :value="$contact['address']" rows="2" placeholder="No. 1, Jalan Contoh, 50000 Kuala Lumpur" />

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field label="Facebook" name="facebook" type="url" :value="$contact['facebook']" placeholder="https://facebook.com/neekahmy" />
                    <x-form.field label="Instagram" name="instagram" type="url" :value="$contact['instagram']" placeholder="https://instagram.com/neekahmy" />
                    <x-form.field label="TikTok" name="tiktok" type="url" :value="$contact['tiktok']" placeholder="https://tiktok.com/@neekahmy" />
                </div>

                <div>
                    <button type="submit" class="w-full rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 sm:w-auto">Simpan maklumat perhubungan</button>
                </div>
            </form>
        </section>

        <section id="seo" class="scroll-mt-28 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6">
            <h2 class="font-semibold">SEO dan pratonton pautan</h2>
            <p class="mt-1 text-sm text-ink-muted">Digunakan pada halaman yang tidak menulis meta tag sendiri, dan sebagai pratonton apabila pautan dikongsi.</p>

            <form method="POST" action="{{ route('admin.settings.seo') }}" class="mt-5 flex flex-col gap-4">
                @csrf
                @method('PUT')

                <x-form.field label="Tagline" name="tagline" :value="$seo['tagline']" required maxlength="{{ App\Support\SeoSettings::TAGLINE_LIMIT }}" help="Muncul selepas nama laman pada tajuk halaman utama." />
                <x-form.textarea label="Penerangan lalai" name="description" :value="$seo['description']" rows="3" required maxlength="{{ App\Support\Seo::DESCRIPTION_LIMIT }}" help="Google memotong sekitar {{ App\Support\Seo::DESCRIPTION_LIMIT }} aksara." />
                <x-form.field label="Akaun X (pilihan)" name="twitter" :value="$seo['twitter']" placeholder="@neekahmy" help="Dikreditkan pada kad pratonton X." />

                <div class="rounded-xl bg-surface-muted px-4 py-3">
                    <p class="text-xs font-medium text-ink-muted">Pratonton hasil carian</p>
                    <p class="mt-2 truncate text-sm text-brand-700">{{ config('app.name') }} — {{ $seo['tagline'] }}</p>
                    <p class="text-xs break-words text-ink-muted">{{ $seo['description'] }}</p>
                </div>

                <div>
                    <button type="submit" class="w-full rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 sm:w-auto">Simpan tetapan SEO</button>
                </div>
            </form>
        </section>

        <section id="keselamatan" class="scroll-mt-28 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <h2 class="font-semibold">Cloudflare Turnstile</h2>
                <span @class(['rounded-full px-3 py-1 text-xs font-medium', 'bg-emerald-100 text-emerald-800' => $turnstileActive, 'bg-surface-muted text-ink-muted' => ! $turnstileActive])>
                    {{ $turnstileActive ? 'Aktif' : 'Tidak aktif' }}
                </span>
            </div>
            <p class="mt-1 text-sm text-ink-muted">Semakan tanpa teka-teki pada borang log masuk, pendaftaran pengantin dan vendor, serta borang tempahan. Dapatkan kunci di dash.cloudflare.com → Turnstile.</p>

            <form method="POST" action="{{ route('admin.settings.turnstile') }}" class="mt-5 flex flex-col gap-4">
                @csrf
                @method('PUT')

                <label class="flex items-start gap-3">
                    <input type="hidden" name="enabled" value="0">
                    <input type="checkbox" name="enabled" value="1" class="mt-1 accent-brand-600" @checked(old('enabled', $turnstile['enabled']))>
                    <span>
                        <span class="text-sm font-medium">Hidupkan Turnstile</span>
                        <span class="block text-xs text-ink-muted">Hanya berjalan apabila kedua-dua kunci diisi, supaya pendaftaran tidak pernah tersekat.</span>
                    </span>
                </label>

                <x-form.field label="Site key" name="site_key" :value="$turnstile['site_key']" placeholder="0x4AAAAAAA..." autocomplete="off" help="Kunci awam, dipaparkan dalam halaman." />
                <x-form.field label="Secret key" name="secret_key" type="password" placeholder="{{ $turnstile['secret_key'] ? 'Tersimpan — biarkan kosong untuk kekalkan' : 'Belum ditetapkan' }}" autocomplete="off" help="Tidak pernah dipaparkan semula selepas disimpan." />

                <div>
                    <button type="submit" class="w-full rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 sm:w-auto">Simpan tetapan Turnstile</button>
                </div>
            </form>
        </section>

        <section id="telegram" class="scroll-mt-28 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <h2 class="font-semibold">Makluman Telegram</h2>
                <span @class(['rounded-full px-3 py-1 text-xs font-medium', 'bg-emerald-100 text-emerald-800' => $telegramActive, 'bg-surface-muted text-ink-muted' => ! $telegramActive])>
                    {{ $telegramActive ? 'Aktif' : 'Tidak aktif' }}
                </span>
            </div>
            <p class="mt-1 text-sm text-ink-muted">Setiap pendaftaran vendor dan pengantin dihantar ke chat admin, lengkap dengan emel dan pautan WhatsApp lead itu. Cipta bot dengan @BotFather, kemudian ambil chat id chat atau kumpulan admin.</p>

            <form method="POST" action="{{ route('admin.settings.telegram') }}" class="mt-5 flex flex-col gap-4">
                @csrf
                @method('PUT')

                <label class="flex items-start gap-3">
                    <input type="hidden" name="enabled" value="0">
                    <input type="checkbox" name="enabled" value="1" class="mt-1 accent-brand-600" @checked(old('enabled', $telegram['enabled']))>
                    <span>
                        <span class="text-sm font-medium">Hantar makluman ke Telegram</span>
                        <span class="block text-xs text-ink-muted">Dihantar melalui queue, jadi pendaftaran tidak pernah menunggu Telegram.</span>
                    </span>
                </label>

                <x-form.field label="Bot token" name="bot_token" type="password" placeholder="{{ $telegram['bot_token'] ? 'Tersimpan — biarkan kosong untuk kekalkan' : '123456:ABC-DEF...' }}" autocomplete="off" help="Tidak pernah dipaparkan semula selepas disimpan." />
                <x-form.field label="Chat id" name="chat_id" :value="$telegram['chat_id']" placeholder="-1001234567890" autocomplete="off" help="Chat peribadi admin atau kumpulan. Kumpulan bermula dengan tanda tolak." />

                <div>
                    <button type="submit" class="w-full rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 sm:w-auto">Simpan tetapan Telegram</button>
                </div>
            </form>
        </section>

        <section id="gambar" class="scroll-mt-28 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6">
            <h2 class="font-semibold">Gambar</h2>
            <p class="mt-1 text-sm text-ink-muted">
                Setiap gambar yang dimuat naik oleh vendor, pasangan dan admin diubah saiz, dimampatkan dan dibuang data EXIF (termasuk lokasi GPS) sebelum disimpan.
                Satu salinan thumbnail turut dijana untuk senarai vendor, grid portfolio dan galeri.
            </p>

            <form method="POST" action="{{ route('admin.settings.update') }}" class="mt-5 flex flex-col gap-4">
                @csrf
                @method('PUT')

                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field label="Saiz maksimum (piksel, sisi terpanjang)" name="max_dimension" type="number" :value="$images['max_dimension']" min="800" max="4000" step="10" required help="1920 sudah tajam untuk skrin penuh. Lebih besar bermakna fail lebih berat." />
                    <x-form.field label="Lebar thumbnail (piksel)" name="thumbnail_width" type="number" :value="$images['thumbnail_width']" min="200" max="1200" step="10" required help="Saiz yang dipaparkan dalam senarai dan grid." />
                    <x-form.field label="Kualiti (40 hingga 95)" name="quality" type="number" :value="$images['quality']" min="40" max="95" required help="80 ialah titik terbaik: sukar dibezakan daripada asal, tetapi fail jauh lebih kecil." />
                    <x-form.select label="Format" name="format" required help="WebP biasanya 25 hingga 35% lebih kecil daripada JPEG pada kualiti yang sama.">
                        @foreach ($formats as $value => $label)
                            <option value="{{ $value }}" @selected(old('format', $images['format']) === $value)>{{ $label }}</option>
                        @endforeach
                    </x-form.select>
                    <x-form.field label="Had saiz muat naik (MB)" name="max_upload_mb" type="number" :value="$images['max_upload_mb']" min="1" max="15" required help="Saiz fail asal yang dibenarkan sebelum diproses. Maksimum 15 MB." />
                </div>

                <p class="rounded-xl bg-surface-muted px-4 py-3 text-xs break-words text-ink-muted">
                    Tetapan ini digunakan untuk gambar yang dimuat naik selepas ini. Gambar lama diproses dengan menjalankan
                    <code class="font-mono">php artisan neekah:optimize-images</code> pada server.
                </p>

                <div>
                    <button type="submit" class="w-full rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 sm:w-auto">Simpan tetapan gambar</button>
                </div>
            </form>
        </section>
    </div>
</x-layouts.admin>
