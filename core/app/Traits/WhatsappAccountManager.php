<?php

namespace App\Traits;

use App\Constants\Status;
use App\Models\WhatsappAccount;
use Exception;
use Illuminate\Http\Request;

trait WhatsappAccountManager
{
    use WhatsappManager;

    public function whatsappAccounts()
    {
        $pageTitle             = "Manage WhatsApp Account";
        $view                  = 'Template::user.whatsapp.accounts';
        $whatsappAccountsQuery = WhatsappAccount::where('user_id', getParentUser()->id)->orderBy('is_default', 'desc');

        if (isApiRequest()) {
            $whatsappAccounts = $whatsappAccountsQuery->get();
        } else {
            $whatsappAccounts = $whatsappAccountsQuery->paginate(getPaginate(10));
        }
        
        return responseManager("whatsapp_accounts", $pageTitle, "success", [
            'pageTitle'        => $pageTitle,
            'view'             => $view,
            'whatsappAccounts' => $whatsappAccounts
        ]);
    }

    public function storeWhatsappAccount(Request $request)
    {
        $request->validate([
            'whatsapp_number'              => 'required',
            'whatsapp_business_account_id' => 'required',
            'phone_number_id'              => 'required',
            'meta_access_token'            => 'required',
            'meta_app_id'                  => 'required',
        ]);

        $user = getParentUser();

        if (!featureAccessLimitCheck($user->account_limit)) {
            $message = "You have reached the maximum limit of WhatsApp account. Please upgrade your plan.";
            return responseManager("whatsapp_error", $message, "error");
        }

        $accountExists = WhatsappAccount::where('phone_number_id', $request->phone_number_id)
            ->orWhere('whatsapp_business_account_id', $request->whatsapp_business_account_id)
            ->exists();

        if ($accountExists) {
            $message = 'This account already has been registered to our system';
            return responseManager("whatsapp_error", $message, "error");
        }

        try {
            $whatsappData = $this->verifyWhatsappCredentials($request->whatsapp_business_account_id, $request->meta_access_token);
        } catch (Exception $ex) {
            return responseManager("whatsapp_error", $ex->getMessage());
        }

        $whatsAccountData = $whatsappData['data'];

        if ($whatsAccountData['code_verification_status'] != 'APPROVED') {
            $notify[] = ['info', 'Your whatsapp business account is not approved. Please create a permanent access token.'];
            if (isApiRequest()) {
                $notify[] = 'Your whatsapp business account is not approved. Please create a permanent access token.';
            }
        }

        $whatsappAccount                               = new WhatsappAccount();
        $whatsappAccount->user_id                      = $user->id;
        $whatsappAccount->phone_number_id              = $whatsAccountData['id'];
        $whatsappAccount->phone_number                 = $request->whatsapp_number;
        $whatsappAccount->business_name                = $whatsAccountData['verified_name'];
        $whatsappAccount->access_token                 = $request->meta_access_token;
        $whatsappAccount->code_verification_status     = $whatsAccountData['code_verification_status'];
        $whatsappAccount->whatsapp_business_account_id = $request->whatsapp_business_account_id;
        $whatsappAccount->meta_app_id                  = $request->meta_app_id;
        $whatsappAccount->is_default                   = WhatsappAccount::where('user_id', $user->id)->count() ? Status::NO : Status::YES;
        $whatsappAccount->save();

        decrementFeature($user, 'account_limit');

        if (isApiRequest()) {
            $notify[] = "WhatsApp account added successfully";
            return apiResponse("whatsapp_success", "success", $notify, [
                'whatsappAccount' => $whatsappAccount
            ]);
        }

        $notify[] = ["success", "WhatsApp account added successfully"];
        return to_route('user.whatsapp.account.index')->withNotify($notify);
    }

    public function whatsappAccountVerificationCheck($accountId)
    {
        $user            = getParentUser();
        $whatsappAccount = WhatsappAccount::where('user_id', $user->id)->findOrFailWithApi("whatsapp account", $accountId);

        try {
            $whatsappData = $this->verifyWhatsappCredentials($whatsappAccount->whatsapp_business_account_id, $whatsappAccount->access_token);
        } catch (Exception $ex) {
            return responseManager("whatsapp_error", $ex->getMessage());
        }

        $whatsappAccount->code_verification_status = $whatsappData['data']['code_verification_status'];
        $whatsappAccount->save();

        $message = "WhatsApp account verification status updated successfully";
        return responseManager("verification_status", $message, "success");
    }

    public function whatsappAccountConnect($id)
    {
        $user                        = getParentUser();
        $whatsappAccount             = WhatsappAccount::where('user_id', $user->id)->findOrFailWithApi("whatsapp account", $id);
        $whatsappAccount->is_default = Status::YES;
        $whatsappAccount->save();

        WhatsappAccount::where('user_id', $user->id)->where('id', '!=', $whatsappAccount->id)->update(['is_default' => Status::NO]);

        $message = "WhatsApp account connected successfully";
        return responseManager("whatsapp_success", $message, "success");
    }

    public function whatsappAccountSettingConfirm(Request $request, $accountId)
    {
        $request->validate([
            'meta_access_token' => 'required',
        ]);

        $user            = getParentUser();
        $whatsappAccount = WhatsappAccount::where('user_id', $user->id)->findOrFailWithApi("whatsapp account", $accountId);

        try {
            $whatsappData = $this->verifyWhatsappCredentials($whatsappAccount->whatsapp_business_account_id, $request->meta_access_token);
        } catch (Exception $ex) {
            return responseManager("whatsapp_error", $ex->getMessage());
        }

        $whatsappAccount->access_token             = $request->meta_access_token;
        $whatsappAccount->code_verification_status = $whatsappData['data']['code_verification_status'];
        $whatsappAccount->save();

        $message = "WhatsApp account credentials updated successfully";
        return responseManager("whatsapp_success", $message, "success");
    }
}
