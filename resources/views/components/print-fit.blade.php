{{-- One sheet of A4, whatever the document holds. Print tightens the
     spacing ([data-doc] and its children); if the sheet is still taller than
     a page, it is scaled down (zoom) to fit on one page rather than spill
     onto a second. The height is measured at print width with the same
     tightened rules applied under html.nk-measure, because a browser still
     lays a page out for the screen while beforeprint runs. --}}
@php
    $rules = <<<'CSS'
{p}[data-doc] { width: 190mm; max-width: none; border-radius: 0; box-shadow: none; font-size: 11px; }
{p}[data-doc] [data-doc-body] { padding: 5mm 0 0 0; }
{p}[data-doc] .text-sm { font-size: 10.5px; line-height: 1.45; }
{p}[data-doc] .text-xs { font-size: 9.5px; line-height: 1.4; }
{p}[data-doc] .text-base { font-size: 12px; }
{p}[data-doc] .text-2xl { font-size: 17px; line-height: 1.2; }
{p}[data-doc] .text-4xl { font-size: 24px; line-height: 1.1; }
{p}[data-doc] .mt-12, {p}[data-doc] .mt-10, {p}[data-doc] .mt-8 { margin-top: 12px; }
{p}[data-doc] .mt-6 { margin-top: 10px; }
{p}[data-doc] .py-6 { padding-top: 10px; padding-bottom: 10px; }
{p}[data-doc] .py-4 { padding-top: 5px; padding-bottom: 5px; }
{p}[data-doc] .pt-6 { padding-top: 10px; }
{p}[data-doc] .p-5 { padding: 10px; }
{p}[data-doc] .gap-6 { gap: 12px; }
{p}[data-doc] .size-16 { width: 40px; height: 40px; }
{p}[data-doc] [data-doc-features] { display: none; }
{p}[data-doc] [data-doc-features-inline] { display: block; }
CSS;
@endphp
<style>
    @page { size: A4; margin: 10mm; }
    @media print {
        body { background: #fff !important; }
        {!! str_replace('{p}', '', $rules) !!}
        [data-doc][data-fit] { zoom: var(--fit); width: calc(190mm / var(--fit)); }
    }
    {!! str_replace('{p}', 'html.nk-measure ', $rules) !!}
</style>
<script>
    (() => {
        // 297mm less the two 10mm margins, less a little for rounding.
        const pageHeight = (277 - 3) * (96 / 25.4);
        const fit = () => {
            const sheet = document.querySelector('[data-doc]');
            if (!sheet) return;
            sheet.removeAttribute('data-fit');
            document.documentElement.classList.add('nk-measure');
            const height = sheet.scrollHeight;
            document.documentElement.classList.remove('nk-measure');
            const scale = Math.min(1, pageHeight / height);
            if (scale < 1) {
                sheet.style.setProperty('--fit', scale.toFixed(4));
                sheet.setAttribute('data-fit', '');
            }
        };
        window.addEventListener('beforeprint', fit);
        document.addEventListener('click', (event) => {
            if (event.target.closest('[data-print]')) {
                fit();
                window.print();
            }
        });
        @if (request()->boolean('cetak'))
            window.addEventListener('load', () => setTimeout(() => { fit(); window.print(); }, 300));
        @endif
    })();
</script>
