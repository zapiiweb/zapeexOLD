# OvoWpp - WhatsApp CRM and Marketing Platform

## Overview

OvoWpp is a cross-platform SaaS-based WhatsApp CRM and marketing solution built with Laravel. It helps businesses manage customer relationships, automate marketing campaigns, and handle WhatsApp communications. The platform features a multi-tenant architecture with separate admin and user interfaces, real-time messaging, and extensive third-party integrations for payments, notifications, and communication services. Its primary purpose is to scale WhatsApp-based business operations efficiently.

## User Preferences

Preferred communication style: Simple, everyday language.

## Recent Changes

### 2025-10-17: Fixed Media Files Display and Download Issues

#### Issue #1: Images Not Displaying in Conversations
**Problem**: Images and media files were not being displayed in conversations. Files were being saved but not shown in the chat interface.

**Root Cause**: The Baileys webhook was **not saving the `media_id` field** when receiving media messages. The view template checks `@if (@$message->media_id)` before rendering images.

**Solution**:
1. **Added `media_id` to Baileys webhook** (`WebhookController.php` line 475):
   - Now saves `$message->media_id = $messageType !== 'text' ? $messageId : null;`
   - Uses WhatsApp message ID as media identifier
   
2. **Updated existing messages**: `UPDATE messages SET media_id = whatsapp_message_id WHERE media_path IS NOT NULL AND media_id IS NULL`

3. **Path configuration**:
   - Changed conversation path to `'../assets/media/conversation'` for PHP operations
   - Enhanced `getImage()` to normalize `../` to `/` for browser URLs

#### Issue #2: Media Download Failing ("Error failed load the media")
**Problem**: Clicking images to download them showed error "Error failed load the media" and redirected to login page.

**Root Causes**: 
1. Download route was **inside middleware groups** `has.subscription`, `has.whatsapp`, and `agent.permission:view inbox`
2. Code tried to access `$user->currentWhatsapp()->access_token` **before** checking if message was an image (causing error for non-agent users)

**Solutions**: 
1. **Moved download route completely outside restrictive middlewares** (`core/routes/user.php` line 128)
   - Now only requires basic authentication (`auth`, `check.status`, `registration.complete`)
   - Works for all authenticated users (agents and non-agents)

2. **Fixed downloadMedia method logic** (`InboxManager.php` line 424):
   - Now checks message type FIRST before accessing WhatsApp account
   - For images: downloads directly from local file (no WhatsApp access needed)
   - For other media: validates WhatsApp exists before accessing token

**Files Modified**:
- `core/app/Http/Controllers/WebhookController.php` - Added media_id assignment (line 475)
- `core/app/Constants/FileInfo.php` - Changed conversation path to `'../assets/media/conversation'`
- `core/app/Http/Helpers/helpers.php` - Added path normalization in getImage()
- `core/routes/user.php` - Moved download route outside all restrictive middlewares (line 128)
- `core/app/Traits/InboxManager.php` - Fixed downloadMedia logic to check message type before accessing WhatsApp
- Database: Updated existing messages with `media_id`

**Technical Details**:
- Media storage: `/home/runner/workspace/assets/media/conversation/{user_id}/{year}/{month}/{day}/{filename}`
- Browser URL: `https://.../assets/media/conversation/{user_id}/{year}/{month}/{day}/{filename}`
- Download route: `/user/inbox/media/download/{media_id}` (now accessible without subscription/whatsapp middlewares)

## System Architecture

### Backend
- **Framework**: Laravel 11.9 (PHP 8.3+) for MVC, routing, and core application logic.
- **Dependency Management**: Composer.

### Frontend
- **Build Tool**: Vite for asset compilation.
- **UI Framework**: Bootstrap 5 for responsive design.
- **JavaScript**: jQuery 3.7.1 for DOM manipulation.
- **Theming**: Custom HSL-based CSS with CSS variables for light/dark mode and modular color schemes.

### Real-time Communication
- **Messaging**: Pusher for real-time messaging and notifications via Laravel Broadcasting.
- **Push Notifications**: Firebase Cloud Messaging for web and mobile.

### Database
- **Type**: MySQL/MariaDB.
- **ORM**: Laravel Eloquent.
- **Schema Management**: Laravel Migrations.

### Authentication
- **API**: Laravel Sanctum for SPA/mobile.
- **Web**: Laravel UI with multi-guard authentication for admin and users.
- **Social**: Laravel Socialite for OAuth.

### WhatsApp Integration
- **Official API**: Meta WhatsApp Business API for official business messaging.
- **Direct Connection**: Baileys (@whiskeysockets/baileys) Node.js service (port 3001) for direct WhatsApp Web connection, QR authentication, session persistence, and message handling (text, media).
- **Unified Flow**: Automatic switching between Meta and Baileys, using a consistent message processing logic and database schema.
- **Media**: HTML5 players for audio/video, document icons, and download functionality.

### File Management
- **Image Processing**: Intervention Image.
- **Spreadsheets**: PhpSpreadsheet for Excel/CSV.
- **PDF Generation**: DomPDF.
- **Security**: HTMLPurifier for XSS protection.

### Admin Panel
- **Data Visualization**: ApexCharts.
- **Code Editor**: CodeMirror.
- **UI Components**: jQuery UI, Summernote (WYSIWYG), Select2.

### Frontend UI
- **Components**: Slick Carousel, Flatpickr (date picker), iziToast (notifications).
- **Animations**: WOW.js.
- **Text Formatting**: Custom Markdown parser for chat.

### Security & Validation
- **Web**: CSRF Protection.
- **Input**: libphonenumber-for-php for phone numbers, HTMLPurifier for input sanitization.
- **API**: Rate Limiting.

### Other
- **Internationalization**: Multi-language support using JSON files.
- **Task Management**: Custom cron job system, Laravel Queue for background tasks.

## External Dependencies

### Payment Processors
- Stripe API
- Razorpay SDK
- Authorize.Net SDK
- Mollie Laravel package
- BTCPay Server Greenfield API
- CoinGate PHP SDK

### Communication APIs
- Twilio SDK
- Vonage Client
- MessageBird REST API
- SendGrid Email API
- Mailjet API

### Google Services
- Google API Client (for Firebase)

### Real-time Services
- Pusher PHP Server
- Firebase Cloud Messaging

### File Processing
- PhpSpreadsheet
- DomPDF
- Intervention Image

### Utilities
- Guzzle HTTP
- libphonenumber-for-php
- HTMLPurifier