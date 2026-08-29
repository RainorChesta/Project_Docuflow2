<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Document;
use App\Models\DocumentDistribution;
use App\Models\User;
use App\Notifications\CrossBranchDocumentReceived;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DocumentDistributionController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
    ) {}

    public function store(Request $request, Document $document): RedirectResponse
    {
        $this->authorize('update', $document);

        $validated = $request->validate([
            'target_branch_ids' => 'required|array',
            'target_branch_ids.*' => 'exists:branches,id',
        ]);

        $user = auth()->user();
        $sourceBranchId = $document->branch_id ?? $user->allBranchIds()[0] ?? null;

        if (!$sourceBranchId) {
            return back()->with('error', 'Cannot determine source branch for distribution.');
        }

        foreach ($validated['target_branch_ids'] as $targetBranchId) {
            // Prevent duplicate distribution
            $exists = DocumentDistribution::where('document_id', $document->id)
                ->where('target_branch_id', $targetBranchId)
                ->exists();

            if (!$exists) {
                $distribution = DocumentDistribution::create([
                    'document_id' => $document->id,
                    'source_branch_id' => $sourceBranchId,
                    'target_branch_id' => $targetBranchId,
                    'status' => 'unread',
                    'sent_at' => now(),
                    'created_by' => $user->id,
                ]);

                $this->auditService->log($user, 'document.distributed', 'document_distribution', $distribution->id, [
                    'document_id' => $document->id,
                    'target_branch_id' => $targetBranchId,
                ]);

                // Notify target branch users
                $branch = Branch::find($targetBranchId);
                if ($branch) {
                    $targetUsers = User::whereHas('branches', fn($q) => $q->where('branches.id', $targetBranchId))
                        ->where('is_active', true)
                        ->get();

                    foreach ($targetUsers as $targetUser) {
                        $targetUser->notify(new \App\Notifications\CrossBranchDocumentReceived($document, $branch->name, $user->name));
                    }
                }
            }
        }

        return back()->with('success', 'Document distributed successfully.');
    }
}
