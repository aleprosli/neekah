<x-auth-card title="Satu langkah lagi" subtitle="Masukkan nombor telefon anda supaya vendor boleh menghubungi anda selepas anda menempah.">
    <form method="POST" action="{{ route('phone.store') }}" class="flex flex-col gap-4">
        @csrf
        <x-form.field
            label="Nombor telefon"
            name="phone"
            type="tel"
            autocomplete="tel"
            placeholder="012-345 6789"
            required
            help="Nombor WhatsApp anda. Kami tidak memaparkannya kepada sesiapa selain vendor yang anda tempah."
        />

        <button type="submit" class="rounded-full bg-brand-600 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Simpan dan teruskan</button>
    </form>
</x-auth-card>
