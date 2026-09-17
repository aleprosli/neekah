@php
    $facts = [
        ['label' => 'Emel', 'value' => $user->email],
        ['label' => 'Telefon', 'value' => $user->phone ?: '—'],
        ['label' => 'Peranan', 'value' => $user->role->label()],
        ['label' => 'Status', 'value' => $user->isDeactivated() ? 'Dinyahaktif sejak '.$user->deactivated_at->translatedFormat('j M Y') : 'Aktif'],
        ['label' => 'Daftar', 'value' => $user->created_at->translatedFormat('j M Y').($user->google_id ? ' · Google' : '')],
        ['label' => 'Majlis', 'value' => $user->weddings_count.' dikongsi · '.$user->created_weddings_count.' dicipta'],
        ['label' => 'Tempahan (sebagai pengantin)', 'value' => $user->bookings_count],
        ['label' => 'Enquiry · review', 'value' => $user->enquiries_count.' · '.$user->reviews_count],
    ];
    $unavailable = 'rounded-2xl border border-dashed border-line p-4 text-sm text-ink-muted';
    $card = 'flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5';
    $lineButton = 'self-start rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400 hover:text-brand-700';
    $dangerButton = 'self-start rounded-full border border-red-200 px-4 py-2 text-sm font-medium text-red-700 transition hover:border-red-400';
@endphp

<x-layouts.admin :title="$user->name" :heading="$user->name" :subheading="$user->role->label().' · '.$user->email">
    <x-slot:actions>
        @if ($user->vendor)
            <a href="{{ route('admin.vendors.show', $user->vendor) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Profil vendor</a>
        @endif
        @if ($user->canBeImpersonated())
            <x-confirm-action
                :action="route('admin.users.impersonate', $user)"
                :title="'Log masuk sebagai '.$user->name.'?'"
                message="Anda akan melihat Neekah persis seperti pengguna ini. Pembayaran dimatikan, dan tindakan ini direkod dalam log sistem."
                confirm="Ya, impersonate"
                trigger-class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400 hover:text-brand-700"
            >Impersonate</x-confirm-action>
        @endif
    </x-slot:actions>

    <div class="grid gap-6 break-words lg:grid-cols-[minmax(0,1fr)_360px]">
        <dl class="grid min-w-0 content-start gap-3 rounded-2xl border border-line p-5 text-sm sm:grid-cols-2 [&>div]:min-w-0">
            @foreach ($facts as $fact)
                <div>
                    <dt class="text-ink-muted">{{ $fact['label'] }}</dt>
                    <dd class="font-semibold">{{ $fact['value'] }}</dd>
                </div>
            @endforeach
        </dl>

        <aside class="flex min-w-0 flex-col gap-4">
            @if ($user->isAdmin())
                <p class="{{ $unavailable }}">Akaun admin tidak diurus dari sini.</p>
            @else
                @if ($user->isCustomer())
                    <div class="{{ $card }}">
                        <h2 class="text-sm font-semibold">Tukar ke akaun vendor</h2>
                        @if ($can['switchToVendor'])
                            <p class="text-sm text-ink-muted">Untuk vendor yang tersilap daftar sebagai pengantin. Isi maklumat perniagaan; profil akan menunggu kelulusan.</p>
                            <details @if ($errors->any()) open @endif>
                                <summary class="{{ $lineButton }} cursor-pointer list-none">Isi maklumat perniagaan</summary>
                                {{-- resources/js/components/vendor/VendorRegisterForm.vue --}}
                                <div data-vue="vendor-register-form" data-props="@vueProps($vendorForm)"></div>
                            </details>
                        @else
                            <p class="text-sm text-ink-muted">Tidak boleh: akaun ini ada tempahan sebagai pengantin. Nyahaktifkan akaun dan minta mereka daftar vendor dengan emel lain.</p>
                        @endif
                    </div>
                @endif

                @if ($user->isVendor())
                    <div class="{{ $card }}">
                        <h2 class="text-sm font-semibold">Tukar ke akaun pengantin</h2>
                        @if ($can['switchToCouple'])
                            <p class="text-sm text-ink-muted">Untuk pengantin yang tersilap daftar sebagai vendor. Profil vendor, pakej dan portfolio akan dipadam.</p>
                            <x-confirm-action
                                :action="route('admin.users.vendor.destroy', $user)"
                                method="DELETE"
                                :title="'Tukar '.$user->name.' ke akaun pengantin?'"
                                message="Profil vendor, pakej dan gambar portfolio akaun ini akan dipadam. Tindakan ini tidak boleh diundur."
                                confirm="Ya, tukar"
                                tone="danger"
                                :trigger-class="$lineButton"
                            >Tukar ke pengantin</x-confirm-action>
                        @else
                            <p class="text-sm text-ink-muted">Tidak boleh: vendor ini sudah ada tempahan, enquiry atau review.</p>
                        @endif
                    </div>
                @endif

                <div class="{{ $card }}">
                    <h2 class="text-sm font-semibold">{{ $user->isDeactivated() ? 'Aktifkan semula' : 'Nyahaktifkan akaun' }}</h2>
                    @if ($can['reactivate'])
                        <p class="text-sm text-ink-muted">Pengguna ini boleh log masuk semula. @if ($user->vendor)Profil vendor kekal digantung sehingga diluluskan semula.@endif</p>
                        <x-confirm-action
                            :action="route('admin.users.reactivate', $user)"
                            method="DELETE"
                            :title="'Aktifkan semula '.$user->name.'?'"
                            confirm="Ya, aktifkan"
                            :trigger-class="$lineButton"
                        >Aktifkan semula</x-confirm-action>
                    @elseif ($can['deactivate'])
                        <p class="text-sm text-ink-muted">Pengguna dilog keluar dan tidak boleh log masuk. Semua rekod dikekalkan. @if ($user->vendor)Profil vendor turut digantung.@endif</p>
                        <x-confirm-action
                            :action="route('admin.users.deactivate', $user)"
                            :title="'Nyahaktifkan '.$user->name.'?'"
                            message="Pengguna ini akan dilog keluar dan tidak boleh log masuk sehingga diaktifkan semula."
                            confirm="Ya, nyahaktifkan"
                            tone="danger"
                            :trigger-class="$dangerButton"
                        >Nyahaktifkan</x-confirm-action>
                    @endif
                </div>

                <div class="{{ $card }}">
                    <h2 class="text-sm font-semibold">Padam akaun</h2>
                    @if ($can['delete'])
                        <p class="text-sm text-ink-muted">Memadam akaun ini beserta majlis, enquiry dan gambar yang dimuat naik.</p>
                        <x-confirm-action
                            :action="route('admin.users.destroy', $user)"
                            method="DELETE"
                            :title="'Padam akaun '.$user->name.'?'"
                            message="Akaun, majlis, enquiry, review dan gambar yang dimuat naik akan dipadam kekal. Tindakan ini tidak boleh diundur."
                            confirm="Ya, padam kekal"
                            tone="danger"
                            :trigger-class="$dangerButton"
                        >Padam akaun</x-confirm-action>
                    @else
                        <p class="text-sm text-ink-muted">Tidak boleh dipadam: akaun ini ada tempahan, atau majlis yang dikongsi dengan pengguna lain. Nyahaktifkan sahaja supaya rekod kekal.</p>
                    @endif
                </div>
            @endif
        </aside>
    </div>
</x-layouts.admin>
