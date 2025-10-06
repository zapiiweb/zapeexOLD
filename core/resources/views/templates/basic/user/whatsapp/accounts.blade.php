@extends($activeTemplate . 'layouts.master')
@section('content')
    <div class="dashboard-container">
        <div class="container-top">
            <div class="container-top__left">
                <h5 class="container-top__title">{{ __(@$pageTitle) }}</h5>
                <p class="container-top__desc">@lang('Connect and configure multiple WhatsApp Business accounts from here')</p>
            </div>
            <div class="container-top__right">
                <div class="btn--group">
                    <a href="{{ route('user.whatsapp.account.add') }}" class="btn btn--base btn-shadow">
                        <i class="las la-plus"></i>
                        @lang('Add New')
                    </a>
                </div>
            </div>
        </div>
        <div class="dashboard-container__body">
            <div class="dashboard-table">
                <div class="dashboard-table__top">
                    <h5 class="dashboard-table__title mb-0">@lang('All Accounts')</h5>
                </div>
                <table class="table table--responsive--lg">
                    <thead>
                        <tr>
                            <th>@lang('Whatsapp Business Name')</th>
                            <th>@lang('Whatsapp Business Number')</th>
                            <th>@lang('Verification Status')</th>
                            <th>@lang('Is Default Account')</th>
                            <th>@lang('Action')</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($whatsappAccounts as $whatsappAccount)
                            <tr>
                                <td>{{ __(@$whatsappAccount->business_name) }}</td>
                                <td>{{ @$whatsappAccount->phone_number }}</td>
                                <td>
                                    <div>
                                        @php echo $whatsappAccount->verificationStatusBadge; @endphp
                                        <a title="@lang('Get the current verification status of your whatsapp business account from Meta API')"
                                            href="{{ route('user.whatsapp.account.verification.check', $whatsappAccount->id) }}">
                                            <i class="las la-redo-alt"></i>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    @if ($whatsappAccount->is_default)
                                        <span class="badge badge--success">@lang('Yes')</span>
                                    @else
                                        <span class="badge badge--danger">@lang('No')</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="action-btn">
                                        <button class="action-btn__icon p-1">
                                            <i class="fa-solid fa-ellipsis-vertical"></i>
                                        </button>
                                        <ul class="action-dropdown">
                                            @if (!$whatsappAccount->is_default)
                                                <li class="action-dropdown__item">
                                                    <a class="action-dropdown__link"
                                                        href="{{ route('user.whatsapp.account.connect', $whatsappAccount->id) }}">
                                                        <span class="text"><i class="las la-check-circle"></i>
                                                            @lang('Make Default Account')
                                                        </span>
                                                    </a>
                                                </li>
                                            @endif
                                            <li class="action-dropdown__item">
                                                <a class="action-dropdown__link"
                                                    href="{{ route('user.whatsapp.account.setting', $whatsappAccount->id) }}">
                                                    <span class="text">
                                                        <i class="las la-cog"></i>
                                                        @lang('Change Token')
                                                    </span>
                                                </a>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            @include('Template::partials.empty_message')
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ paginateLinks($whatsappAccounts) }}
        </div>
    </div>
@endsection

@push('topbar_tabs')
    @include('Template::partials.profile_tab')
@endpush
