<x-auth-card title="Set kata laluan baharu" subtitle="Pilih kata laluan baharu untuk akaun anda.">
    <form method="POST" action="{{ route('password.store') }}" class="flex flex-col gap-4">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <x-form.field label="Emel" name="email" type="email" :value="$email" autocomplete="email" required />
        <x-form.field label="Kata laluan baharu" name="password" type="password" autocomplete="new-password" help="Sekurang-kurangnya 8 aksara." required />
        <x-form.field label="Sahkan kata laluan" name="password_confirmation" type="password" autocomplete="new-password" required />
        <button type="submit" class="rounded-full bg-brand-600 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Simpan kata laluan</button>
    </form>
</x-auth-card>
