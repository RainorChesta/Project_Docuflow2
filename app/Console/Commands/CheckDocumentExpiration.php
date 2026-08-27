<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:check-document-expiration')]
#[Description('Mark documents as expired if they pass their expiration date')]
class CheckDocumentExpiration extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredCount = 0;
        
        \App\Models\User::chunk(50, function ($users) use (&$expiredCount) {
            foreach ($users as $user) {
                $docs = $user->documents()->where('is_expired', false)->get();
                
                $in30Days = 0;
                $in7Days = 0;
                
                foreach ($docs as $document) {
                    if (!$document->expires_at) continue;
                    
                    $days = now()->startOfDay()->diffInDays($document->expires_at->startOfDay(), false);
                    
                    if ($days < 0) {
                        $document->update(['is_expired' => true]);
                        $document->delete();
                        $expiredCount++;
                    } elseif ($days <= 1) {
                        if ($document->expiration_notif_status !== 'urgent') {
                            $user->notify(new \App\Notifications\UrgentDocumentExpiring($document, $days));
                            $document->update(['expiration_notif_status' => 'urgent']);
                        }
                    } elseif ($days <= 7) {
                        if ($document->expiration_notif_status !== '7days') {
                            $user->notify(new \App\Notifications\WarningDocumentExpiring($document, $days));
                            $document->update(['expiration_notif_status' => '7days']);
                        }
                    } elseif ($days <= 30) {
                        if ($document->expiration_notif_status !== '30days') {
                            $in30Days++;
                            $document->update(['expiration_notif_status' => '30days']);
                        }
                    }
                }
                
                if ($in30Days > 0 || $in7Days > 0) {
                    $user->notify(new \App\Notifications\GroupedDocumentExpiring($in30Days, $in7Days));
                }
            }
        });
        
        $this->info("Processed expiration checks. Marked {$expiredCount} documents as expired.");
    }
}
