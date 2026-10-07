{{-- $items: [['label' => ..., 'url' => ...], ...]. The last item is the current page. --}}
@props(['items' => []])

<nav class="breadcrumb" aria-label="breadcrumb">
    <ol>
        @foreach ($items as $item)
            <li>
                @if (! $loop->last && ! empty($item['url']))
                    <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
                @else
                    <span aria-current="page">{{ $item['label'] }}</span>
                @endif

                @unless ($loop->last)
                    <x-icon name="chevron-right" :size="14" class="icon-flip-rtl" />
                @endunless
            </li>
        @endforeach
    </ol>
</nav>
