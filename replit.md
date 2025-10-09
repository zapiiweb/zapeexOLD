# OvoWpp - WhatsApp CRM and Marketing Platform

## Overview

OvoWpp is a comprehensive cross-platform SaaS-based WhatsApp CRM and marketing solution built with Laravel. The platform enables businesses to manage customer relationships, automate marketing campaigns, and handle WhatsApp communications at scale. It features a multi-tenant architecture with separate admin and user interfaces, real-time messaging capabilities, and extensive third-party integrations for payments, notifications, and communication services.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Backend Framework
- **Laravel 11.9**: Core PHP framework providing MVC architecture, routing, and application structure
- **PHP 8.3+**: Server-side scripting language with modern features (BCMath, GD, cURL extensions required)
- **Composer**: Dependency management for PHP packages

### Frontend Architecture
- **Vite**: Modern build tool replacing Laravel Mix for asset compilation
- **Bootstrap 5**: CSS framework for responsive UI components
- **jQuery 3.7.1**: JavaScript library for DOM manipulation and AJAX
- **Custom CSS Architecture**: HSL-based color system with CSS custom properties for theming
  - Supports light/dark mode with `data-theme` attribute
  - Modular color system (primary, secondary, success, danger, warning)
  - Responsive design with mobile-first approach

### Real-time Communication
- **Pusher**: WebSocket service for real-time messaging and notifications
- **Broadcasting System**: Laravel's event broadcasting integrated with Pusher
- **Firebase Cloud Messaging**: Push notifications for web and mobile
  - Service worker implementation for background messaging
  - Configuration stored in `assets/admin/push_config.json`

### Database & ORM
- **MySQL/MariaDB**: Primary relational database (via PDO MySQL extension)
- **Laravel Eloquent**: ORM for database interactions
- **Migrations**: Version-controlled database schema management

### Authentication & Authorization
- **Laravel Sanctum**: API authentication for SPA and mobile applications
- **Laravel UI**: Scaffolding for authentication views
- **Multi-guard Authentication**: Separate authentication for admin and users
- **Social Authentication**: Laravel Socialite for OAuth providers

### Payment Gateway Integrations
- **Stripe**: Credit card processing
- **Razorpay**: Indian payment gateway
- **Mollie**: European payment provider
- **Authorize.Net**: Payment processing
- **BTCPay Server**: Cryptocurrency payments
- **CoinGate**: Crypto payment gateway

### Communication Services
- **Twilio**: SMS and WhatsApp messaging API
- **Vonage (Nexmo)**: SMS and voice services
- **MessageBird**: Omnichannel communication platform
- **Email Services**:
  - PHPMailer for SMTP
  - SendGrid API integration
  - Mailjet API integration

### WhatsApp Messaging Integration
- **Meta WhatsApp Business API**: Official API for business messaging with templates, media, and webhooks
- **Baileys (@whiskeysockets/baileys)**: Direct WhatsApp Web connection for automated QR code authentication
  - Runs as separate Node.js service on port 3001
  - REST API for session management, QR generation, and message sending
  - Webhook system for receiving incoming messages and status updates
  - Automatic session persistence with multi-file auth state
  - Supports text, images, documents, video, and audio messages
  - Real-time message status updates: sent (1) → delivered (2) → read/played (3/4)
  - Session files protected via .gitignore (auth_sessions/ directory)
- **Automatic Switching**: System automatically selects Baileys when QR connected, falls back to Meta API otherwise
- **Unified Message Flow**: Both integrations use the same database schema and message processing logic
- **Media Support**: HTML5 players for audio/video with download functionality, document icons for files (PDF, DOC, XLS, etc.)
- **UI/UX**: Proper z-index layering ensures modals and popups overlay correctly over media players and buttons

### File Processing & Generation
- **Intervention Image**: Image manipulation and processing
- **PhpSpreadsheet**: Excel/CSV file generation and parsing
- **DomPDF**: PDF generation from HTML templates
- **HTMLPurifier**: XSS protection for user-generated content

### Admin Panel Features
- **ApexCharts**: Interactive data visualizations
- **CodeMirror**: Syntax-highlighted code editor for templates
- **Spectrum**: Color picker for theme customization
- **jQuery UI**: Advanced UI components and interactions
- **Summernote**: WYSIWYG rich text editor
- **Select2**: Enhanced select dropdowns with search

### Frontend User Interface
- **Slick Carousel**: Responsive carousel/slider component
- **WOW.js**: Scroll animation library
- **Flatpickr**: Lightweight date picker
- **iziToast**: Notification toast library
- **Custom Markdown Parser**: Lightweight text formatting for chat

### Security & Validation
- **CSRF Protection**: Laravel's built-in token validation
- **Phone Number Validation**: libphonenumber-for-php for international formats
- **Input Sanitization**: HTMLPurifier for cleaning user input
- **Rate Limiting**: API throttling and request limiting

### Internationalization
- **Multi-language Support**: JSON-based translation files (en, es, pt)
- **Laravel Localization**: Built-in translation helpers

### Task Scheduling & Queue Management
- **Cron Job Management**: Custom implementation with database-driven scheduler
- **Laravel Queue**: Asynchronous job processing
- **Background Tasks**: Email sending, report generation, automated campaigns

### Development Tools
- **Laravel Debugbar**: Development debugging toolbar
- **Laravel Pint**: Code style fixer
- **PHPUnit**: Unit and feature testing framework
- **Faker**: Test data generation

### Asset Management
- **Version Control**: Separate minified and unminified JavaScript files
- **CDN Strategy**: Local hosting of third-party libraries for reliability
- **Asset Compilation**: Vite for modern bundling with hot reload

### Error Handling & Logging
- **Custom Error Pages**: Branded 404/500 error templates
- **Debug Logging**: Comprehensive error tracking with context
- **Spatie Laravel Ignition**: Enhanced error pages for development

## External Dependencies

### Payment Processors
- Stripe API (stripe/stripe-php)
- Razorpay SDK (razorpay/razorpay)
- Authorize.Net SDK (authorizenet/authorizenet)
- Mollie Laravel package (mollie/laravel-mollie)
- BTCPay Server Greenfield API (btcpayserver/btcpayserver-greenfield-php)
- CoinGate PHP SDK (coingate/coingate-php)

### Communication APIs
- Twilio SDK (twilio/sdk) - SMS, WhatsApp, Voice
- Vonage Client (vonage/client) - SMS and communication
- MessageBird REST API (messagebird/php-rest-api)
- SendGrid Email API (sendgrid/sendgrid)
- Mailjet API (mailjet/mailjet-apiv3-php)

### Google Services
- Google API Client (google/apiclient) - Firebase, Analytics, Cloud services

### Real-time Services
- Pusher PHP Server (pusher/pusher-php-server) - WebSocket connections
- Firebase Cloud Messaging - Push notifications via service worker

### File Processing
- PhpSpreadsheet (phpoffice/phpspreadsheet) - Excel/CSV operations
- DomPDF (barryvdh/laravel-dompdf) - PDF generation
- Intervention Image (intervention/image) - Image processing

### Utilities
- Guzzle HTTP (guzzlehttp/guzzle) - HTTP client for API requests
- libphonenumber (giggsey/libphonenumber-for-php) - Phone validation
- HTMLPurifier (ezyang/htmlpurifier) - XSS prevention