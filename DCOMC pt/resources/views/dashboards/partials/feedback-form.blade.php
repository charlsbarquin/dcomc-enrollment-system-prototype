@php
    $storeRoute = $storeRoute ?? 'admin.feedback.store';
    $priority = old('priority', 3);
    $subjectOld = old('subject', '');
    $messageOld = old('message', '');
@endphp
@if(session('success'))
    <div x-data="{ showSuccess: true }"
         x-show="showSuccess"
         x-cloak
         role="alert"
         class="mb-4 rounded-xl border border-green-200 bg-green-50 p-4 pr-12 text-green-800 shadow-sm font-data text-sm relative">
        {{ session('success') }}
        <button type="button"
                @click="showSuccess = false"
                class="absolute top-3 right-3 text-green-600 hover:text-green-800 focus:outline-none focus:ring-2 focus:ring-green-500/50 rounded p-0.5 min-w-[1.5rem] min-h-[1.5rem] flex items-center justify-center"
                aria-label="Dismiss">×</button>
    </div>
@endif
@if($errors->any())
    <div class="mb-4 rounded-xl border-2 border-[#F97316] bg-[#F97316]/5 p-4 shadow-sm" role="alert">
        <ul class="list-disc list-inside font-data text-sm text-[#F97316] font-medium space-y-1">
            @foreach($errors->all() as $e)
                <li>{{ $e }}</li>
            @endforeach
        </ul>
    </div>
