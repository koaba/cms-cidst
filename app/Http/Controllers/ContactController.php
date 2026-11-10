<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use App\Mail\ContactAlert;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Services\Contact\ContactImageStore;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    public function show()
    {
        $settings = SiteSetting::current();

        return view('public.contact', [
            'imagesEnabled' => $settings->contact_images_enabled,
            'imagesMaxMb' => max(1, (int) $settings->contact_images_max_mb),
            'imagesMaxFiles' => (int) config('contact.max_files'),
        ]);
    }

    public function store(ContactMessageRequest $request)
    {
        if (filled($request->input('website'))) {
            return redirect()->route('contact.show')->with('success', true);
        }

        $message = new ContactMessage($request->safe()->only(['name', 'email', 'subject', 'message', 'attachment_link']));
        $message->ip_hash = hash_hmac('sha256', (string) $request->ip(), (string) config('app.key'));
        $message->save();

        // Reencodage apres la reponse : le visiteur n'attend pas GD.
        $images = collect($request->file('images', []))
            ->map(fn ($file) => ['path' => $file->getRealPath(), 'name' => $file->getClientOriginalName()])
            ->filter(fn ($image) => $image['path'] !== false)
            ->values()
            ->all();

        if ($images !== []) {
            defer(function () use ($message, $images) {
                $store = app(ContactImageStore::class);

                foreach ($images as $image) {
                    $store->store($image['path'], $image['name'], $message);
                }
            });
        }

        // Envoi apres la reponse : un SMTP lent ne fait pas attendre le visiteur.
        // defer() isole l'echec (signale, jamais montre au visiteur) ; alerted_at reste alors vide.
        defer(function () use ($message) {
            Mail::to(config('mail.from.address'))->send(new ContactAlert);
            $message->forceFill(['alerted_at' => now()])->save();
        });

        return redirect()->route('contact.show')->with('success', true);
    }
}
