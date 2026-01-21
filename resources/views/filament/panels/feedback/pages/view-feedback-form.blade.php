<x-filament::page>
    <div class="flex flex-col items-center">
        <div class="text-center bg-red-50 border border-red-400 text-red-700 px-3 py-2 rounded relative mb-4 w-96" role="alert">
            <strong class="font-bold">Warning!</strong>
            <span class="block sm:inline">This is a read-only view of the feedback form.</span>
        </div>
         <iframe
                    src="{{ route('feedback.form.print', $record->id) }}"
                    width="1080px"
                    height="800px"
                    frameborder="0"
                    class="border border-gray-300 rounded"
                    title="Feedback Form">
        </iframe>
    </div>
</x-filament::page>
