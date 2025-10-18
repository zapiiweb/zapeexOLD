# OvoWpp - WhatsApp CRM and Marketing Platform

## Overview

OvoWpp is a comprehensive cross-platform SaaS-based WhatsApp CRM and marketing solution built with Laravel. It enables businesses to manage customer relationships, automate marketing campaigns, and handle WhatsApp communications at scale. The platform features a multi-tenant architecture with separate admin and user interfaces, real-time messaging capabilities, and extensive third-party integrations for payments, notifications, and communication services, aiming to streamline business communication and marketing efforts through WhatsApp.

## User Preferences

Preferred communication style: Simple, everyday language.
Primary Language: Portuguese (pt-BR) for user-facing messages and error handling.

## System Architecture

### Backend
- **Framework**: Laravel 11.9 on PHP 8.3+ with Composer for dependency management.
- **Database**: MySQL/MariaDB with Laravel Eloquent ORM and Migrations.
- **Authentication**: Laravel Sanctum for API, Laravel UI for web, Multi-guard for admin/users, and Laravel Socialite for OAuth.
- **Task Management**: Cron job management and Laravel Queue for asynchronous processing.
- **Security**: CSRF protection, libphonenumber-for-php for phone validation, HTMLPurifier for XSS prevention, and API rate limiting.

### Frontend
- **Build Tool**: Vite for asset compilation.
- **UI Framework**: Bootstrap 5 for responsive design.
- **JavaScript**: jQuery 3.7.1 for DOM manipulation.
- **Styling**: Custom HSL-based CSS architecture with CSS custom properties for theming (light/dark mode support, modular color system).
- **Components**: Slick Carousel, WOW.js for animations, Flatpickr for date selection, iziToast for notifications, and a custom Markdown parser.
- **UI Features**: Collapsible sidebar menu with localStorage persistence, smooth transitions, and hover-expand functionality on desktop (visible only on screens >991px).

### Real-time & Messaging
- **Real-time**: Pusher for WebSocket communication and Laravel's broadcasting system.
- **Push Notifications**: Firebase Cloud Messaging (FCM) with service worker implementation.
- **WhatsApp Integration**:
    - **Meta WhatsApp Business API**: Official API for business messaging with synchronous message delivery.
    - **Baileys (@whiskeysockets/baileys)**: Node.js service for direct WhatsApp Web connection, QR code authentication, session management, and webhook handling with asynchronous job-based delivery. Webhook automatically filters out WhatsApp Status broadcasts (`status@broadcast`) and group messages (`@g.us`) to process only individual conversations.
    - **Connection Type Selection**: Users explicitly choose their preferred connection method via UI toggle:
        - `connection_type` field in `whatsapp_accounts` table (1=Meta API, 2=Baileys, default=1)
        - UI toggle in WhatsApp Account settings page syncs with database via AJAX
        - Message sending logic validates connection status based on user's selected type
    - **Error Handling**: Context-aware error messages:
        - Meta API (connection_type=1): "Token de acesso da Meta API está expirado ou inválido" when token issues occur
        - Baileys (connection_type=2): "WhatsApp está desconectado. Por favor, reconecte sua conta" when not connected
    - **Message Status Tracking**: 
        - Baileys: Messages saved with `job_id` and Status::SCHEDULED, updated to Status::SENT via webhook when delivery confirms.
        - Meta API: Messages saved with `whatsapp_message_id` and Status::SENT immediately upon synchronous response.
    - **Media Support**: Handles various media types (text, images, documents, video, audio) with inline previews (images up to 25MB, videos up to 50MB with HTML5 player, audio up to 20MB with HTML5 player), download buttons, and real-time status updates.
    - **AI Auto-Response**: Both Meta API and Baileys connection types support AI-powered automatic responses identically:
        - AI responses sent via same connection method that received the message (`conversation->whatsappAccount`)
        - Auto-reactivation feature allows IA to resume after manual fallback response (configurable delay or immediate)
        - Fallback mechanism when AI cannot answer: sends customizable fallback message and marks conversation for human intervention
        - UI configuration available in AI Assistant page for fallback messages and auto-reactivation settings

### File Processing
- **Image Manipulation**: Intervention Image.
- **Spreadsheet**: PhpSpreadsheet for Excel/CSV.
- **PDF Generation**: DomPDF from HTML templates.

### Admin Panel
- **Data Visualization**: ApexCharts.
- **Code Editor**: CodeMirror for syntax highlighting.
- **UI Enhancements**: Spectrum color picker, jQuery UI, Summernote WYSIWYG editor, and Select2 for enhanced dropdowns.

### Internationalization
- Multi-language support using JSON-based translation files and Laravel Localization.

## External Dependencies

### Payment Gateways
- Stripe API (stripe/stripe-php)
- Razorpay SDK (razorpay/razorpay)
- Authorize.Net SDK (authorizenet/authorizenet)
- Mollie Laravel package (mollie/laravel-mollie)
- BTCPay Server Greenfield API (btcpayserver/btcpayserver-greenfield-php)
- CoinGate PHP SDK (coingate/coingate-php)

### Communication Services
- Twilio SDK (twilio/sdk)
- Vonage Client (vonage/client)
- MessageBird REST API (messagebird/php-rest-api)
- SendGrid Email API (sendgrid/sendgrid)
- Mailjet API (mailjet/mailjet-apiv3-php)

### Real-time Services
- Pusher PHP Server (pusher/pusher-php-server)
- Firebase Cloud Messaging (via Google API Client)

### File Processing & Utilities
- PhpSpreadsheet (phpoffice/phpspreadsheet)
- DomPDF (barryvdh/laravel-dompdf)
- Intervention Image (intervention/image)
- Guzzle HTTP (guzzlehttp/guzzle)
- libphonenumber (giggsey/libphonenumber-for-php)
- HTMLPurifier (ezyang/htmlpurifier)