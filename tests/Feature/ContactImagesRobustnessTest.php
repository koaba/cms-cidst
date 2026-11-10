<?php

use App\Mail\ContactAlert;
use App\Models\ContactAttachment;
use App\Models\ContactMessage;
use App\Models\SiteSetting;
use App\Services\Contact\ContactImageStore;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

function robustPayload(array $o = []): array
{
    return array_merge(['name' => 'Jean', 'email' => 'jean@example.com', 'subject' => 'Photo', 'message' => 'Un message assez long.', 'website' => ''], $o);
}

function robustImagesOn(): void
{
    SiteSetting::current()->update(['contact_images_enabled' => true, 'contact_images_max_mb' => 5]);
}

function robustStoreWithMemory(int $bytes): ContactImageStore
{
    return new class($bytes) extends ContactImageStore
    {
        public function __construct(private int $bytes) {}

        protected function availableMemory(): int
        {
            return $this->bytes;
        }
    };
}

function robustMessage(): ContactMessage
{
    return ContactMessage::create(['name' => 'Jean', 'email' => 'jean@example.com', 'message' => 'Un message assez long.']);
}

it('envoie l alerte avant de traiter les images', function () {
    Storage::fake('local');
    Mail::fake();
    robustImagesOn();

    $calls = new ArrayObject;
    app()->instance(ContactImageStore::class, new class($calls) extends ContactImageStore
    {
        public function __construct(private ArrayObject $calls) {}

        public function store(string $path, string $originalName, ContactMessage $message): ?ContactAttachment
        {
            $this->calls[] = Mail::sent(ContactAlert::class)->count();

            return null;
        }
    });

    $this->post('/contact', robustPayload(['images' => [UploadedFile::fake()->image('a.jpg', 50, 50)]]))
        ->assertRedirect()->assertSessionHasNoErrors();

    expect($calls->getArrayCopy())->toBe([1]);
});

it('garde le message et l alerte quand une image est trop lourde pour la memoire', function () {
    Storage::fake('local');
    Mail::fake();
    Log::spy();
    robustImagesOn();
    app()->instance(ContactImageStore::class, robustStoreWithMemory(1_000_000));

    $this->post('/contact', robustPayload(['images' => [UploadedFile::fake()->image('grande.jpg', 600, 600)]]))
        ->assertRedirect()->assertSessionHasNoErrors();

    expect(ContactMessage::count())->toBe(1)
        ->and(ContactAttachment::count())->toBe(0)
        ->and(Storage::disk('local')->allFiles())->toBe([]);
    Mail::assertSent(ContactAlert::class, 1);
    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $msg, array $ctx = []) => $msg === 'contact.image_discarded' && ($ctx['reason'] ?? null) === 'not_enough_memory')
        ->once();
});

it('range une image quand la memoire disponible suffit', function () {
    Storage::fake('local');
    $message = robustMessage();
    $file = UploadedFile::fake()->image('a.jpg', 100, 100);

    $attachment = robustStoreWithMemory(500_000_000)->store($file->getPathname(), 'a.jpg', $message);

    expect($attachment)->not->toBeNull();
    Storage::disk('local')->assertExists($attachment->path);
});

it('ecarte un fichier qui n est pas une image saine et le journalise', function () {
    Storage::fake('local');
    Log::spy();
    $message = robustMessage();
    $path = tempnam(sys_get_temp_dir(), 'rob');
    file_put_contents($path, '<?php echo 1;');

    $attachment = robustStoreWithMemory(500_000_000)->store($path, 'x.jpg', $message);
    @unlink($path);

    expect($attachment)->toBeNull()->and(ContactAttachment::count())->toBe(0);
    Log::shouldHaveReceived('warning')
        ->withArgs(fn (string $msg, array $ctx = []) => $msg === 'contact.image_discarded' && ($ctx['reason'] ?? null) === 'not_a_safe_image' && ($ctx['contact_message_id'] ?? null) === $message->id)
        ->once();
});

it('limite le cote des images a 3000 pixels', function () {
    Storage::fake('local');
    Mail::fake();
    robustImagesOn();

    $this->post('/contact', robustPayload(['images' => [UploadedFile::fake()->image('trop.jpg', 3001, 10)]]))
        ->assertSessionHasErrors('images.0');

    $this->post('/contact', robustPayload(['images' => [UploadedFile::fake()->image('limite.jpg', 3000, 10)]]))
        ->assertRedirect()->assertSessionHasNoErrors();
});
