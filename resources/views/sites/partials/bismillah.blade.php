@props(['template', 'class' => ''])
@if ($template->showsBismillah())
    <p class="nk-arabic text-2xl leading-loose {{ $class }}" lang="ar" dir="rtl">بِسْمِ ٱللَّٰهِ ٱلرَّحْمَٰنِ ٱلرَّحِيمِ</p>
@endif
