<div >
    <div class="flex flex-col items-center justify-center min-h-[60vh] text-center space-y-6">
        <h1 class="text-3xl font-bold">Thank You!</h1>
        <p class="text-lg text-gray-600">We appreciate your feedback and will get back to you if necessary.</p>


    <div class="space-y-2">

        <x-filament::button
            href="{{ url()->previous() }}"
            color="gray"
            tag="a"
            class="w-full"
        >
            <span >Submit Another Feedback</span>
        </x-filament::button>

        <x-filament::button
            href="/"
            color="primary"
            tag="a"
            class="w-full"
        >
            Go to Homepage
        </x-filament::button>
    </div>
    </div>
</div>
