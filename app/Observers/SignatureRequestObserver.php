<?php

namespace App\Observers;

use App\Models\SignatureRequest;
use App\Notifications\SignatureRequested;

class SignatureRequestObserver
{
    /**
     * When a new signature request is created, notifications are deferred until
     * document editing is finished and saved via $signatureRequest->sendNotification().
     */
    public function created(SignatureRequest $signatureRequest): void
    {
        // Notifications are sent when document editing is saved and finished.
    }
}


