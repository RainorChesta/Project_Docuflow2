<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    private function filterNotifications($user, $notifications)
    {
        if ($user->isAdmin()) {
            return $notifications;
        }

        $contextService = app(\App\Services\CompanyContextService::class);
        $activeBranchId = $contextService->getActiveBranchId($user);
        $activeCompanyId = $contextService->getActiveCompanyId($user);

        $docIds = [];
        foreach ($notifications as $n) {
            $docId = $n->data['document_id'] ?? null;
            if (!$docId) {
                $url = $n->data['url'] ?? '';
                if (preg_match('/\/documents\/([0-9a-f\-]{36}|[0-9]+)/', $url, $matches)) {
                    $docId = $matches[1];
                }
            }
            if ($docId) {
                $docIds[$n->id] = $docId;
            }
        }

        $documents = !empty($docIds)
            ? \App\Models\Document::with('branch', 'distributions.targetBranch')->whereIn('id', array_unique(array_values($docIds)))->get()->keyBy('id')
            : collect();

        return $notifications->filter(function ($n) use ($user, $docIds, $documents, $activeBranchId, $activeCompanyId) {
            if (!isset($docIds[$n->id])) {
                $type = $n->data['type'] ?? '';
                if (in_array($type, [
                    'approval_request', 
                    'approval_result', 
                    'document_shared', 
                    'document_added_division', 
                    'signature_request', 
                    'signature_request_approved',
                    'signature_request_rejected',
                    'rollback_request',
                    'rollback_approved',
                    'rollback_rejected',
                    'document_expiring_urgent',
                    'document_expiring_warning',
                    'document_opened',
                    'grouped_document_expiring',
                ])) {
                    return false; // Hide legacy/unresolved document notifications to prevent leaking
                }
                return true;
            }

            $doc = $documents->get($docIds[$n->id]);
            if (!$doc) {
                return false;
            }

            $type = $n->data['type'] ?? '';
            if ($type !== 'document_access_revoked' && !$user->can('view', $doc)) {
                return false;
            }
            
            // Scope notifications to the selected branch and company:
            if ($activeBranchId) {
                if ($doc->branch_id && (int)$doc->branch_id === (int)$activeBranchId) {
                    // ok
                } elseif (!$doc->branch_id && $doc->company_id && (int)$doc->company_id === (int)$activeCompanyId) {
                    // ok
                } elseif ($doc->distributions->contains('target_branch_id', $activeBranchId)) {
                    // ok
                } else {
                    return false;
                }
            } elseif ($activeCompanyId) {
                $docCompanyId = $doc->company_id ?? $doc->branch?->company_id;
                if ($docCompanyId && (int)$docCompanyId === (int)$activeCompanyId) {
                    // ok
                } elseif ($doc->distributions->contains(fn($dist) => $dist->targetBranch?->company_id == $activeCompanyId)) {
                    // ok
                } else {
                    return false;
                }
            }
            
            return true;
        });
    }

    /**
     * Get recent notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $rawNotifications = $request->user()->notifications()->latest()->limit(100)->get();
        $filtered = $this->filterNotifications($request->user(), $rawNotifications);

        $notifications = $filtered->take(20)->map(fn($n) => [
            'id'       => $n->id,
            'type'     => $n->data['type'] ?? 'general',
            'title'    => $n->data['title'] ?? '',
            'message'  => $n->data['message'] ?? '',
            'url'      => $n->data['url'] ?? '#',
            'icon'     => $n->data['icon'] ?? 'bell',
            'read'     => !is_null($n->read_at),
            'time'     => $n->created_at->diffForHumans(),
            'reason'   => $n->data['reason'] ?? ($n->data['notes'] ?? null),
        ])->values();

        $unreadCount = $filtered->whereNull('read_at')->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count'  => $unreadCount,
        ]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => $this->getUnreadCount($request->user()),
        ]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'unread_count' => 0,
        ]);
    }

    /**
     * Get unread notification count only (for polling fallback).
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'unread_count' => $this->getUnreadCount($request->user()),
        ]);
    }

    private function getUnreadCount($user): int
    {
        $rawUnread = $user->unreadNotifications()->latest()->limit(100)->get();
        return $this->filterNotifications($user, $rawUnread)->count();
    }
}
