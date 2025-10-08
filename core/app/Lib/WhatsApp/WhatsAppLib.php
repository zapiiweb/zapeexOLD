<?php

namespace App\Lib\WhatsApp;

use App\Constants\Status;
use App\Lib\CurlRequest;
use App\Services\BaileysService;
use Exception;
use Illuminate\Support\Facades\Http;

class WhatsAppLib
{

    public function messageSend($request, $toNumber, $whatsappAccount)
    {
        // Check if Baileys is connected and use it instead of Meta API
        if ($whatsappAccount->baileys_connected && $whatsappAccount->baileys_session_id) {
            return $this->messageSendViaBaileys($request, $toNumber, $whatsappAccount);
        }

        $phoneNumberId    = $whatsappAccount->phone_number_id;
        $accessToken      = $whatsappAccount->access_token;

        $url       = $this->getWhatsAppBaseUrl() . "{$phoneNumberId}/messages";
        $mediaLink = $this->getWhatsAppBaseUrl() . "{$phoneNumberId}/media";

        $mediaId       = null;
        $mediaUrl      = null;
        $mediaPath     = null;
        $mediaCaption  = null;
        $mediaFileName = null;
        $mimeType      = null;
        $mediaType     = null;

        $data = [
            'messaging_product' => 'whatsapp',
            'to'                => $toNumber,
        ];

        if ($request->hasFile('image')) {
            $file          = $request->file('image');
            $mediaUpload   = $this->uploadMedia($mediaLink, $file, $accessToken);

            $mediaId       = $mediaUpload['id'];
            $mediaCaption  = $request->message;
            $data['type']  = 'image';
            $data['image'] = [
                'id'      => $mediaId,
                'caption' => $mediaCaption
            ];
            $mediaType     = 'image';
            $mimeType      = mime_content_type($file->getPathname());
        } else if ($request->hasFile('document')) {
            $file             = $request->file('document');
            $mediaUpload      = $this->uploadMedia($mediaLink, $file, $accessToken);
            $mediaId          = $mediaUpload['id'];
            $mediaCaption     = $request->message;
            $mediaFileName    = $request->file('document')->getClientOriginalName();
            $data['type']     = 'document';
            $data['document'] = [
                'id'       => $mediaId,
                'caption'  => $mediaCaption,
                'filename' => $mediaFileName
            ];
            $mediaType        = 'document';
            $mimeType         = mime_content_type($file->getPathname());
        } else if ($request->hasFile('video')) {
            $file          = $request->file('video');
            $mediaUpload   = $this->uploadMedia($mediaLink, $file, $accessToken);
            $mediaId       = $mediaUpload['id'];
            $mediaCaption  = $request->message;
            $data['type']  = 'video';
            $data['video'] = [
                'id'      => $mediaId,
                'caption' => $mediaCaption
            ];
            $mediaType     = 'video';
            $mimeType      = mime_content_type($file->getPathname());
        } else {
            $data['type'] = 'text';
            $data['text'] = [
                'body' => $request->message
            ];
        }

        try {

            if ($mediaId) {
                $mediaUrl = $this->getMediaUrl($mediaId, $accessToken)['url'];
            }

            if ($mediaId && $mediaUrl && $request->hasFile('image')) {
                $mediaPath = $this->storedMediaToLocal($mediaUrl, $mediaId, $accessToken, $whatsappAccount->user_id);
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}"
            ])->post($url, $data);

            $responseData = $response->json();


            if (!is_array($responseData) || !count($responseData)) {
                throw new Exception("Something went wrong");
            }

            if (isset($responseData['error']) || !isset($responseData['messages'])) {
                throw new Exception(@$responseData['error']['message'] ?? "Something went wrong");
            }

            if ($response->failed()) {
                throw new Exception("Message sending failed");
            }

