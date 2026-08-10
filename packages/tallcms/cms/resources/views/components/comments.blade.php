@props(['post'])

@php
    $maxDepth = max(1, (int) config('tallcms.comments.max_depth', 2));

    // Build eager-loading chains for nested approved replies + their authors
    $eagerLoads = ['user'];
    $replyChain = 'approvedReplies';
    for ($i = 1; $i < $maxDepth; $i++) {
        $eagerLoads[] = $replyChain . '.user';
        if ($i < $maxDepth - 1) {
            $replyChain .= '.approvedReplies';
        }
    }
    $eagerLoads[] = $replyChain;

    $comments = $post->approvedComments()
        ->topLevel()
        ->with($eagerLoads)
        ->orderBy('created_at', 'asc')
        ->get();

    $commentCount = $post->approvedComments()->count();
    $guestCommentsAllowed = config('tallcms.comments.guest_comments', true);
@endphp

<section class="mt-12 border-t border-base-300 pt-8" id="comments">
    <h2 class="text-2xl font-bold text-base-content mb-6">
        {{ trans_choice('tallcms::frontend.comments_count', $commentCount, ['count' => $commentCount]) }}
    </h2>

    {{-- Comment List --}}
    @if($comments->isNotEmpty())
        <div class="space-y-6 mb-8">
            @foreach($comments as $comment)
                @include('tallcms::components.comment-item', [
                    'comment' => $comment,
                    'depth' => 0,
                    'maxDepth' => $maxDepth,
                    'postId' => $post->id,
                ])
            @endforeach
        </div>
    @else
        <p class="text-base-content/60 mb-8">No comments yet. Be the first to share your thoughts!</p>
    @endif

    {{-- Comment Form --}}
    @include('tallcms::components.comment-form', [
        'postId' => $post->id,
        'parentId' => null,
        'guestCommentsAllowed' => $guestCommentsAllowed,
    ])
</section>
