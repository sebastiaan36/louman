<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\SupportQuestionRequest;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Notifications\CustomerQuestion;
use App\Support\AdminNotifier;
use Illuminate\Http\RedirectResponse;

class SupportController extends Controller
{
    /**
     * Pass a question from the help button on to whoever answers them.
     */
    public function store(SupportQuestionRequest $request): RedirectResponse
    {
        $customer = $request->user()->customer;
        $question = $request->validated('question');
        $replyTo = $request->validated('email');

        AdminNotifier::send(
            new CustomerQuestion($customer, $question, $replyTo),
            Setting::MAIL_SUPPORT_NOTIFICATION,
        );

        AuditLog::record('customer.question', "Vraag gesteld door {$customer->company_name}", $customer);

        return back()->with('success', 'Bedankt voor uw vraag. We nemen zo snel mogelijk contact met u op.');
    }
}
