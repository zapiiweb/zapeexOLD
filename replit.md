# OvoWpp - WhatsApp CRM and Marketing Platform

## Overview
OvoWpp is a comprehensive cross-platform SaaS-based WhatsApp CRM and marketing solution built with Laravel. It enables businesses to manage customer relationships, automate marketing campaigns, and handle WhatsApp communications at scale. The platform features a multi-tenant architecture with separate admin and user interfaces, real-time messaging capabilities, and extensive third-party integrations for payments, notifications, and communication services, aiming to provide a robust solution for businesses to leverage WhatsApp for customer engagement and marketing.

## User Preferences
Preferred communication style: Simple, everyday language.

## System Architecture

### Backend Framework
- **Laravel 11.9**: Core PHP framework for MVC architecture.
- **PHP 8.3+**: Server-side scripting language.
- **Composer**: PHP dependency management.

### Frontend Architecture
- **Vite**: Modern build tool for asset compilation.
- **Bootstrap 5**: CSS framework for responsive UI.
- **jQuery 3.7.1**: JavaScript library.
- **Custom CSS Architecture**: HSL-based color system with CSS custom properties, supporting light/dark mode and responsive design.

### Real-time Communication
- **Pusher**: WebSocket service for real-time messaging and notifications.
- **Firebase Cloud Messaging**: Push notifications for web and mobile.

### Database & ORM
- **MySQL/MariaDB**: Primary relational database.
- **Laravel Eloquent**: ORM for database interactions.

### Authentication & Authorization
- **Laravel Sanctum**: API authentication.
- **Multi-guard Authentication**: Separate authentication for admin and users.
- **Social Authentication**: Via Laravel Socialite.

### WhatsApp Messaging Integration
- **Meta WhatsApp Business API**: Official API for business messaging.
- **Baileys (@whiskeysockets/baileys)**: Node.js service for direct WhatsApp Web connection, session management, and message handling via webhook.
- **Automatic Switching**: System automatically uses Baileys when connected, falls back to Meta API otherwise.
- **Unified Message Flow**: Consistent database schema and processing for both integrations.
- **Media Support**: HTML5 players for audio/video, document icons for files.

### File Processing & Generation
- **Intervention Image**: Image manipulation.
- **PhpSpreadsheet**: Excel/CSV file generation and parsing.
- **DomPDF**: PDF generation from HTML.
- **HTMLPurifier**: XSS protection for user-generated content.

### Admin Panel Features
- **ApexCharts**: Interactive data visualizations.
- **CodeMirror**: Syntax-highlighted code editor.
- **Summernote**: WYSIWYG rich text editor.

### Security & Validation
- **CSRF Protection**: Laravel's built-in token validation.
- **Phone Number Validation**: Using `libphonenumber-for-php`.
- **Input Sanitization**: HTMLPurifier for cleaning user input.

### Internationalization
- **Multi-language Support**: JSON-based translation files.

### Task Scheduling & Queue Management
- **Cron Job Management**: Custom database-driven scheduler.
- **Laravel Queue**: Asynchronous job processing.

## External Dependencies

### Payment Processors
- Stripe API
- Razorpay SDK
- Authorize.Net SDK
- Mollie Laravel package
- BTCPay Server Greenfield API
- CoinGate PHP SDK

### Communication APIs
- Twilio SDK (SMS, WhatsApp, Voice)
- Vonage Client (SMS and communication)
- MessageBird REST API
- SendGrid Email API
- Mailjet API

### Google Services
- Google API Client (Firebase)

### Real-time Services
- Pusher PHP Server
- Firebase Cloud Messaging

### File Processing
- PhpSpreadsheet
- DomPDF
- Intervention Image

### Utilities
- Guzzle HTTP (for API requests)
- libphonenumber (for phone validation)
- HTMLPurifier (for XSS prevention)