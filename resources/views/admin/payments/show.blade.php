<x-layouts.admin :title="$payment->reference.' · '.__('pages.payments.title')" :heading="$payment->reference" :subheading="$payment->purpose->label()">
    {{-- resources/js/components/admin/AdminPaymentDetail.vue --}}
    <div data-vue="admin-payment-detail" data-props="@vueProps($props)"></div>
</x-layouts.admin>
