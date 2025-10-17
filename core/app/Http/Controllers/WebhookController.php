<?php

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;
use Illuminate\Http\Request;
use App\Models\Message;
use App\Models\Contact;
use App\Models\Conversation;
use App\Constants\Status;
use App\Events\ReceiveMessage;
use App\Lib\WhatsApp\WhatsAppLib;
use App\Models\User;
use libphonenumber\PhoneNumberUtil;
use App\Traits\WhatsappManager;
use Carbon\Carbon;
use Exception;

class WebhookController extends Controller
{
    use WhatsappManager;

    public function webhookConnect(Request $request)
    {
        $systemWebhookToken = gs('webhook_verify_token');

        if ($systemWebhookToken && $systemWebhookToken != $request->hub_verify_token) {
            return response('Invalid token', 401);
        }

        return response($request->hub_challenge)->header('Content-type', 'plain/text'); // meta need a specific type of response
    }

    public function webhookResponse(Request $request)
    {
        $entry = $request->input('entry', []);
        if (!is_array($entry)) return;
        
        $receiverPhoneNumber = null;
        $senderPhoneNumber   = null;
        $senderId            = null;
        $messageStatus       = null;
        $messageId           = null;
        $messageText         = null;
        $mediaId             = null;
        $mediaType           = null;
        $mediaMimeType       = null;
        $messageType         = 'text';
        $messageCaption      = null;
        $profileName         = null;

        $whatsappAccount = WhatsappAccount::where('whatsapp_business_account_id', $entry[0]['id'])->first();

        if (!$whatsappAccount) return;

        $user = User::active()->find($whatsappAccount->user_id);

        if (!$user) return;

        foreach ($entry as $entryItem) {

            foreach ($entryItem['changes'] as $change) {

                if (!is_array($change) || !isset($change['value'])) continue;

                if (isset($change['field']) && $change['field'] == 'message_template_status_update') {
                    sleep(10); // wait for 10 seconds until the template store
                    $this->templateUpdateNotify($change['value']['message_template_id'], $change['value']['event'], $change['value']['reason'] ?? '');
                    continue;
                };

                $metaValue = $change['value'];
                if (!is_array($metaValue)) continue;

                $profileName = $metaValue['contacts'][0]['profile']['name'] ?? null;
                $metaData    = $metaValue['metadata'] ?? [];
                $metaMessage = $metaValue['messages'] ?? null;

                if (isset($metaData['phone_number_id'])) {
                    $receiverPhoneNumberId = $metaData['phone_number_id'];
                }

                if (isset($metaData['display_phone_number'])) {
                    $receiverPhoneNumber = $metaData['display_phone_number'];
                }

                if (isset($metaMessage[0]['from'])) {
                    $senderPhoneNumber = $metaMessage[0]['from'];
                }

                if (isset($metaMessage[0]['id'])) {
                    $senderId = $metaMessage[0]['id'];
                }

                if (isset($change['value']['statuses'][0]['id'])) {
                    $messageId = $change['value']['statuses'][0]['id'];
                }

                if (isset($change['value']['statuses'][0]['status'])) {
                    $messageStatus = $change['value']['statuses'][0]['status'];
                }

                if (isset($metaMessage[0]['text']['body'])) {
                    $messageText = $metaMessage[0]['text']['body'];
                }
                if (isset($metaMessage[0]['type'])) {
                    $messageType = $metaMessage[0]['type'];
                }

                // Handle media messages
                if (isset($metaMessage[0]['type']) && $metaMessage[0]['type'] !== 'text') {
                    $mediaType = $metaMessage[0]['type'];

                    if (isset($metaMessage[0][$mediaType]['id'])) {
                        $mediaId = $metaMessage[0][$mediaType]['id'];
                    }
                    if (isset($metaMessage[0][$mediaType]['mime_type'])) {
                        $mediaMimeType = $metaMessage[0][$mediaType]['mime_type'];
                    }
                    if (isset($metaMessage[0][$mediaType]['caption'])) {
                        $messageCaption = $metaMessage[0][$mediaType]['caption'];
                    }
                }
            }
        }
        if ($messageId && $messageStatus) {

            $wMessage = Message::where('whatsapp_message_id', $messageId)->first();

            if ($wMessage) {
                $wMessage->status = messageStatus($messageStatus);
                $wMessage->save();

                $isNewMessage = false;
                if ($wMessage->status == Status::SENT || $wMessage->status == Status::FAILED) {
                    $isNewMessage = true;
                }

                $message = $wMessage;
                $html = view('Template::user.inbox.single_message', compact('message'))->render();

                event(new ReceiveMessage($whatsappAccount->id, [
                    'html'           => $html,
                    'messageId'      => $message->id,
                    'message'        => $message,
                    'statusHtml'     => $message->statusBadge,
                    'newMessage'     => $isNewMessage,
                    'mediaPath'      => getFilePath('conversation'),
                    'conversationId' => $wMessage->conversation_id,
                    'unseenMessage'  => $wMessage->conversation->unseenMessages()->count() < 10 ? $wMessage->conversation->unseenMessages()->count() : '9+',
                ]));

                return response()->json(['status' => 'received'], 200);
            }
        }

        if (($messageText || $mediaId) && $senderPhoneNumber && $senderId) {
            // Save the incoming message first
            $receiverPhoneNumber = preg_replace('/\D/', '', $receiverPhoneNumber);
            $phoneUtil           = PhoneNumberUtil::getInstance();
            $parseNumber         = $phoneUtil->parse('+' . $senderPhoneNumber, '');
            $countryCode         = $parseNumber->getCountryCode();
            $nationalNumber      = $parseNumber->getNationalNumber();
            $newContact          = false;

            $contact = Contact::where('mobile_code', $countryCode)
                ->where('mobile', $nationalNumber)
                ->where('user_id', $user->id)
                ->with('conversation')
                ->first();

            if (!$contact) {
                $newContact           = true;
                $contact              = new Contact();
                $contact->firstname   = $profileName;
                $contact->mobile_code = $countryCode;
                $contact->mobile      = $nationalNumber;
                $contact->user_id     = $user->id;
                $contact->save();
            }

            $conversation = Conversation::where('contact_id', $contact->id)->where('user_id', $user->id)->where('whatsapp_account_id', $whatsappAccount->id)->first();
            if (!$conversation) {
                $newContact   = true;
                $conversation = $this->createConversation($contact, $whatsappAccount);
            }

            $messageExists = Message::where('whatsapp_message_id', $senderId)->exists();

            $whatsappLib = new WhatsAppLib();
            
            if (!$messageExists) {
                // Save the incoming message
                $message                      = new Message();
                $message->whatsapp_account_id = $whatsappAccount->id;
                $message->whatsapp_message_id = $senderId;
                $message->user_id             = $user->id ?? 0;
                $message->conversation_id     = $conversation->id;
                $message->message             = $messageText;
                $message->type                = Status::MESSAGE_RECEIVED;
                $message->message_type        = getIntMessageType($messageType);
                $message->media_id            = $mediaId;
                $message->media_type          = $mediaType;
                $message->media_caption       = $messageCaption;
                $message->mime_type           = $mediaMimeType;
                $message->ordering            = Carbon::now()->format('Y-m-d H:i:s.u');
                $message->save();

                $conversation->last_message_at = Carbon::now();
                $conversation->save();
                
                // If it's a media message, fetch and store the media
                if ($mediaId) {
                    $accessToken = $whatsappAccount->access_token;
                    try {
                        $mediaUrl = $whatsappLib->getMediaUrl($mediaId, $accessToken);

                        if ($mediaUrl && $mediaType == 'image') {
                            $mediaPath           = $whatsappLib->storedMediaToLocal($mediaUrl['url'], $mediaId, $accessToken, $user->id);
                            $message->media_url  = $mediaUrl;
                            $message->media_path = $mediaPath;

                            $message->save();
                        }
                    } catch (Exception $ex) {
                    }
                }

                $html                        = view('Template::user.inbox.single_message', compact('message'))->render();
                $lastConversationMessageHtml = view("Template::user.inbox.conversation_last_message", compact('message'))->render();

                event(new ReceiveMessage($whatsappAccount->id, [
                    'html'            => $html,
                    'message'         => $message,
                    'newMessage'      => true,
                    'newContact'      => $newContact,
                    'lastMessageHtml' => $lastConversationMessageHtml,
                    'unseenMessage'   => $conversation->unseenMessages()->count() < 10 ? $conversation->unseenMessages()->count() : '9+',
                    'lastMessageAt'   => showDateTime(Carbon::now()),
                    'conversationId'  => $conversation->id,
                    'mediaPath'       => getFilePath('conversation')
                ]));

            }
            
            $messagesInConversation = Message::where('conversation_id', $conversation->id)->where('type', Status::MESSAGE_RECEIVED)->count();
            if ($messagesInConversation == 1 && @$whatsappAccount->welcomeMessage) {
                $this->sendWelcomeMessage($whatsappAccount, $user, $contact, $conversation);
            }else{

                $matchedChatbot = $whatsappAccount->chatbots()
                    ->where('status', Status::ENABLE)
                    ->where('keywords', 'like', "%{$messageText}%")
                    ->first();
                    
                    if($matchedChatbot){
                        $this->chatbotResponse($whatsappAccount, $user, $contact, $conversation, $matchedChatbot);
                    }else
                    {
                        $whatsappLib->sendAutoReply($user, $conversation, $messageText);
                    }
            }
        }

        return response()->json(['status' => 'received'], 200);
    }

