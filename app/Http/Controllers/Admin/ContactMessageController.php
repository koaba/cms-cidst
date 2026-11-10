<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactAttachment;
use App\Models\ContactMessage;
use App\Services\Contact\ContactImageStore;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactMessageController extends Controller
{
    public function index()
    {
        return view('admin.messages.index', ['messages' => ContactMessage::withCount('attachments')->latest()->paginate(20)]);
    }

    public function show(ContactMessage $message)
    {
        if (! $message->read_at) {
            $message->forceFill(['read_at' => now()])->save();
        }

        $message->load('attachments');

        return view('admin.messages.show', compact('message'));
    }

    public function attachment(ContactMessage $message, ContactAttachment $attachment): StreamedResponse
    {
        return $this->image($message, $attachment, HeaderUtils::DISPOSITION_ATTACHMENT);
    }

    public function preview(ContactMessage $message, ContactAttachment $attachment): StreamedResponse
    {
        return $this->image($message, $attachment, HeaderUtils::DISPOSITION_INLINE);
    }

    /**
     * Sert une image deja reencodee, avec son type d'origine : en pieces jointes (telechargement) ou en ligne (apercu).
     */
    private function image(ContactMessage $message, ContactAttachment $attachment, string $disposition): StreamedResponse
    {
        abort_unless($attachment->contact_message_id === $message->id, 404);

        $disk = Storage::disk(ContactImageStore::DISK);
        abort_unless($disk->exists($attachment->path), 404);

        $name = 'image-'.$attachment->id.'.'.pathinfo($attachment->path, PATHINFO_EXTENSION);

        return response()->stream(function () use ($disk, $attachment) {
            $stream = $disk->readStream($attachment->path);

            if (is_resource($stream)) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $attachment->mime,
            'Content-Disposition' => HeaderUtils::makeDisposition($disposition, $name),
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
