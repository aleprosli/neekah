<script setup>
/**
 * One report, what the vendor's record looks like, and the decision. Upholding
 * carries an automatic action, so the ladder is spelled out before the click.
 */
import { ref } from 'vue';
import UiTextarea from '../ui/UiTextarea.vue';

defineProps({
    violation: { type: Object, required: true },
    history: { type: Array, default: () => [] },
    vendor: { type: Object, required: true },
    action: { type: String, default: null },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const note = ref('');
</script>

<template>
    <div class="grid gap-6 break-words lg:grid-cols-[minmax(0,1fr)_340px]">
        <div class="flex min-w-0 flex-col gap-6">
            <div class="rounded-2xl border border-line p-5">
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('admin_violation.laporan') }}</p>
                <p class="mt-2 text-sm leading-relaxed whitespace-pre-line">{{ violation.description }}</p>

                <dl class="mt-4 grid gap-2 border-t border-line pt-4 text-sm sm:grid-cols-2 [&>div]:min-w-0">
                    <div>
                        <dt class="text-ink-muted">{{ $t('admin_violation.pelapor') }}</dt>
                        <dd class="font-medium">{{ violation.reporter.name }}</dd>
                        <dd class="break-words text-ink-muted">{{ violation.reporter.email }}</dd>
                    </div>
                    <div v-if="violation.booking">
                        <dt class="text-ink-muted">{{ $t('admin_violation.booking') }}</dt>
                        <dd class="font-medium"><a :href="violation.booking.url" class="hover:text-brand-700">{{ violation.booking.reference }}</a></dd>
                    </div>
                </dl>
            </div>

            <section v-if="history.length" class="rounded-2xl border border-line p-5">
                <h2 class="font-semibold">Sejarah pelanggaran disahkan ({{ history.length }})</h2>
                <ul class="mt-3 divide-y divide-line text-sm">
                    <li v-for="past in history" :key="past.id" class="flex flex-wrap items-center justify-between gap-3 py-2">
                        <span>#{{ past.offence_number }} · {{ past.type }}</span>
                        <span class="text-ink-muted">{{ past.action }} · {{ past.resolved_at }}</span>
                    </li>
                </ul>
            </section>
        </div>

        <aside class="flex min-w-0 flex-col gap-4">
            <form v-if="violation.is_open" :action="violation.update_url" method="POST" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                <input type="hidden" name="_token" :value="csrf">
                <input type="hidden" name="_method" value="PUT">

                <h2 class="text-sm font-semibold">{{ $t('admin_violation.keputusan') }}</h2>

                <p class="rounded-xl bg-amber-50 p-3 text-xs text-amber-900">
                    Jika disahkan, ini adalah pelanggaran ke-{{ violation.next_offence_number }}. Tindakan automatik: <strong>{{ action }}</strong>.
                </p>

                <UiTextarea v-model="note" :label="$t('admin_violation.nota_admin_pilihan')" name="admin_note" :rows="4" :placeholder="$t('admin_violation.hasil_siasatan_bukti_yang_disemak')" :error="errors.admin_note" />

                <button type="submit" name="decision" value="uphold" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('admin_violation.sahkan_kenakan_tindakan') }}</button>
                <button type="submit" name="decision" value="dismiss" class="rounded-full border border-line px-5 py-2.5 text-sm font-medium transition hover:border-brand-400">{{ $t('admin_violation.tolak_laporan') }}</button>
            </form>

            <div v-else class="flex flex-col gap-2 rounded-2xl border border-line bg-surface-raised p-5 text-sm">
                <h2 class="font-semibold">{{ violation.status }}</h2>
                <p v-if="violation.action">{{ $t('admin_violation.tindakan') }}<strong>{{ violation.action }}</strong> (pelanggaran ke-{{ violation.offence_number }})</p>
                <p v-if="violation.admin_note" class="text-ink-muted">{{ violation.admin_note }}</p>
                <p class="text-xs text-ink-muted">Diselesaikan oleh {{ violation.resolver }} pada {{ violation.resolved_at }}</p>
            </div>

            <dl class="flex flex-col gap-2 rounded-2xl border border-line p-5 text-sm">
                <div v-for="row in vendor.summary" :key="row.label" class="flex justify-between gap-3">
                    <dt class="text-ink-muted">{{ row.label }}</dt>
                    <dd class="font-medium">{{ row.value }}</dd>
                </div>
            </dl>
        </aside>
    </div>
</template>
