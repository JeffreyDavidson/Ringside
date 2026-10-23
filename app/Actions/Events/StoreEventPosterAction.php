<?php

declare(strict_types=1);

namespace App\Actions\Events;

use App\Exceptions\Events\EventPublicationException;
use App\Support\Images\ImageReencoder;
use App\Support\Images\ImageReencodingException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StoreEventPosterAction
{
    public function __construct(private readonly ImageReencoder $reencoder) {}

    /** Re-encode pixels into a fresh PNG; no original filename or binary metadata is retained. */
    public function handle(UploadedFile $file): string
    {
        try {
            $encoded = $this->reencoder->reencode($file->getContent());
        } catch (ImageReencodingException) {
            throw EventPublicationException::poster();
        }
        $key = Str::uuid().'.png';

        if (! Storage::disk('local')->put('event-posters/'.$key, $encoded)) {
            throw EventPublicationException::poster();
        }

        return $key;
    }
}
