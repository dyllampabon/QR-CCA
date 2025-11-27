<div class="animate-pulse p-6">
    <div class="h-4 bg-gray-200 rounded w-1/3 mb-4"></div>

    @for($i = 0; $i < 5; $i++)
        <div class="flex justify-between space-x-4 mb-3">
            <div class="h-4 w-1/4 bg-gray-200 rounded"></div>
            <div class="h-4 w-1/6 bg-gray-200 rounded"></div>
            <div class="h-4 w-1/6 bg-gray-200 rounded"></div>
            <div class="h-4 w-1/6 bg-gray-200 rounded"></div>
            <div class="h-4 w-1/6 bg-gray-200 rounded"></div>
        </div>
    @endfor
</div>
