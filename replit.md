# OvoWpp - WhatsApp CRM and Marketing Platform

## Overview

OvoWpp is a comprehensive cross-platform SaaS-based WhatsApp CRM and marketing solution built with Laravel. It enables businesses to manage customer relationships, automate marketing campaigns, and handle WhatsApp communications at scale. The platform features a multi-tenant architecture with separate admin and user interfaces, real-time messaging, and extensive third-party integrations for payments, notifications, and communication services. Its purpose is to provide an all-in-one solution for businesses to leverage WhatsApp for customer engagement and marketing, aiming for significant market potential in the CRM and marketing automation sector.

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Core Technologies
- **Backend**: Laravel 11.9, PHP 8.3+, Composer
- **Frontend**: Vite, Bootstrap 5, jQuery 3.7.1
- **Database**: MySQL/MariaDB with Laravel Eloquent ORM
- **Real-time**: Pusher for WebSockets, Firebase Cloud Messaging for push notifications
- **Version Control**: Git

### UI/UX Decisions
- **Design System**: HSL-based color system with CSS custom properties, supporting light/dark modes.
- **Responsiveness**: Mobile-first approach.
- **Component Libraries**: Bootstrap 5 for UI, Slick Carousel, Flatpickr, iziToast.
- **Admin Panel**: ApexCharts for data visualization, CodeMirror for templates, Summernote for rich text editing.

### Technical Implementations
- **Authentication**: Laravel Sanctum for API, Laravel UI for web, Multi-guard for admin/user, Laravel Socialite for OAuth.
- **WhatsApp Integration**:
    - **Meta WhatsApp Business API**: Official API for template messages, media, and webhooks.
    - **Baileys (@whiskeysockets/baileys)**: Node.js service for direct WhatsApp Web connection, QR code authentication, session management, and real-time messaging. Handles text, images, documents, video, and audio.
    - **Automatic Switching**: System intelligently switches between Baileys (if QR connected) and Meta API.
    - **Unified Message Flow**: Consistent data handling across both integrations.
- **Payment Gateways**: Integrated with multiple providers (Stripe, Razorpay, Mollie, Authorize.Net, BTCPay Server, CoinGate).
- **Communication Services**: Twilio, Vonage, MessageBird for SMS/WhatsApp/Voice; PHPMailer, SendGrid, Mailjet for email.
- **File Processing**: Intervention Image for image manipulation, PhpSpreadsheet for Excel/CSV, DomPDF for PDF generation, HTMLPurifier for XSS protection.
- **Internationalization**: Multi-language support using JSON translation files.
- **Task Management**: Database-driven cron job scheduler, Laravel Queue for asynchronous tasks.
- **Security**: CSRF protection, phone number validation (libphonenumber-for-php), input sanitization, rate limiting.

### System Design Choices
- **Multi-tenancy**: Separate admin and user interfaces.
- **Modularity**: Laravel's MVC structure, clear separation of concerns.
- **Extensibility**: Designed for easy integration of new services and features.
- **Error Handling**: Custom error pages, comprehensive debug logging.

## External Dependencies

### Payment Gateways
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
- Google API Client (Firebase)

### Real-time Services
- Pusher PHP Server
- Firebase Cloud Messaging

### File Processing & Utilities
- PhpSpreadsheet
- DomPDF
- Intervention Image
- Guzzle HTTP
- libphonenumber-for-php
- HTMLPurifier