@endif
<form action="{{ route($storeRoute) }}"
      method="POST"
      class="space-y-6 font-data"
      id="feedback-form"
      x-data="{
          priority: {{ $priority }},
          subjectLength: {{ strlen($subjectOld) }},
          messageLength: {{ strlen($messageOld) }},
          submitting: false
      }"
      @submit="submitting = true">
    @csrf

    <a href="#submit-btn" class="sr-only focus:not-sr-only focus:absolute focus:left-4 focus:top-4 focus:z-10 focus:px-3 focus:py-2 focus:bg-[#1E40AF] focus:text-white focus:rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#1E40AF] text-sm font-medium">Skip to Send Feedback</a>

    {{-- Reassurance strip --}}
    <div class="font-data rounded-lg border-l-4 border-[#F97316] bg-[#F97316]/10 px-4 py-3 text-sm text-gray-800 flex items-start gap-3">
        <svg class="w-5 h-5 shrink-0 text-[#F97316] mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        <p class="mb-0">We read every submission. You'll only be contacted if we need more details.</p>
    </div>

    <p class="text-gray-600 text-sm">Share an error report or suggestion. Your message and priority will be visible to system administrators.</p>

    {{-- Type (UI-only / optional for future backend) --}}
    <div>
        <label for="category" class="font-data block text-sm font-bold text-gray-700 mb-1 inline-flex items-center gap-2">
            <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            Type <span class="text-gray-400 font-normal">(optional)</span>
        </label>
        <select id="category" name="category" class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors">
            <option value="">Select type (optional)</option>
            <option value="Bug report" {{ old('category') === 'Bug report' ? 'selected' : '' }}>Bug report</option>
            <option value="Suggestion" {{ old('category') === 'Suggestion' ? 'selected' : '' }}>Suggestion</option>
            <option value="Question" {{ old('category') === 'Question' ? 'selected' : '' }}>Question</option>
            <option value="Other" {{ old('category') === 'Other' ? 'selected' : '' }}>Other</option>
        </select>
    </div>

    <div>
        <div class="flex items-center justify-between gap-2 mb-1">
            <label for="subject" class="font-data block text-sm font-bold text-gray-700 inline-flex items-center gap-2">
                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
                Subject <span class="text-[#F97316]">*</span>
            </label>
            <span class="font-data text-xs tabular-nums"
                  :class="subjectLength >= 140 ? 'text-[#F97316] font-medium' : 'text-gray-500'"
                  x-text="subjectLength + ' / 160'"></span>
        </div>
        <input id="subject"
               name="subject"
               type="text"
               class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors"
               placeholder="Example: Can't submit enrollment form"
               required
               minlength="3"
               maxlength="160"
               value="{{ $subjectOld }}"
               @input="subjectLength = $event.target.value.length">
    </div>

    <div>
        <div class="flex items-center justify-between gap-2 mb-1">
            <label for="message" class="font-data block text-sm font-bold text-gray-700 inline-flex items-center gap-2">
                <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                Description <span class="text-[#F97316]">*</span>
            </label>
            <span class="font-data text-xs tabular-nums"
                  :class="messageLength >= 4500 ? 'text-[#F97316] font-medium' : 'text-gray-500'"
                  x-text="messageLength + ' / 5000'"></span>
        </div>
        <textarea id="message"
                  name="message"
                  rows="6"
                  class="font-data w-full border border-gray-200 rounded-lg px-3 py-2.5 bg-white transition-colors resize-y min-h-[8rem]"
                  placeholder="Write the full description (steps to reproduce, what you expected, what happened, screenshots details, etc.)"
                  required
                  minlength="5"
                  maxlength="5000"
                  @input="messageLength = $event.target.value.length">{{ $messageOld }}</textarea>
    </div>

    <div>
        <label class="font-data block text-sm font-bold text-gray-700 mb-2 inline-flex items-center gap-2">
            <svg class="w-4 h-4 text-[#1E40AF]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
            Importance
        </label>
        <p class="text-xs text-gray-500 mb-2">Drag the slider: left = least important (minor bugs/experience), right = very important (needs immediate fixing).</p>
        {{-- Visual 1–5 scale pills --}}
        <div class="flex items-center justify-between gap-1 mb-2" role="group" aria-label="Priority scale 1 to 5">
            <span class="flex-1 text-center text-xs font-data font-medium py-1.5 rounded-md transition-colors mx-0.5" :class="priority === 1 ? 'bg-[#F97316] text-white' : 'bg-gray-100 text-gray-500'">1</span>
            <span class="flex-1 text-center text-xs font-data font-medium py-1.5 rounded-md transition-colors mx-0.5" :class="priority === 2 ? 'bg-[#F97316] text-white' : 'bg-gray-100 text-gray-500'">2</span>
            <span class="flex-1 text-center text-xs font-data font-medium py-1.5 rounded-md transition-colors mx-0.5" :class="priority === 3 ? 'bg-[#F97316] text-white' : 'bg-gray-100 text-gray-500'">3</span>
            <span class="flex-1 text-center text-xs font-data font-medium py-1.5 rounded-md transition-colors mx-0.5" :class="priority === 4 ? 'bg-[#F97316] text-white' : 'bg-gray-100 text-gray-500'">4</span>
            <span class="flex-1 text-center text-xs font-data font-medium py-1.5 rounded-md transition-colors mx-0.5" :class="priority === 5 ? 'bg-[#F97316] text-white' : 'bg-gray-100 text-gray-500'">5</span>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs text-gray-500 w-24">Least</span>
            <input type="range"
                   name="priority"
                   min="1"
                   max="5"
                   step="1"
                   x-model.number="priority"
                   class="flex-1 h-3 rounded-lg appearance-none bg-gray-200 cursor-pointer focus:outline-none focus:ring-2 focus:ring-[#1E40AF] focus:ring-offset-2"
                   style="accent-color: #F97316;"
                   value="{{ $priority }}"
                   aria-label="Importance from 1 to 5">
            <span class="text-xs text-gray-500 w-24 text-right">Very important</span>
        </div>
        <p class="mt-1 text-sm text-gray-600" x-text="'Priority: ' + priority + ' — ' + (priority <= 2 ? 'Low' : priority === 3 ? 'Medium' : 'High')"></p>
    </div>

    <div class="flex flex-col sm:flex-row gap-3 pt-2">
        <button type="submit"
                id="submit-btn"
                class="font-data w-full px-6 py-3 min-h-[44px] text-sm font-semibold rounded-lg text-white shadow-sm transition-all duration-300 focus:outline-none focus:ring-2 focus:ring-[#1E40AF]/50 focus:ring-offset-2 disabled:opacity-70 disabled:cursor-not-allowed"
                style="background-color: #1E40AF;"
                :disabled="submitting"
                :aria-busy="submitting"
                x-text="submitting ? 'Sending…' : 'Send Feedback'"></button>
        @if(isset($backRoute))
            <a href="{{ route($backRoute) }}" class="font-data inline-flex items-center justify-center w-full sm:w-auto px-6 py-3 min-h-[44px] text-sm font-medium rounded-lg text-[#F97316] hover:bg-[#F97316]/10 transition-all duration-300 no-underline focus:outline-none focus:ring-2 focus:ring-[#F97316]/50 focus:ring-offset-2">Cancel</a>
        @endif
    </div>
</form>
