<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Global document search — scoped by visibility.
     *
     * Reuses Document::scopeVisibleTo() to ensure division documents
     * never leak to users outside that division.
     */
    public function search(Request $request): JsonResponse
    {
        $q = trim($request->get('q', ''));
        $documentTypeId = $request->get('document_type_id');
        $documentType = $request->get('document_type'); // accept either id or code
        $lang = $request->get('lang', app()->getLocale());

        if (strlen($q) < 2 && !$documentTypeId && !$documentType) {
            return response()->json([
                'results' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                    'per_page' => 10,
                ],
            ]);
        }

        $user = auth()->user();

        $query = Document::with('owner:id,name', 'division:id,name,code', 'documentType:id,name,code')
            ->visibleTo($user);

        if ($documentTypeId) {
            $query->where('document_type_id', $documentTypeId);
        } elseif ($documentType) {
            $query->whereHas('documentType', function ($dtQuery) use ($documentType) {
                $dtQuery->where('code', $documentType)->orWhere('name', $documentType);
            });
        }

        if (strlen($q) >= 2) {
            $query->where(function ($sub) use ($q, $lang) {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('document_number', 'like', "%{$q}%");

                // Language-aware search: check version content and document summaries if available
                $sub->orWhereHas('currentVersion', function ($vQuery) use ($q) {
                    $vQuery->where('content', 'like', "%{$q}%");
                });

                if ($lang === 'en') {
                    $sub->orWhere('summary_density_brief', 'like', "%{$q}%")
                        ->orWhere('summary_density_detailed', 'like', "%{$q}%");
                } else {
                    $sub->orWhere('summary_density_brief', 'like', "%{$q}%")
                        ->orWhere('summary_density_detailed', 'like', "%{$q}%");
                }
            });
        }

        $paginated = $query->latest()->paginate(10);

        $results = collect($paginated->items())->map(fn(Document $doc) => [
            'id'              => $doc->id,
            'title'           => $doc->title,
            'document_number' => $doc->document_number,
            'visibility'      => $doc->visibility,
            'owner'           => $doc->owner?->name,
            'division'        => $doc->division?->name,
            'type'            => $doc->documentType?->name,
            'type_code'       => $doc->documentType?->code,
            'updated_at'      => $doc->updated_at->diffForHumans(),
            'url'             => route('documents.show', $doc),
        ]);

        return response()->json([
            'results' => $results,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'per_page' => $paginated->perPage(),
                'has_more' => $paginated->hasMorePages(),
            ],
        ]);
    }
}
