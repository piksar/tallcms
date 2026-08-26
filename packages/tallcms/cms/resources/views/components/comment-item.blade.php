@props(['comment', 'depth' => 0, 'maxDepth' => 2, 'postId'])

@php
    $canReply = $depth < ($maxDepth - 1);
    $replies = $comment->approvedReplies ?? collect();
    $replyFormId = 'reply-form-' . $comment->id;
@endphp

<div class="card bg-base-200/50" id="comment-{{ $comment->id }}"
     @if($depth > 0) style="margin-left: {{ min($depth * 1.5, 4.5) }}rem;" @endif>
    <div class="card-body py-4 px-5">
        <div class="flex items-start justify-between">
            <div class="flex items-center gap-2 text-sm">
                <span class="font-semibold text-base-content">
                    {{ $comment->getAuthorName() ?? __('tallcms::frontend.anonymous') }}
                </span>
                <span class="text-base-content/50">
                    {{ $comment->created_at->diffForHumans() }}
                </span>
            </div>
        </div>

        <div class="text-base-content mt-1 whitespace-pre-line">{{ $comment->content }}</div>

        @if($canReply)
            <div class="mt-2" x-data="{ showReplyForm: false }">
                <button
                    type="button"
                    class="btn btn-ghost btn-xs text-base-content/60"
                    x-on:click="showReplyForm = !showReplyForm"
                >
                    <x-heroicon-o-chat-bubble-left class="w-4 h-4" />
                    {{ __('tallcms::frontend.reply') }}
                </button>

                <div x-show="showReplyForm" x-cloak class="mt-3">
                    @include('tallcms::components.comment-form', [
                        'postId' => $postId,
                        'parentId' => $comment->id,
                        'guestCommentsAllowed' => config('tallcms.comments.guest_comments', true),
                        'compact' => true,
                    ])
                </div>
            </div>
        @endif
    </div>
</div>

{{-- Render replies recursively --}}
@if($replies->isNotEmpty())
    <div class="space-y-3 mt-3">
        @foreach($replies as $reply)
            @include('tallcms::components.comment-item', [
                'comment' => $reply,
                'depth' => $depth + 1,
                'maxDepth' => $maxDepth,
                'postId' => $postId,
            ])
        @endforeach
    </div>
@endif
