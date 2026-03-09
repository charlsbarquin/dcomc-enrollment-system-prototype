{{-- Empty state: icon + title + optional text and action. Use in tables/lists. --}}
<div class="admin-empty-state flex flex-col items-center justify-center py-12 px-4 text-center">
    <svg class="admin-empty-state-icon w-16 h-16 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.5M4 13h2.5M4 18h2.5"/>
    </svg>
    <p class="admin-empty-state-title text-gray-600 font-semibold">{{ $title ?? 'No records' }}</p>
    @if(!empty($text))
        <p class="admin-empty-state-text text-gray-500 text-sm mt-1 max-w-sm">{{ $text }}</p>
    @endif
    @if(!empty($actionUrl) && !empty($actionLabel))
        <a href="{{ $actionUrl }}" class="btn-primary mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 transition-colors">{{ $actionLabel }}</a>
    @endif
</div>
