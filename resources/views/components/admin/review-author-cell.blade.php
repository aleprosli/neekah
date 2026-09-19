@props(['review'])

<div class="flex min-w-0 flex-col">
    <span class="truncate font-medium">{{ $review->authorName() }}</span>
    <span class="text-xs text-ink-muted">
        @if ($review->addedBy)
            Dimasukkan oleh {{ $review->addedBy->name }}
        @elseif ($review->user)
            Akaun Neekah
        @else
            Tanpa akaun
        @endif
        @if ($review->photos->isNotEmpty())
            · {{ $review->photos->count() }} gambar
        @endif
    </span>
</div>
