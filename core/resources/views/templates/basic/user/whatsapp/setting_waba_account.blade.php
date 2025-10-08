@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-container">

        <div class="container-top">
            <div class="container-top__left">
                <h5 class="container-top__title">{{ __(@$pageTitle) }}</h5>
                <p class="container-top__desc">@lang('On this page you’ll able to change access token for the WhatsApp Business Account. Make sure you have taken the access token from your')
                    <a target="_blank" href="https://developers.facebook.com/apps/">
                        <i class="la la-external-link"></i> @lang('Meta Dashboard')
                    </a>
                </p>
            </div>
            <div class="container-top__right">
                <div class="btn--group">
                    <a href="{{ route('user.whatsapp.account.index') }}" class="btn btn--dark"><i class="las la-undo"></i>
                        @lang('Back')</a>
                    <button type="submit" form="whatsapp-meta-form" class="btn btn--base btn-shadow">
                        <i class="lab la-telegram"></i>
                        @lang('Update Token')
                    </button>
                </div>
            </div>
        </div>
        <div class="dashboard-container__body">
            <form id="whatsapp-meta-form" method="POST"
                action="{{ route('user.whatsapp.account.setting.confirm', @$whatsappAccount->id) }}">
                @csrf
                <div class="row gy-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="label-two">@lang('Business Name')</label>
                            <input type="text" class="form--control form-two" name="business_name"
                                placeholder="@lang('Enter your business name')" value="{{ @$whatsappAccount->business_name }}" readonly
                                required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="label-two">@lang('WhatsApp Number')</label>
                            <input type="text" class="form--control form-two" name="whatsapp_number"
                                placeholder="@lang('Enter your WhatsApp number with country code')" value="{{ @$whatsappAccount->phone_number }}" readonly
                                required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="label-two">@lang('WhatsApp Business Account ID')</label>
                            <input type="text" class="form--control form-two" name="whatsapp_business_account_id"
                                placeholder="@lang('Enter business account ID')"
                                value="{{ @$whatsappAccount->whatsapp_business_account_id }}" readonly required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label class="label-two">@lang('WhatsApp Phone Number ID')</label>
                            <input type="text" class="form--control form-two" name="phone_number_id"
                                placeholder="@lang('Enter phone number ID')" value="{{ @$whatsappAccount->phone_number_id }}" readonly
                                required>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group">
                            <label class="label-two">
                                @lang('Meta Access Token')
                            </label>
                            <i class="fas fa-info-circle text--info ms-1" data-toggle="tooltip" data-placement="top"
                                title="@lang('If you change the access token, the current token will be expired.')">
                            </i>
                            <input type="text" class="form--control form-two" name="meta_access_token"
                                placeholder="@lang('Enter your access token')" value="{{ @$whatsappAccount->access_token }}" required>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Baileys WhatsApp Connection Section -->
        <div class="dashboard-container__body mt-4">
            <div class="card">
                <div class="card-header bg--light">
                    <h5 class="mb-0">
                        <i class="lab la-whatsapp"></i> @lang('WhatsApp Direct Connection (Baileys)')
                    </h5>
                    <p class="mb-0 mt-2 text-muted">@lang('Connect your WhatsApp directly by scanning a QR code. No Meta Business Account needed.')</p>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="baileys-connection-info">
                                @if($whatsappAccount->baileys_connected)
                                    <div class="alert alert-success">
                                        <i class="las la-check-circle"></i> 
                                        @lang('WhatsApp Connected')
                                        @if($whatsappAccount->baileys_phone_number)
                                            <br>
                                            <small>@lang('Phone'): {{ $whatsappAccount->baileys_phone_number }}</small>
                                        @endif
                                        @if($whatsappAccount->baileys_connected_at)
                                            <br>
                                            <small>@lang('Connected at'): {{ $whatsappAccount->baileys_connected_at->format('d/m/Y H:i') }}</small>
                                        @endif
                                    </div>
                                    <button type="button" class="btn btn--danger btn-disconnect" data-account-id="{{ $whatsappAccount->id }}">
                                        <i class="las la-unlink"></i> @lang('Disconnect')
                                    </button>
                                @else
                                    <div class="alert alert-info">
                                        <i class="las la-info-circle"></i> 
                                        @lang('Not connected. Click "Generate QR Code" to connect your WhatsApp.')
                                    </div>
                                    <button type="button" class="btn btn--base btn-start-session" data-account-id="{{ $whatsappAccount->id }}">
                                        <i class="las la-qrcode"></i> @lang('Generate QR Code')
                                    </button>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="qr-code-container text-center" style="min-height: 300px; display: flex; align-items: center; justify-content: center; border: 2px dashed #ddd; border-radius: 10px;">
                                <div class="qr-placeholder">
                                    <i class="las la-qrcode" style="font-size: 80px; color: #ccc;"></i>
                                    <p class="text-muted mt-2">@lang('QR code will appear here')</p>
                                </div>
                                <div class="qr-code" style="display: none;">
                                    <canvas id="qrCanvas"></canvas>
                                    <p class="mt-3 text-muted small">@lang('Scan this QR code with your WhatsApp mobile app')</p>
                                </div>
                                <div class="qr-loading" style="display: none;">
                                    <div class="spinner-border text-primary" role="status">
                                        <span class="sr-only">@lang('Loading...')</span>
                                    </div>
                                    <p class="mt-2">@lang('Generating QR code...')</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('topbar_tabs')
    @include('Template::partials.profile_tab')
