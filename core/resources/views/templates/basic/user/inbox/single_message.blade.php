 @php
     $baseText = $message->message ?? '';
     $escapedText = e($baseText);

     $messageText = preg_replace_callback(
         '/([a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,})|((https?:\/\/|www\.)[^\s@]+|[a-z0-9\-]+\.[a-z]{2,}(\/[^\s@]*)?)/i',
         function ($matches) {
             if (!empty($matches[1])) {
                 $email = $matches[1];
                 return '<a href="mailto:' . $email . '">' . $email . '</a>';
             }
             $url = $matches[0];
             $href = preg_match('/^https?:\/\//i', $url) ? $url : 'https://' . $url;
             return '<a href="' . $href . '" target="_blank" rel="noopener noreferrer">' . $url . '</a>';
         },
         $escapedText,
     );

 @endphp

 <div class="single-message {{ @$message->type == Status::MESSAGE_SENT ? 'message--right' : 'message--left' }}" data-message-id="{{ $message->id }}">
     <div class="message-content">
         @if ($message->template_id)
             <p class="message-text">@lang('Template Message')</p>

         @else
             @if ($message->media_caption)
                 <p class="message-text">{!! nl2br($message->media_caption) !!}</p>
             @else
                 <p class="message-text">{!! nl2br($messageText) !!}</p>
             @endif
             @if (@$message->media_id)
                 @if (@$message->message_type == Status::IMAGE_TYPE_MESSAGE)
                     <a href="{{ route('user.inbox.media.download', $message->media_id) }}">
                         <img class="message-image" src="{{ getImage(getFilePath('conversation') . '/' . @$message->media_path) }}" alt="image">
                     </a>
                 @endif
                 @if (@$message->message_type == Status::VIDEO_TYPE_MESSAGE)
                     <div class="text-dark d-flex align-items-center justify-content-between">
                         <a href="{{ route('user.inbox.media.download', $message->media_id) }}"
                             class="text--primary download-document">
                             <img class="message-image" src="{{ asset('assets/images/video_preview.png') }}" alt="image">
                         </a>
                     </div>
                 @endif
                 @if (@$message->message_type == Status::DOCUMENT_TYPE_MESSAGE)
                     <div class="text-dark d-flex justify-content-between flex-column">
                         <a href="{{ route('user.inbox.media.download', $message->media_id) }}"
                             class="text--primary download-document">
                             <img class="message-image" src="{{ asset('assets/images/document_preview.png') }}" alt="image">
                        </a>
                        {{ @$message->media_filename ?? 'Document' }}
                     </div>
                 @endif
             @endif
         @endif
     </div>
     <div class="d-flex align-items-center justify-content-between">
         <span class="message-time">{{ showDateTime(@$message->created_at, 'h:i A') }}</span>
         @if (@$message->type == Status::MESSAGE_SENT)
             <span class="message-status">
                @php echo $message->statusBadge @endphp
             </span>
         @endif
     </div>
 </div>
