@props([
    'title',
    'description',
])

<div class="flex w-full flex-col text-center">
    <h1 class="font-heading text-xl font-bold text-bark">{{ $title }}</h1>
    <p class="mt-1 text-sm text-bark-light">{{ $description }}</p>
</div>
