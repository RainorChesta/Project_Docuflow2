<?php

namespace App\Observers;

use App\Models\SignatureRequest;
use App\Notifications\SignatureRequested;

class SignatureRequestObserver
{
    /**
     * When a new signature request is created, notify the target user.
     */
    public function created(SignatureRequest $signatureRequest): void
    {
        $signatureRequest->loadMissing(['document', 'requester']);

        $targetUser = $signatureRequest->targetUser;
        $document   = $signatureRequest->document;
        $requester  = $signatureRequest->requester;

        if ($targetUser && $document && $requester) {
            $targetUser->notify(new SignatureRequested($document, $requester->name));
        }
    }
}
