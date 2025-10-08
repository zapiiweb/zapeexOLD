@php
    $whatsappAccounts = App\Models\WhatsappAccount::where('user_id', getParentUser()->id)->get();
@endphp

@props(['isHide' => false])

@if (!$isHide || $whatsappAccounts->count() > 1)
    <select class="form--control select2 form-two" required name="whatsapp_account_id">
        @foreach ($whatsappAccounts as $whatsappAccount)
            <option value="{{ $whatsappAccount->id }}" @selected($whatsappAccount->id == old('whatsapp_account_id', request()->whatsapp_account_id ?? $whatsappAccount->is_default))>
                {{ __($whatsappAccount->business_name) }} ({{ $whatsappAccount->phone_number }})
            </option>
        @endforeach
    </select>
@endif
