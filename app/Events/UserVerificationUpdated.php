<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserVerificationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public User $user)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->user->id),
            new PrivateChannel('notifications.' . $this->user->id),
        ];
    }

    /**
     * Broadcast event name.
     */
    public function broadcastAs(): string
    {
        return 'user.verification.updated';
    }

    /**
     * Data to broadcast with the event.
     */
    public function broadcastWith(): array
    {
        $this->user->load(['unitKerjas', 'companies', 'branches']);

        $hasUnitKerja = !empty($this->user->unit_kerja_id) || $this->user->unitKerjas->isNotEmpty();
        $hasCompany = $this->user->companies->isNotEmpty();
        $hasBranch = $this->user->branches->isNotEmpty();
        $isVerified = $this->user->isVerified();

        return [
            'user_id' => $this->user->id,
            'is_verified' => $isVerified,
            'has_unit_kerja' => $hasUnitKerja,
            'has_division' => $hasUnitKerja,
            'has_company' => $hasCompany,
            'has_branch' => $hasBranch,
            'has_company_and_branch' => ($hasCompany && $hasBranch),
        ];
    }
}
