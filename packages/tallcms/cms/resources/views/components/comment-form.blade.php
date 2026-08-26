@props(['postId', 'parentId' => null, 'guestCommentsAllowed' => true, 'compact' => false])

@php
    $formId = 'comment-form-' . ($parentId ?? 'main') . '-' . uniqid();
    $submitUrl = route('tallcms.comments.submit');
    $commentConfig = json_encode([
        'submitUrl' => $submitUrl,
        'postId' => $postId,
        'parentId' => $parentId,
    ]);
@endphp

<div
    id="{{ $formId }}"
    x-data="commentForm"
    data-comment-config="{{ $commentConfig }}"
    x-cloak
>
    {{-- Error Alert --}}
    <div x-show="formError" x-cloak class="alert alert-error mb-4" role="alert">
        <x-heroicon-o-exclamation-circle class="w-5 h-5" />
        <span x-text="formError"></span>
    </div>

    {{-- Success Message --}}
    <div x-show="submitted" x-cloak class="alert alert-success">
        <x-heroicon-o-check-circle class="w-5 h-5" />
        <span x-text="successMessage"></span>
    </div>

    {{-- Form --}}
    <form x-show="!submitted" x-on:submit.prevent="submit" class="space-y-4">
        @auth
            <p class="text-sm text-base-content/60">
                {{ __('tallcms::frontend.commenting_as') }} <span class="font-semibold">{{ auth()->user()->name }}</span>
            </p>
        @else
            @if($guestCommentsAllowed)
                <div class="{{ $compact ? 'grid grid-cols-2 gap-3' : 'grid grid-cols-1 sm:grid-cols-2 gap-4' }}">
                    <div>
                        <label for="{{ $formId }}-name" class="label">
                            <span class="label-text">{{ __('tallcms::frontend.name') }} <span class="text-error">*</span></span>
                        </label>
                        <input
                            type="text"
                            id="{{ $formId }}-name"
                            x-model="formData.author_name"
                            class="input input-bordered w-full"
                            :class="{ 'input-error': errors.author_name }"
                            required
                        >
                        <template x-if="errors.author_name">
                            <p class="text-error text-xs mt-1" x-text="errors.author_name[0]"></p>
                        </template>
                    </div>
                    <div>
                        <label for="{{ $formId }}-email" class="label">
                            <span class="label-text">{{ __('tallcms::frontend.email') }} <span class="text-error">*</span></span>
                        </label>
                        <input
                            type="email"
                            id="{{ $formId }}-email"
                            x-model="formData.author_email"
                            class="input input-bordered w-full"
                            :class="{ 'input-error': errors.author_email }"
                            required
                        >
                        <template x-if="errors.author_email">
                            <p class="text-error text-xs mt-1" x-text="errors.author_email[0]"></p>
                        </template>
                    </div>
                </div>
            @else
                @php
                    $loginUrl = null;
                    // Try configured login route (route name or URL), then common route names, then fall back to /login
                    $loginRoute = config('tallcms.auth.login_route');
                    if ($loginRoute && (str_starts_with($loginRoute, '/') || str_starts_with($loginRoute, 'http'))) {
                        $loginUrl = $loginRoute;
                    } elseif ($loginRoute && \Illuminate\Support\Facades\Route::has($loginRoute)) {
                        $loginUrl = route($loginRoute);
                    } elseif (\Illuminate\Support\Facades\Route::has('filament.' . config('tallcms.filament.panel_id', 'admin') . '.auth.login')) {
                        $loginUrl = route('filament.' . config('tallcms.filament.panel_id', 'admin') . '.auth.login');
                    } elseif (\Illuminate\Support\Facades\Route::has('login')) {
                        $loginUrl = route('login');
                    } else {
                        $loginUrl = url('/login');
                    }
                @endphp
                <div class="alert alert-info">
                    <x-heroicon-o-information-circle class="w-5 h-5" />
                    <span>{!! __('tallcms::frontend.must_log_in_to_comment', ['url' => $loginUrl]) !!}</span>
                </div>
            @endif
        @endauth

        @if(auth()->check() || $guestCommentsAllowed)
            <div>
                <label for="{{ $formId }}-content" class="label">
                    <span class="label-text">{{ $parentId ? __('tallcms::frontend.your_reply') : __('tallcms::frontend.your_comment') }} <span class="text-error">*</span></span>
                </label>
                <textarea
                    id="{{ $formId }}-content"
                    x-model="formData.content"
                    class="textarea textarea-bordered w-full"
                    :class="{ 'textarea-error': errors.content }"
                    rows="{{ $compact ? 3 : 4 }}"
                    required
                ></textarea>
                <template x-if="errors.content">
                    <p class="text-error text-xs mt-1" x-text="errors.content[0]"></p>
                </template>
            </div>

            {{-- Honeypot --}}
            <div class="hidden" aria-hidden="true">
                <label for="{{ $formId }}-website">{{ __('tallcms::frontend.website') }}</label>
                <input type="text" id="{{ $formId }}-website" x-model="formData._honeypot" tabindex="-1" autocomplete="off">
            </div>

            <div>
                <button
                    type="submit"
                    class="btn btn-primary {{ $compact ? 'btn-sm' : '' }}"
                    x-bind:disabled="submitting"
                >
                    <span x-show="!submitting">{{ $parentId ? __('tallcms::frontend.post_reply') : __('tallcms::frontend.post_comment') }}</span>
                    <span x-show="submitting" x-cloak class="inline-flex items-center">
                        <span class="loading loading-spinner loading-sm mr-2"></span>{{ __('tallcms::frontend.submitting') }}</span>
                </button>
            </div>
        @endif
    </form>
</div>
