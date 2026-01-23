<div class="flex flex-col gap-4 justify-center items-center">
    {!! $qr !!}


    <x-filament::button
    href="{{ $qrLink }}"
    tag="a"
    target="_blank"
    color="gray"
    class="w-full"
>
    Open Survey Form
</x-filament::button>
</div>
