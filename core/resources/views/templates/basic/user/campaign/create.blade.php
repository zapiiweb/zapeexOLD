@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-container">
        <div class="container-top">
            <div class="container-top__left">
                <h5 class="container-top__title">{{ __(@$pageTitle) }}</h5>
                <p class="container-top__desc">@lang('Start your next campaign by completing the form below.')</p>
            </div>
            <div class="container-top__right">
                <div class="btn--group">
                    <a href="{{ route('user.campaign.index') }}" class="btn btn--dark">
                        <i class="las la-undo"></i>
                        @lang('Back')
                    </a>
                    <button type="submit" form="campaign-form" class="btn btn--base btn-shadow">
                        <i class="lab la-telegram"></i> @lang('Save Campaign')
                    </button>
                </div>
            </div>
        </div>
        <div class="dashboard-container__body">
            <div class="information-wrapper">
                <form action="{{ route('user.campaign.save') }}" method="POST" class="information-main-form"
                    id="campaign-form">
                    @csrf
                    <div class="row gy-2">
                        <div class="col-12">
                            <div class="form-group">
                                <label class="label-two">@lang('Title')</label>
                                <input type="text" class="form--control form-two" name="title"
                                    placeholder="@lang('Enter campaign title')" required value="{{ old('title') }}">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="label-two">@lang('Whatsapp Account')</label>
                                <x-whatsapp_account />
                                <span class="fs-12 text-dark">
                                    <i>@lang('Please select the WhatsApp account from which the message will be sent or the campaign will be started')</i>
                                </span>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="label-two">@lang('Contact From List')</label>
                                <select name="contact_lists[]" class="form--control select2 form-two contactList"
                                    data-minimum-results-for-search="-1" data-placeholder="Choose contact list" multiple>
                                    @foreach ($contactLists as $list)
                                        <option value="{{ $list->id }}" @selected(in_array($list->id, old('contact_lists', [])))>
                                            {{ __(@$list->name) . ' (' . $list->contact->count() . ')' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="label-two">@lang('Contact From Tag')</label>
                                <select name="contact_tags[]" class="form--control select2 form-two contactTag"
                                    data-minimum-results-for-search="-1" data-placeholder="Choose contact tags" multiple>
                                    @foreach ($contactTags as $contactTag)
                                        <option value="{{ $contactTag->id }}" @selected(in_array($contactTag->id, old('contact_tags', [])))>
                                            {{ __(@$contactTag->name) . ' (' . $contactTag->contacts->count() . ')' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12" id="schedule">
                            <div class="form-group">
                                <label class="label-two">@lang('Send At')</label>
                                <select name="schedule" class="form--control form-two select2"
                                    data-minimum-results-for-search="-1" required>
                                    <option value="off">@lang('Send Now')</option>
                                    <option value="on" selected>@lang('Schedule At')</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6 d-none" id="date">
                            <div class="form-group">
                                <label class="label-two">@lang('Scheduled At')</label>
                                <input type="date" class="form--control form-two date" name="scheduled_at">
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="form-group">
                                <label class="label-two">@lang('Choose template')</label>
                                <select name="template_id" class="form--control select2 form-two"
                                    data-minimum-results-for-search="-1" required>
                                    <option value="" disabled >@lang('Please select whatsapp account first')</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 header-variable-area d-none">
                            <div class="row justify-content-center">
                                <div class="col-lg-6">
                                    <div class="auth-devider text-center">
                                        <span> @lang('HEADER VARIABLES')</span>
                                    </div>
                                </div>
                            </div>
                            <div id="template-header-variables"></div>
                        </div>
                        <div class="col-12 body-variable-area d-none">
                            <div class="row justify-content-center">
                                <div class="col-lg-6">
                                    <div class="auth-devider text-center">
                                        <span> @lang('BODY VARIABLES')</span>
                                    </div>
                                </div>
                            </div>
                            <div id="template-body-variables"></div>
                        </div>
                        <div class="col-12">
                            @foreach (variableShortCodes() as $key => $value)
                                <span class="btn btn--sm btn--dark code-btn"
                                    data-code="{{ $value }}">{{ $value }}</span>
                            @endforeach
                        </div>
                    </div>
                </form>
                <div class="template-info-container__right">
                    <div class="preview-item">
                        <div class="preview-item__header">
                            <h5 class="preview-item__title">@lang('Template Preview')</h5>
                        </div>
                        <div class="preview-item__content FL;E">
                            <div class="preview-item__shape">
                                <img src="{{ getImage($activeTemplateTrue . 'images/preview-1.png') }}" alt="image">
                            </div>
                            <div>
                                <div class="card-item">
                                    <div class="card-item__thumb header_media">
                                        <img src="{{ getImage($activeTemplateTrue . 'images/preview-1.png') }}"
                                            alt="image">
                                    </div>
                                    <div class="card-item__content">
                                        <p class="card-item__title header_text">@lang('Template header')</p>
                                        <p class="card-item__desc body_text">@lang('Template body')</p>
                                        <p class="text-wrapper">
                                            <span class="text footer_text">@lang('Footer text')</span>
                                        </p>
                                    </div>
                                </div>
                                <div class="button-preview mt-2 d-flex gap-2 flex-column">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('style-lib')
    <link rel="stylesheet" type="text/css" href="{{ asset('assets/global/css/flatpickr.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/global/css/select2.min.css') }}" />
@endpush

@push('script-lib')
    <script src="{{ asset('assets/global/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/global/js/flatpickr.min.js') }}"></script>
@endpush


@push('script')
    <script>
        (function($) {
            "use strict";

            $("select[name=schedule]").on('change', function(e) {
                toggleDateField();
            });

            function toggleDateField() {
                let schedule = $("select[name=schedule]").val();
                if (schedule == 'on') {
                    $("#schedule").removeClass('col-md-12')
                    $("#schedule").addClass('col-md-6');
                    $("#date").removeClass('d-none');
                } else {
                    $("#schedule").addClass('col-md-12')
                    $("#schedule").removeClass('col-md-6');
                    $("#date").addClass('d-none');
                }
            }

            toggleDateField();

            $('#campaign-form').on("submit", function(e) {
                e.preventDefault();
                let form = $(this);
                let url = form.attr('action');
                let data = form.serialize();
                $.post(url, data, function(response) {
                    notify(response.status, response.message);
                    $('#campaign-form').find('select').val('').trigger('change');
                    $('#campaign-form').trigger('reset');
                });
            });

            $(".date").flatpickr({
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                minDate: "today",
                defaultDate: new Date(),
                minuteIncrement: 10,
                allowInput: false,
                onClose: function(selectedDates, dateStr, instance) {
                    let date = selectedDates[0];
                    if (date) {
                        let minutes = date.getMinutes();
                        if (minutes % 10 !== 0) {
                            date.setMinutes(Math.round(minutes / 10) * 10);
                            instance.setDate(date, true);
                        }
                    }
                },
                onReady: function(selectedDates, dateStr, instance) {
                    instance._input.removeAttribute("readonly");
                }
            });

            $(document).on('mousedown', '.code-btn', function(e) {
                e.preventDefault();
                let code = $(this).data('code');
                let focusedInput = $('.dynamic-filed:focus');
                if (focusedInput.length) {
                    focusedInput.val(code);
                }
            });

            function generateParamsField() {
                let templateBody = $(this).find(':selected').data('template-body');
                let templateHeader = $(this).find(':selected').data('template-header');
                let templateHeaderText = templateHeader?.text ?? null;

                let totalBodyMatches = templateBody ? templateBody.match(/\{\{\d+\}\}/g) : [];
                let totalHeaderMatches = templateHeaderText ? templateHeaderText.match(/\{\{\d+\}\}/g) : [];

                if (totalHeaderMatches && totalHeaderMatches.length > 0) {
                    let html = ``;
                    $.each(totalHeaderMatches, function(index, value) {
                        html += `
                        <div class="form-group">
                            <label class="label-two">Variable ${value}</label>
                            <input type="text" data-name="${value}" name="header_variables[${value}]" class="form--control form-two dynamic-filed"  placeholder="Enter value for ${value}" required>
                        </div>`;
                    });
                    $('#template-header-variables').html(html);
                    $('.header-variable-area').removeClass('d-none');
                } else {
                    $('#template-header-variables').html('');
                    $('.header-variable-area').addClass('d-none');
                }

                $('#template-body-variables').empty();

                if (totalBodyMatches && totalBodyMatches.length > 0) {
                    let html = ``;
                    $.each(totalBodyMatches, function(index, value) {
                        html += `
                        <div class="form-group">
                            <label class="label-two">Variable ${value}</label>
                            <input type="text" data-name="${value}" name="body_variables[${value}]" class="form--control form-two dynamic-filed"  placeholder="Enter value for ${value}" required>
                        </div>`;
                    });
                    $('#template-body-variables').html(html);
                    $('.body-variable-area').removeClass('d-none');
                } else {
                    $('#template-body-variables').html('');
                    $('.body-variable-area').addClass('d-none');
                }
            }

            $('select[name=template_id]').on('change', function() {
                generateParamsField.call(this);
                showTemplatePreview.call(this);
                showTemplatePreview.call(this);

            });

            $('select[name=whatsapp_account_id]').on('change', function() {
                let $this = $(this);
                let getWhatsappAccountId = $this.val();
                getWhatsappAccountTemplates(getWhatsappAccountId);
            });

            let route = "{{ route('user.template.get', ':id') }}";
            

            function getWhatsappAccountTemplates() {
                let id = $('select[name=whatsapp_account_id]').val();
                $.ajax({
                    url: route.replace(':id', id),
                    type: "POST",
                    data: {
                        whatsapp_account_id: id,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.status != 'success') {
                            notify('error', response.message);
                            return;
                        }
                        $('select[name=template_id]').empty();
                        $('select[name=template_id]').append(
                            `<option value="" disabled selected>@lang('Select template')</option>`);
                        let templates = response.data.templates ?? [];
                        let headerBasePath = "{{ asset(getFilePath('templateHeader')) }}";
                        $.each(templates, function(index, template) {
                            const mediaUrl = `${headerBasePath}/${template.header_media}`;
                            $('select[name=template_id]').append(`
                            <option value="${template.id}" data-template-header='${JSON?.stringify(template.header)}' data-template-body="${template.body}" data-template-footer="${template.footer}" data-header-format="${template.header_format}" data-header-media="${mediaUrl}">
                                ${template.name}
                            </option>`);
                        });
                        $('select[name=template_id]').trigger('change');
                    }
                });
            }

            function showTemplatePreview() {
                const $selected = $('select[name=template_id] :selected');

                const templateBody = $selected.data('template-body') ?? "@lang('Template body')";
                const footer = $selected.data('template-footer') ?? "@lang('Template footer')";
                const templateHeaderText = $selected.data('template-header')?.text ?? "@lang('Template header')";
                const headerMediaPath = $selected.data('header-media');
                const headerFormat = $selected.data('header-format');

                $('.body_text').text(templateBody);
                $('.footer_text').text(footer);
                const $headerMedia = $('.header_media').empty();
                const $headerText = $('.header_text');

                if (headerFormat === 'IMAGE' && headerMediaPath) {
                    $headerText.text('');
                    $headerMedia.html(`<img src="${headerMediaPath}" alt="Template header">`);
                } else if (headerFormat === 'VIDEO' && headerMediaPath) {
                    $headerText.text('');
                    $headerMedia.html(`
                    <video controls>
                        <source src="${headerMediaPath}" type="video/mp4">
                    </video>
                    `);
                } else if (headerFormat === 'DOCUMENT' && headerMediaPath) {
                    $headerText.text('');
                    $headerMedia.html(`
                    <embed class="pdf-embed" src="${headerMediaPath}" type="application/pdf" width="100%" height="200px" style="border: none">
                    `);
                } else {
                    $headerMedia.empty();
                    $headerText.text(templateHeaderText);
                }
            }


        })(jQuery);
    </script>
@endpush


@push('style')
    <style>
        .datepickers-container {
            z-index: 9999999999;
        }



        .divider-title::after {
            position: absolute;
            content: '';
            top: 14px;
            left: 0px;
            background: #6b6b6b65;
            height: 2px;
            width: 100%;
        }

        .divider-title .text {
            background: hsl(var(--white));
            position: relative;
            z-index: 1;
            padding: 0 10px;
        }

        .information-main-form {
            width: calc(100% - 500px);
        }

        .information-wrapper {
            display: flex;
            gap: 16px;
        }
        @media screen and (max-width: 1199px) {
            .information-wrapper {
                flex-direction: column;
            }
            .information-main-form {
                width: 100%;
            }
        }
    </style>
@endpush
