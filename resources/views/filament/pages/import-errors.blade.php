<div class="space-y-2">
    @forelse ($errors as $error)
        <div class="rounded-lg bg-gray-50 p-3 text-sm dark:bg-white/5">
            {{ $error }}
        </div>
    @empty
        <p class="text-sm text-gray-500">Ошибок нет.</p>
    @endforelse
</div>
