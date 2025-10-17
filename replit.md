# OvoWpp - WhatsApp CRM and Marketing Platform

## Overview

OvoWpp is a cross-platform SaaS-based WhatsApp CRM and marketing solution built with Laravel. It helps businesses manage customer relationships, automate marketing campaigns, and handle WhatsApp communications. The platform features a multi-tenant architecture with separate admin and user interfaces, real-time messaging, and extensive third-party integrations for payments, notifications, and communication services. Its primary purpose is to scale WhatsApp-based business operations efficiently.

## User Preferences

Preferred communication style: Simple, everyday language.

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