<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\PlanPurchase;
use App\Models\PricingPlan;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchasePlanController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'pricing_plan_id'         => 'required',
            'plan_recurring'          => ['required', Rule::in([Status::MONTHLY, Status::YEARLY])],
            'purchase_payment_option' => ['required', Rule::in([Status::GATEWAY_PAYMENT, Status::WALLET_PAYMENT])],
        ]);

        $user        = auth()->user();
        $pricingPlan = PricingPlan::active()->find($request->pricing_plan_id);

        if (!$pricingPlan) {
            $notify[] = ['error', 'The pricing plan is not found'];
            return back()->withNotify($notify);
        }

        $purchasePrice  = getPlanPurchasePrice($pricingPlan, $request->plan_recurring);

        if ($purchasePrice <= 0) {
            if (PlanPurchase::where('user_id', $user->id)->where('amount', "<=", $purchasePrice)->count()) {
                $notify[] = ['error', 'You cannot subscribe to the free plan more than once.'];
                return back()->withNotify($notify);
            }

            $this->updateUserSubscription($user, $pricingPlan, $request->plan_recurring);

            $notify[] = ['success', 'Plan purchased successfully.'];
            return to_route('user.subscription.index', ['tab' => 'current-plan'])->withNotify($notify);
        }

        if ($request->purchase_payment_option == Status::GATEWAY_PAYMENT) {
            $pricingPlan->recurring_type = $request->plan_recurring;
            session()->put('pricing_plan', $pricingPlan);
            return to_route('user.deposit.index');
        }

        if ($user->balance < $purchasePrice) {
            $notify[] = ['error', 'Insufficient balance.'];
            return back()->withNotify($notify);
        }

        $this->updateUserSubscription($user, $pricingPlan, $request->plan_recurring);

        $notify[] = ['success', 'Plan purchased successfully.'];
        return to_route('user.subscription.index', ['tab' => 'current-plan'])->withNotify($notify);
    }

    public static function updateUserSubscription($user, $pricingPlan, $recurringType, $method = Status::WALLET_PAYMENT, $methodCode = 0)
    {
        $purchasePrice = getPlanPurchasePrice($pricingPlan, $recurringType);
        $now           = $user->plan_expired_at ? Carbon::parse($user->plan_expired_at) : Carbon::now();
        $expireAt      = null;

        if ($recurringType == Status::YEARLY) {
            $expireAt = $now->addYear();
        } else {
            $expireAt = $now->addMonth();
        }

        $purchase                      = new PlanPurchase();
        $purchase->user_id             = $user->id;
        $purchase->plan_id             = $pricingPlan->id;
        $purchase->recurring_type      = $recurringType;
        $purchase->amount              = $purchasePrice;
        $purchase->payment_method      = $method;
        $purchase->gateway_method_code = $methodCode;
        $purchase->expired_at          = $expireAt;
        $purchase->save();

        $amount = getAmount($purchasePrice);

        $user->balance          -= $amount;
        $user->plan_id          = $pricingPlan->id;
        $user->account_limit    = $pricingPlan->account_limit    == -1 ? -1 : $user->account_limit    + $pricingPlan->account_limit;
        $user->agent_limit      = $pricingPlan->agent_limit      == -1 ? -1 : $user->agent_limit      + $pricingPlan->agent_limit;
        $user->contact_limit    = $pricingPlan->contact_limit    == -1 ? -1 : $user->contact_limit    + $pricingPlan->contact_limit;
        $user->template_limit   = $pricingPlan->template_limit   == -1 ? -1 : $user->template_limit   + $pricingPlan->template_limit;
        $user->chatbot_limit    = $pricingPlan->chatbot_limit    == -1 ? -1 : $user->chatbot_limit    + $pricingPlan->chatbot_limit;
        $user->campaign_limit   = $pricingPlan->campaign_limit   == -1 ? -1 : $user->campaign_limit   + $pricingPlan->campaign_limit;
        $user->short_link_limit = $pricingPlan->short_link_limit == -1 ? -1 : $user->short_link_limit + $pricingPlan->short_link_limit;
        $user->floater_limit    = $pricingPlan->floater_limit    == -1 ? -1 : $user->floater_limit    + $pricingPlan->floater_limit;
        $user->welcome_message  = $pricingPlan->welcome_message;
        $user->plan_expired_at  = $expireAt;
        $user->save();

        // Transaction
        if ($amount > 0) {
            $transaction               = new Transaction();
            $transaction->trx          = getTrx();
            $transaction->user_id      = $user->id;
            $transaction->amount       = $amount;
            $transaction->post_balance = $user->balance;
            $transaction->charge       = 0;
            $transaction->trx_type     = '-';
            $transaction->details      = 'Purchase plan: ' . $pricingPlan->name;
            $transaction->remark       = 'plan_purchase';
            $transaction->save();

            notify($user, "SUBSCRIPTION_PAYMENT", [
                'trx'          => $transaction->trx,
                'plan_name'    => $pricingPlan->name,
                'duration'     => showDateTime($expireAt),
                'amount'       => showAmount($transaction->amount, currencyFormat: false),
                'next_billing' => showDateTime($expireAt, 'd M Y'),
                'post_balance' => showAmount($user->balance, currencyFormat: false),
                'remark'       => $transaction->remark
            ]);

            $userTotalPurchaseCount = PlanPurchase::where('user_id', $user->id)->count();
            if ($user->ref_by && $userTotalPurchaseCount <= 1) {
                userReferralCommission($user, $purchasePrice);
            }
        }
    }
}