@endpush

@push('script-lib')
    <script src="https://cdn.jsdelivr.net/npm/qrious@4.0.2/dist/qrious.min.js"></script>
@endpush

@push('script')
<script>
(function($) {
    "use strict";

    const accountId = "{{ $whatsappAccount->id }}";
    let statusCheckInterval = null;

    // Start session and generate QR code
    $('.btn-start-session').on('click', function() {
        const btn = $(this);
        btn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> @lang("Starting...")');

        $('.qr-placeholder').hide();
        $('.qr-code').hide();
        $('.qr-loading').show();

        $.ajax({
            url: "{{ route('user.whatsapp.account.baileys.start', '') }}/" + accountId,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.remark === 'success') {
                    iziToast.success({
                        message: response.message,
                        position: "topRight"
                    });
                    
                    // Start polling for QR code
                    pollForQRCode();
                } else {
                    iziToast.error({
                        message: response.message || '@lang("Failed to start session")',
                        position: "topRight"
                    });
                    $('.qr-loading').hide();
                    $('.qr-placeholder').show();
                    btn.prop('disabled', false).html('<i class="las la-qrcode"></i> @lang("Generate QR Code")');
                }
            },
            error: function(xhr) {
                iziToast.error({
                    message: '@lang("Error starting session")',
                    position: "topRight"
                });
                $('.qr-loading').hide();
                $('.qr-placeholder').show();
                btn.prop('disabled', false).html('<i class="las la-qrcode"></i> @lang("Generate QR Code")');
            }
        });
    });

    // Poll for QR code
    function pollForQRCode() {
        let attempts = 0;
        const maxAttempts = 30;

        const interval = setInterval(function() {
            attempts++;

            if (attempts > maxAttempts) {
                clearInterval(interval);
                $('.qr-loading').hide();
                $('.qr-placeholder').show();
                $('.btn-start-session').prop('disabled', false).html('<i class="las la-qrcode"></i> @lang("Generate QR Code")');
                iziToast.error({
                    message: '@lang("QR code generation timeout. Please try again.")',
                    position: "topRight"
                });
                return;
            }

            $.ajax({
                url: "{{ route('user.whatsapp.account.baileys.qr', '') }}/" + accountId,
                type: 'GET',
                success: function(response) {
                    if (response.success && response.qr) {
                        clearInterval(interval);
                        displayQRCode(response.qr);
                        // Start checking connection status
                        startStatusCheck();
                    }
                }
            });
        }, 1000);
    }

    // Display QR code
    function displayQRCode(qrData) {
        $('.qr-loading').hide();
        $('.qr-placeholder').hide();
        $('.qr-code').show();

        const canvas = document.getElementById('qrCanvas');
        new QRious({
            element: canvas,
            value: qrData,
            size: 300,
            level: 'H'
        });

        $('.btn-start-session').prop('disabled', false).html('<i class="las la-sync"></i> @lang("Refresh QR Code")');
    }

    // Check connection status
    function startStatusCheck() {
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
        }

        statusCheckInterval = setInterval(function() {
            $.ajax({
                url: "{{ route('user.whatsapp.account.baileys.status', '') }}/" + accountId,
                type: 'GET',
                success: function(response) {
                    if (response.success && response.connected) {
                        clearInterval(statusCheckInterval);
                        iziToast.success({
                            message: '@lang("WhatsApp connected successfully!")',
                            position: "topRight"
                        });
                        // Reload page to show connected status
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    }
                }
            });
        }, 3000);
    }

    // Disconnect
    $('.btn-disconnect').on('click', function() {
        if (!confirm('@lang("Are you sure you want to disconnect?")')) {
            return;
        }

        const btn = $(this);
        btn.prop('disabled', true).html('<i class="las la-spinner la-spin"></i> @lang("Disconnecting...")');

        $.ajax({
            url: "{{ route('user.whatsapp.account.baileys.disconnect', '') }}/" + accountId,
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.remark === 'success') {
                    iziToast.success({
                        message: response.message,
                        position: "topRight"
                    });
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    iziToast.error({
                        message: response.message || '@lang("Failed to disconnect")',
                        position: "topRight"
                    });
                    btn.prop('disabled', false).html('<i class="las la-unlink"></i> @lang("Disconnect")');
                }
            },
            error: function() {
                iziToast.error({
                    message: '@lang("Error disconnecting")',
                    position: "topRight"
                });
                btn.prop('disabled', false).html('<i class="las la-unlink"></i> @lang("Disconnect")');
            }
        });
    });

    // Clean up interval on page unload
    $(window).on('beforeunload', function() {
        if (statusCheckInterval) {
            clearInterval(statusCheckInterval);
        }
    });

})(jQuery);
</script>
@endpush
