<?php

namespace App\Modules\Forms;

use App\Models\Client;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class SubmitController
{
    public function store(Request $request, string $client, int $section)
    {
        $client = Client::where('slug', $client)->where('status', 'active')->firstOrFail();
        // The form only works while its section type is allowed for this client (same rule as the public page).
        abort_unless(isset($client->allowedSectionTypes()['contact_form']), 404);

        $section = Section::where('client_id', $client->id)
            ->where('type', 'contact_form')->where('is_enabled', true)->findOrFail($section);

        $back = $client->publicUrl() . '?sent=' . $section->id . '#' . ($section->anchor ?: 'contact');

        // Honeypot: real visitors never see or fill this field. Bots do; pretend it worked.
        if ($request->filled('website')) {
            return redirect($back);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:190'],
            'phone' => ['nullable', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:3000'],
        ]);
        $validator->after(function ($v) use ($request) {
            if (! $request->filled('email') && ! $request->filled('phone')) {
                $v->errors()->add('email', 'Please leave an email address or a phone number.');
            }
        });

        if ($validator->fails()) {
            return response()->view('forms::invalid', ['errors' => $validator->errors()->all(), 'back' => $client->publicUrl()], 422);
        }

        // 1. The lead is stored first: whatever happens to the email, it can never be lost.
        $lead = Submission::create([
            'client_id' => $client->id,
            'section_id' => $section->id,
            'payload' => $validator->validated(),
        ]);

        // 2. The alert goes out after the visitor already has their response, and a mail failure is
        //    only logged — it must never turn a stored lead into an error page.
        if ($recipients = $client->ownerEmails()) {
            app()->terminating(function () use ($recipients, $client, $lead) {
                try {
                    Mail::to($recipients)->send(new NewLeadMail($client, $lead));
                } catch (\Throwable $e) {
                    report($e);
                }
            });
        }

        return redirect($back);
    }
}