    private function createConversation($contact, $whatsappAccount)
    {

        $conversation                      = new Conversation();
        $conversation->contact_id          = $contact->id;
        $conversation->whatsapp_account_id = $whatsappAccount->id;
        $conversation->user_id             = $whatsappAccount->user_id;
        $conversation->save();

        return $conversation;
    }
 /**
     * Handle Baileys webhook for incoming messages and send confirmations
     */
    public function baileysWebhook(Request $request)
    {
        try {
            $type = $request->input('type');
            $sessionId = $request->input('sessionId');
            $messageId = $request->input('messageId');
            $jobId = $request->input('jobId');
            $status = $request->input('status');

            // Handle async send confirmation (from background job)
            if ($jobId && $status) {
                \Log::info("Baileys webhook: Job confirmation", [
                    'jobId' => $jobId,
                    'status' => $status,
                    'messageId' => $messageId
                ]);

                // Find message by job_id
                $message = Message::where('job_id', $jobId)->first();

                if ($message) {
                    // Update message status based on result
                    $message->status = $status === 'sent' ? Status::SENT : Status::FAILED;
                    if ($messageId) {
                        $message->whatsapp_message_id = $messageId;
                    }
                    $message->save();

                    // Broadcast update via Pusher
                    $html = view('Template::user.inbox.single_message', compact('message'))->render();
                    
                    event(new ReceiveMessage($message->whatsapp_account_id, [
                        'html'           => $html,
                        'messageId'      => $message->id,
                        'message'        => $message,
                        'statusHtml'     => $message->statusBadge,
                        'newMessage'     => true,
                        'mediaPath'      => getFilePath('conversation'),
                        'conversationId' => $message->conversation_id,
                        'unseenMessage'  => $message->conversation->unseenMessages()->count() < 10 ? $message->conversation->unseenMessages()->count() : '9+',
                    ]));

                    return response()->json(['success' => true]);
                }

                return response()->json(['error' => 'Message not found'], 404);
            }

            // Find WhatsApp account by session ID
            $whatsappAccount = WhatsappAccount::where('baileys_session_id', $sessionId)->first();

            if (!$whatsappAccount) {
                return response()->json(['error' => 'Account not found'], 404);
            }

            $user = User::active()->find($whatsappAccount->user_id);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            // Handle status updates
            if ($type === 'status_update') {
                $status = $request->input('status');
                
                $message = Message::where('whatsapp_message_id', $messageId)->first();
                
                if ($message) {
                    $message->status = $status;
                    $message->save();

                    // Broadcast status update via Pusher
                    $html = view('Template::user.inbox.single_message', compact('message'))->render();
                    
                    event(new ReceiveMessage($whatsappAccount->id, [
                        'html'           => $html,
                        'messageId'      => $message->id,
                        'message'        => $message,
                        'statusHtml'     => $message->statusBadge,
                        'newMessage'     => false,
                        'mediaPath'      => getFilePath('conversation'),
                        'conversationId' => $message->conversation_id,
                        'unseenMessage'  => $message->conversation->unseenMessages()->count() < 10 ? $message->conversation->unseenMessages()->count() : '9+',
                    ]));
                }

                return response()->json(['success' => true]);
            }

            // Handle incoming messages
            $from = $request->input('from');
            $messageText = $request->input('message');  
            $messageType = $request->input('messageType', 'text');
            $pushName = $request->input('pushName');
            $caption = $request->input('caption');
            $fileName = $request->input('fileName');
            $mimetype = $request->input('mimetype');
            $profilePicUrl = $request->input('profilePicUrl');

            // Parse phone number using libphonenumber (same as Meta webhook)
            // Remove @s.whatsapp.net if present
            $phoneNumber = str_replace('@s.whatsapp.net', '', $from);
            
            $phoneUtil = PhoneNumberUtil::getInstance();
            
            try {
                // Try parsing with + prefix
                $parseNumber = $phoneUtil->parse('+' . $phoneNumber, '');
            } catch (\Exception $e) {
                // If fails, try without + prefix (number might already have it)
                try {
                    $parseNumber = $phoneUtil->parse($phoneNumber, '');
                } catch (\Exception $e2) {
                    \Log::error('Baileys webhook: Failed to parse phone number', [
                        'from' => $from,
                        'phoneNumber' => $phoneNumber,
                        'error' => $e2->getMessage()
                    ]);
                    return response()->json(['error' => 'Invalid phone number format'], 400);
                }
            }
            
            $countryCode = $parseNumber->getCountryCode();
            $nationalNumber = $parseNumber->getNationalNumber();
            $newContact = false;

            // Find or create contact with correct schema
            $contact = Contact::where('mobile_code', $countryCode)
                ->where('mobile', $nationalNumber)
                ->where('user_id', $user->id)
                ->with('conversation')
                ->first();

            if (!$contact) {
                $newContact = true;
                $contact = new Contact();
                $contact->firstname = $pushName ?? $from;
                $contact->mobile_code = $countryCode;
                $contact->mobile = $nationalNumber;
                $contact->user_id = $user->id;
                $contact->save();
            }

            // Download and save profile picture if available and not already set
            if ($profilePicUrl && (!$contact->image || $contact->image == 'default.png')) {
                try {
                    $imageContent = file_get_contents($profilePicUrl);
                    if ($imageContent) {
                        $filename = uniqid() . '_' . $contact->id . '.jpg';
                        $path = getFilePath('contactProfile');
                        $fullPath = $path . '/' . $filename;
                        
                        // Create directory if it doesn't exist
                        if (!file_exists($path)) {
                            mkdir($path, 0755, true);
                        }
                        
                        file_put_contents($fullPath, $imageContent);
                        $contact->image = $filename;
                        $contact->save();
                    }
                } catch (\Exception $e) {
                    \Log::error('Error downloading profile picture: ' . $e->getMessage());
                }
            }

            // Find or create conversation
            $conversation = Conversation::where('contact_id', $contact->id)
                ->where('user_id', $user->id)
                ->where('whatsapp_account_id', $whatsappAccount->id)
                ->first();

            if (!$conversation) {
                $newContact = true;
                $conversation = $this->createConversation($contact, $whatsappAccount);
            }

            // Check if message already exists
            $messageExists = Message::where('whatsapp_message_id', $messageId)->exists();

            if (!$messageExists) {
                // Store message
                $message = new Message();
                $message->user_id = $user->id;
                $message->whatsapp_account_id = $whatsappAccount->id;
                $message->whatsapp_message_id = $messageId;
                $message->conversation_id = $conversation->id;
                $message->type = Status::MESSAGE_RECEIVED;
                $message->message = $messageType === 'text' ? $messageText : ($caption ?? '');
                $message->message_type = getIntMessageType($messageType);
                $message->media_id = $messageType !== 'text' ? $messageId : null;
                $message->media_caption = $caption;
                $message->media_filename = $messageType === 'document' ? basename($fileName) : null; // Only basename for documents
                $message->media_path = $fileName; // Full path with user_id/year/month/day/filename
                $message->mime_type = $mimetype;
                $message->media_type = $messageType !== 'text' ? $messageType : null;
                $message->ordering = Carbon::now()->format('Y-m-d H:i:s.u');
                $message->save();

                // Update conversation
                $conversation->last_message_at = Carbon::now();
                $conversation->save();

                // Render views for broadcast
                $html = view('Template::user.inbox.single_message', compact('message'))->render();
                $lastConversationMessageHtml = view("Template::user.inbox.conversation_last_message", compact('message'))->render();

                // Broadcast event
                event(new ReceiveMessage($whatsappAccount->id, [
                    'html' => $html,
                    'message' => $message,
                    'newMessage' => true,
                    'newContact' => $newContact,
                    'lastMessageHtml' => $lastConversationMessageHtml,
                    'unseenMessage' => $conversation->unseenMessages()->count() < 10 ? $conversation->unseenMessages()->count() : '9+',
                    'lastMessageAt' => showDateTime(Carbon::now()),
                    'conversationId' => $conversation->id,
                    'mediaPath' => getFilePath('conversation')
                ]));
            }

            // Handle welcome message and chatbot for first message, or just chatbot for subsequent messages
            $messagesInConversation = Message::where('conversation_id', $conversation->id)
                ->where('type', Status::MESSAGE_RECEIVED)
                ->count();

            if ($messagesInConversation == 1) {
                $this->sendWelcomeMessage($whatsappAccount, $user, $contact, $conversation);
            } else {
                $matchedChatbot = $whatsappAccount->chatbots()
                    ->where('status', Status::ENABLE)
                    ->where('keywords', 'like', "%{$messageText}%")
                    ->first();
                    
                if ($matchedChatbot) {
                    $this->chatbotResponse($whatsappAccount, $user, $contact, $conversation, $matchedChatbot);
                } else {
                    $whatsappLib = new WhatsAppLib();
                    $whatsappLib->sendAutoReply($user, $conversation, $messageText);
                }
            }

            return response()->json(['success' => true]);

        } catch (Exception $e) {
            \Log::error('Baileys webhook error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}