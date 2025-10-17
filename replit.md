# OvoWpp - WhatsApp CRM and Marketing Platform

## Overview

OvoWpp is a comprehensive cross-platform SaaS-based WhatsApp CRM and marketing solution built with Laravel. It enables businesses to manage customer relationships, automate marketing campaigns, and handle WhatsApp communications at scale. The platform features a multi-tenant architecture with separate admin and user interfaces, real-time messaging capabilities, and extensive third-party integrations for payments, notifications, and communication services, aiming to streamline business communication and marketing efforts through WhatsApp.

## User Preferences

Preferred communication style: Simple, everyday language.

## Recent Changes (October 17, 2025)

### Media System Improvements
- **File Path Fix**: Corrected media file paths from `../assets/media/conversation` to `assets/media/conversation` in FileInfo.php
- **Supported File Types**:
  - Images: JPG, JPEG, PNG, WEBP (max 25 MB)
  - Videos: MP4, MOV, AVI (max 50 MB) with inline HTML5 player
  - Audio: All formats (max 20 MB) with inline HTML5 player
  - Documents: PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, RAR (max 100 MB)
  - Custom preview icons for each document type (PDF, DOC, XLS, PPT, ZIP)

### Baileys Webhook Fixes
- **Status Update System**: Fixed message status updates (sent/delivered/read) not working
- **Webhook Error Resolution**: Added validation to skip status broadcasts and group messages that were causing 400 errors
- **Status Mapping**: Baileys status (1=sent, 2=delivered, 3=read) correctly mapped to system status
- **Real-time Updates**: Status changes broadcast via Pusher for instant UI updates

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

### Real-time & Messaging
- **Real-time**: Pusher for WebSocket communication and Laravel's broadcasting system.
- **Push Notifications**: Firebase Cloud Messaging (FCM) with service worker implementation.
- **WhatsApp Integration**:
    - **Meta WhatsApp Business API**: Official API for business messaging.
    - **Baileys (@whiskeysockets/baileys)**: Node.js service for direct WhatsApp Web connection, QR code authentication, session management, and webhook handling.
    - **Unified Flow**: Both integrations utilize a common database schema and message processing logic with automatic switching.
    - **Media Support**: Handles various media types (text, images, documents, video, audio) with inline previews, download options, and real-time status updates.

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