<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentApprovalLog;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Resolves the approval target(s) for a document using a dynamic fallback chain:
 *   Head → Admin → Direktur
 *
 * The service checks the document's PT context (company_id / branch_id) to
 * find an active user with the right role. If no Head is found, it falls
 * back to Admin; if no Admin either, it falls back to Direktur.
 *
 * Every evaluation step is logged in `document_approval_logs` for auditability.
 */
class ApprovalRoutingService
{
    /**
     * Result DTO returned by resolveApprover().
     *
     * @property Collection<int, User> $approvers  One or more resolved approvers.
     * @property string                $role       The role that was resolved (head / admin / direktur).
     * @property string                $message    Human-readable notification text for the requester.
     * @property bool                  $isFallback Whether the result is a fallback (not the primary Head).
     */
    public readonly Collection $approvers;
    public readonly ?string $role;
    public readonly string $message;
    public readonly bool $isFallback;

    // ───────────────────────────────────────────────────
    // Public API
    // ───────────────────────────────────────────────────

    /**
     * Resolve approver(s) for the given document.
     *
     * @return array{approvers: Collection<int, User>, role: ?string, message: string, isFallback: bool}
     */
    public function resolveApprover(Document $document, ?User $excludeUser = null): array
    {
        $document->loadMissing('company', 'branch.company');

        $ptName = $this->getPtName($document);

        // ── Step 1: Try Head ──
        $heads = $this->findActiveUsers($document, 'head', $excludeUser);
        $this->logEvaluation($document, 'head', $heads);

        if ($heads->isNotEmpty()) {
            $approverNames = $heads->pluck('name')->join(', ');
            return $this->result(
                $heads,
                'head',
                __(':approver (Head) akan mereview dokumen Anda.', ['approver' => $approverNames]),
                false,
            );
        }

        // ── Step 2: Fallback to Admin ──
        $admins = $this->findActiveUsers($document, 'admin', $excludeUser);
        $this->logEvaluation($document, 'admin', $admins);

        if ($admins->isNotEmpty()) {
            $approverNames = $admins->pluck('name')->join(', ');
            return $this->result(
                $admins,
                'admin',
                __('Tidak ada Head di :pt, dokumen akan di-review oleh :approver (Admin).', [
                    'pt' => $ptName,
                    'approver' => $approverNames,
                ]),
                true,
            );
        }

        // ── Step 3: Fallback to Direktur ──
        $direkturs = $this->findActiveUsers($document, 'direktur', $excludeUser);
        $this->logEvaluation($document, 'direktur', $direkturs);

        if ($direkturs->isNotEmpty()) {
            $approverNames = $direkturs->pluck('name')->join(', ');
            return $this->result(
                $direkturs,
                'direktur',
                __('Tidak ada Head/Admin di :pt, dokumen akan di-review oleh :approver (Direktur).', [
                    'pt' => $ptName,
                    'approver' => $approverNames,
                ]),
                true,
            );
        }

        // ── No approver found ──
        $this->logEvaluation($document, 'none', collect());

        return $this->result(
            collect(),
            null,
            __('Approver tidak ditemukan di :pt. Silakan hubungi Super Admin.', ['pt' => $ptName]),
            true,
        );
    }

    /**
     * After resolveApprover(), persist the chosen approver on the document.
     */
    public function applyToDocument(Document $document, array $resolution): void
    {
        $firstApprover = $resolution['approvers']->first();

        $document->update([
            'approver_id' => $firstApprover?->id,
            'approver_role' => $resolution['role'],
        ]);
    }

    // ───────────────────────────────────────────────────
    // Query helpers
    // ───────────────────────────────────────────────────

    /**
     * Find active users with the given role in the same PT context as the document.
     *
     * For Head: match on division + branch/company (heads are division-scoped).
     * For Admin/Direktur: match on company (they are company-scoped).
     */
    private function findActiveUsers(Document $document, string $role, ?User $excludeUser = null): Collection
    {
        $query = User::where('system_role', $role)
            ->where('is_active', true);

        if ($excludeUser) {
            $query->where('id', '!=', $excludeUser->id);
        }

        if ($role === 'head') {
            // Head harus di divisi yang sama DAN di branch/company yang sama
            if ($document->division_id) {
                $query->where(function ($q) use ($document) {
                    // Primary division match
                    $q->where('division_id', $document->division_id)
                      // or via pivot table (multi-division heads)
                      ->orWhereHas('divisions', fn($dq) => $dq->where('divisions.id', $document->division_id));
                });
            } else {
                // No division on doc → no head can match
                return collect();
            }

            // Scope to same branch/company
            if ($document->branch_id) {
                $query->whereHas('branches', fn($bq) => $bq->where('branches.id', $document->branch_id));
            } elseif ($document->company_id) {
                $query->whereHas('companies', fn($cq) => $cq->where('companies.id', $document->company_id));
            }
        } else {
            // Admin is global, Direktur is scoped by company
            if ($role === 'direktur') {
                $companyId = $document->company_id ?? $document->branch?->company_id;

                if ($companyId) {
                    $query->whereHas('companies', fn($cq) => $cq->where('companies.id', $companyId));
                }

                // Also scope by branch if document has one (more precise matching)
                if ($document->branch_id) {
                    // Direktur may be branch-scoped
                    $query->where(function ($q) use ($document) {
                        $q->whereHas('branches', fn($bq) => $bq->where('branches.id', $document->branch_id))
                          // Or company-wide (no specific branch assigned but has the company)
                          ->orWhereDoesntHave('branches');
                    });
                }
            }
        }

        return $query->get();
    }

    // ───────────────────────────────────────────────────
    // Audit logging
    // ───────────────────────────────────────────────────

    private function logEvaluation(Document $document, string $role, Collection $users): void
    {
        $firstUser = $users->first();

        DocumentApprovalLog::create([
            'document_id' => $document->id,
            'evaluated_role' => $role,
            'result' => $users->isNotEmpty() ? 'found' : 'not_found',
            'resolved_user_id' => $firstUser?->id,
            'resolved_user_name' => $firstUser?->name,
            'notes' => $users->count() > 1
                ? __(':count pengguna ditemukan: :names', [
                    'count' => $users->count(),
                    'names' => $users->pluck('name')->join(', '),
                ])
                : null,
        ]);
    }

    // ───────────────────────────────────────────────────
    // Helpers
    // ───────────────────────────────────────────────────

    private function result(Collection $approvers, ?string $role, string $message, bool $isFallback): array
    {
        return [
            'approvers' => $approvers,
            'role' => $role,
            'message' => $message,
            'isFallback' => $isFallback,
        ];
    }

    private function getPtName(Document $document): string
    {
        if ($document->branch) {
            return $document->branch->name . ' (' . ($document->branch->company?->name ?? '') . ')';
        }

        return $document->company?->name ?? __('PT tidak diketahui');
    }
}
