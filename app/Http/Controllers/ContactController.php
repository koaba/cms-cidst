<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use App\Mail\ContactAlert;
use App\Models\ContactMessage;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ContactController extends Controller
{
    public function show()
    {
        return view('public.contact');
    }

    public function store(ContactMessageRequest $request)
    {
        if (filled($request->input('website'))) {
            return redirect()->route('contact.show')->with('success', true);
        }

        $message = new ContactMessage($request->safe()->only(['name', 'email', 'subject', 'message']));
        $message->ip_hash = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        $message->save();

        try {
            Mail::to(config('mail.from.address'))->send(new ContactAlert);
            $message->forceFill(['alerted_at' => now()])->save();
        } catch (Throwable $e) {
            report($e);
        }

        return redirect()->route('contact.show')->with('success', true);
    }
}
