@php
    $isUnread = $message->status != Status::READ;
    $boldClass = $isUnread ? ' text--bold' : '';
@endphp

<div class="last-message" data-conversation-id="{{ $message->conversation_id }}">
    @if ($message->media_id)
        @if ($message->message_type === Status::VIDEO_TYPE_MESSAGE)
            <p class="text text-muted{{ $boldClass }}">
                <i class="las la-video"></i> {{ __('Video') }}
            </p>
        @elseif ($message->message_type === Status::DOCUMENT_TYPE_MESSAGE)
            <p class="text text-muted{{ $boldClass }}">
                <i class="las la-file"></i> {{ __('Document') }}
            </p>
        @else
            <p class="text text-muted{{ $boldClass }}">
                <i class="las la-image"></i> {{ __('Photo') }}
            </p>
        @endif
    @else
        @php
            $shortMessage = strLimit($message->message, 15);
        @endphp
        <p class="text{{ $boldClass }}">{{ e($shortMessage) }}</p>
    @endif
</div>