            return [
                'whatsAppMessage' => $responseData['messages'],
                'mediaId'         => $mediaId,
                'mediaUrl'        => $mediaUrl,
                'mediaPath'       => $mediaPath,
                'mediaCaption'    => $mediaCaption,
                'mediaFileName'   => $mediaFileName,
                'messageType'     => $data['type'],
                'mimeType'        => $mimeType ?? null,
                'mediaType'       => $mediaType ?? null
            ];
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function messageResend(object $message, $toNumber, $whatsappAccount)
    {
        $phoneNumberId    = $whatsappAccount->phone_number_id;
        $accessToken      = $whatsappAccount->access_token;

        $url       = $this->getWhatsAppBaseUrl() . "{$phoneNumberId}/messages";

        $mediaId = $message->media_id ?? null;
        $mediaCaption = $message->media_caption ?? null;
        $mediaFileName = $message->media_filename ?? null;

        $data = [
            'messaging_product' => 'whatsapp',
            'to'                => $toNumber,
            'type'              => 'text'
        ];

        if ($message->media_id && $message->message_type == Status::IMAGE_TYPE_MESSAGE) {
            $data['type']  = 'image';
            $data['image'] = [
                'id'      => $mediaId,
                'caption' => $mediaCaption
            ];
        } else if ($message->media_id && $message->message_type == Status::DOCUMENT_TYPE_MESSAGE) {
            $data['type']     = 'document';
            $data['document'] = [
                'id'       => $mediaId,
                'caption'  => $mediaCaption,
                'filename' => $mediaFileName
            ];
        } else if ($message->media_id && $message->message_type == Status::VIDEO_TYPE_MESSAGE) {
            $data['type']  = 'video';
            $data['video'] = [
                'id'      => $mediaId,
                'caption' => $mediaCaption
            ];
        } else {
            $data['type'] = 'text';
            $data['text'] = [
                'body' => $message->message
            ];
        }

        try {

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}"
            ])->post($url, $data);

            $responseData = $response->json();

            if (!is_array($responseData) || !count($responseData)) {
                throw new Exception("Something went wrong");
            }

            if (isset($responseData['error']) || !isset($responseData['messages'])) {
                throw new Exception(@$responseData['error']['message'] ?? "Something went wrong");
            }

            if ($response->failed()) {
                throw new Exception("Message sending failed");
            }

            return [
                'whatsAppMessage' => $responseData['messages']
            ];
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function uploadMedia($mediaUrl, $file, $accessToken)
    {
        $filePath = $file->getRealPath();
        $fileName = $file->getClientOriginalName();

        $postData = [
            'messaging_product' => 'whatsapp',
        ];

        $headers = [
            "Authorization: Bearer {$accessToken}",
        ];

        $response = CurlRequest::curlFileUpload(
            $mediaUrl,
            $postData,
            'file',
            $filePath,
            $fileName,
            $headers
        );

        $data = json_decode($response, true);

        if (!is_array($data) || isset($data['error']) || !isset($data['id'])) {
            $errorMessage = "Failed to upload media";
            if (isset($data['error']['error_user_msg'])) {
                $errorMessage = $data['error']['error_user_msg'];
            }
            if ($data['error']['message']) {
                $errorMessage = $data['error']['message'];
            }
            throw new Exception($errorMessage);
        }

        return $data;
    }

    function getSessionId($appId, array $fileData, $accessToken)
    {
        try {

            $url      = "https://graph.facebook.com/v23.0/{$appId}/uploads";
            $response = Http::post($url, [
                'file_name'    => $fileData['name'],
                'file_type'    => $fileData['type'],
                'file_length'  => $fileData['size'],
                'access_token' => $accessToken
            ]);

            $data = $response->json();

            if ($response->failed() || !is_array($data) || !isset($data['id'])) {
                throw new Exception(@$data['error']['message'] ?? "Couldn\'t upload your header image");
            }
            return $data;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage() ?? "Couldn\'t upload your header image");
        }
    }

    function getMediaHandle($sessionId, $accessToken, $filePath, $mimeType)
    {
        try {

            $cleanSessionId = str_replace('upload:', '', $sessionId);
            $url            = "https://graph.facebook.com/v23.0/upload:$cleanSessionId";
            $fileContents   = file_get_contents($filePath);

            $response = Http::withHeaders([
                'Authorization' => "OAuth $accessToken",
                'file_offset'   => '0',
            ])->withBody($fileContents, $mimeType)
                ->post($url);

            $data = $response->json();

            if ($response->failed() || !is_array($data) || !isset($data['h'])) {
                throw new Exception(@$data['error']['message'] ?? "Something went wrong");
            }
            return $data['h'];
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage() ?? "Something went wrong");
        }
    }

    public function getMediaUrl($mediaId, $accessToken)
    {
        $url = $this->getWhatsAppBaseUrl() . "{$mediaId}";

        $response = CurlRequest::curlContent($url, [
            "Authorization: Bearer {$accessToken}"
        ]);

        $data = json_decode($response, true);

        if (!is_array($data) || isset($data['error']) || !isset($data['url'])) {
            throw new Exception(@$data['error']['message'] ?? "Something went wrong");
        }

        return $data;
    }

    private function getWhatsAppBaseUrl()
    {
        return "https://graph.facebook.com/v22.0/";
    }

    public function storedMediaToLocal($mediaUrl, $mediaId, $accessToken, $userId)
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
            ])->get($mediaUrl);

            if ($response->failed()) {
                throw new Exception("Message sending fail for the download media");
            }

            $fileContent = $response->body();
            $mimeType    = $response->header('Content-Type');

            $fileExtension = explode('/', $mimeType)[1];
            $fileName      = "{$mediaId}.{$fileExtension}";

            $parentFolder = getFilePath('conversation');
            $subFolder    = "{$userId}/" . date('Y/m/d');
            $folderPath   = $parentFolder . "/" . $subFolder;
            $filePath     = $folderPath . "/" . $fileName;

            if (!file_exists($folderPath)) {
                mkdir($folderPath, 0755, true);
            }

            file_put_contents($filePath, $fileContent);

            return $subFolder . "/" . $fileName;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    /**
     * Send message via Baileys
     */
    private function messageSendViaBaileys($request, $toNumber, $whatsappAccount)
    {
        $baileysService = new BaileysService();
        
        $message = $request->message ?? '';
        $options = [];
        
        // Handle media uploads
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $mimeType = $file->getMimeType();
            $mediaPath = $this->storeMediaFile($file, $whatsappAccount->user_id);
            $mediaUrl = asset(getFilePath('conversation') . '/' . $mediaPath);
            
            $options['mediaType'] = 'image';
            $options['mediaUrl'] = $mediaUrl;
            $options['caption'] = $message;
            $options['mimeType'] = $mimeType;
        } elseif ($request->hasFile('document')) {
            $file = $request->file('document');
            $mimeType = $file->getMimeType();
            $fileName = $file->getClientOriginalName();
            $mediaPath = $this->storeMediaFile($file, $whatsappAccount->user_id);
            $mediaUrl = asset(getFilePath('conversation') . '/' . $mediaPath);
            
            $options['mediaType'] = 'document';
            $options['mediaUrl'] = $mediaUrl;
            $options['caption'] = $message;
            $options['mimeType'] = $mimeType;
            $options['fileName'] = $fileName;
        } elseif ($request->hasFile('video')) {
            $file = $request->file('video');
            $mimeType = $file->getMimeType();
            $mediaPath = $this->storeMediaFile($file, $whatsappAccount->user_id);
            $mediaUrl = asset(getFilePath('conversation') . '/' . $mediaPath);
            
            $options['mediaType'] = 'video';
            $options['mediaUrl'] = $mediaUrl;
            $options['caption'] = $message;
            $options['mimeType'] = $mimeType;
        }
        
        // Send message via Baileys
        $result = $baileysService->sendMessage(
            $whatsappAccount->baileys_session_id,
            $toNumber,
            $message,
            $options
        );
        
        if (!$result['success']) {
            throw new Exception($result['message']);
        }
        
        // Return format compatible with Meta API response
        return [
            'whatsAppMessage' => [[
                'id' => $result['messageId'] ?? 'baileys_' . time(),
            ]],
            'mediaId'         => null,
            'mediaUrl'        => $options['mediaUrl'] ?? null,
            'mediaPath'       => $mediaPath ?? null,
            'mediaCaption'    => $options['caption'] ?? null,
            'mediaFileName'   => $options['fileName'] ?? null,
            'messageType'     => $options['mediaType'] ?? 'text',
            'mimeType'        => $options['mimeType'] ?? null,
            'mediaType'       => $options['mediaType'] ?? null
        ];
    }
    
    /**
     * Store media file locally
     */
    private function storeMediaFile($file, $userId)
    {
        $fileExtension = $file->getClientOriginalExtension();
        $fileName = uniqid() . '.' . $fileExtension;
        
        $parentFolder = getFilePath('conversation');
        $subFolder = "{$userId}/" . date('Y/m/d');
        $folderPath = $parentFolder . "/" . $subFolder;
        
        if (!file_exists($folderPath)) {
            mkdir($folderPath, 0755, true);
        }
        
        $file->move($folderPath, $fileName);
        
        return $subFolder . "/" . $fileName;
    }

    public function  submitTemplate($businessAccountId, $accessToken, $templateData = [])
    {
        $header = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ];

        try {

            $response = CurlRequest::curlPostContent($this->getWhatsAppBaseUrl() . "{$businessAccountId}/message_templates", $templateData, $header);
            $data     = json_decode($response, true);
            if (!is_array($data) || isset($data['error'])) {
                throw new Exception(@$data['error']['message'] ?? "Something went wrong");
            }
            return $data;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage() ?? "Something went wrong");
        }
    }
}
