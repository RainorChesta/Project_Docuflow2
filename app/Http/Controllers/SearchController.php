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
        $q = trim((string) $request->get('q'));
        $documentTypeId = $request->get('document_type_id');
        $documentType = $request->get('document_type'); // accept either id or code
        $visibility = $request->get('visibility');
        $formatChoice = $request->get('format_choice');
        $lang = $request->get('lang', app()->getLocale());

        if (strlen($q) < 2 && !$documentTypeId && !$documentType && !$visibility && !$formatChoice) {
            return response()->json([
                'results' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => 0,
                    'per_page' => 10,
                    'has_more' => false,
                ],
            ]);
        }

        $user = auth()->user();

        $query = Document::with([
            'owner:id,name',
            'division:id,name,code',
            'documentType:id,name,code',
            'branch:id,name,code,company_id',
            'company:id,name,code',
            'currentVersion:id,document_id,version_number,status'
        ])->visibleTo($user);

        if ($visibility && in_array($visibility, ['general', 'division', 'personal'], true)) {
            $query->where('visibility', $visibility);
        }

        if ($formatChoice && in_array($formatChoice, ['baru', 'lama'], true)) {
            $query->where('format_choice', $formatChoice);
        }

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
                    ->orWhere('document_number', 'like', "%{$q}%")
                    ->orWhere('summary', 'like', "%{$q}%")
                    ->orWhereHas('division', function ($dQuery) use ($q) {
                        $dQuery->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%");
                    })
                    ->orWhereHas('branch', function ($bQuery) use ($q) {
                        $bQuery->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%");
                    })
                    ->orWhereHas('company', function ($cQuery) use ($q) {
                        $cQuery->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%");
                    })
                    ->orWhereHas('documentType', function ($dtQuery) use ($q) {
                        $dtQuery->where('name', 'like', "%{$q}%")->orWhere('code', 'like', "%{$q}%");
                    })
                    ->orWhereHas('owner', function ($uQuery) use ($q) {
                        $uQuery->where('name', 'like', "%{$q}%");
                    })
                    ->orWhereHas('currentVersion', function ($vQuery) use ($q) {
                        $vQuery->where('content', 'like', "%{$q}%");
                    });
            });
        }

        $paginated = $query->latest()->paginate(10);

        $results = collect($paginated->items())->map(fn(Document $doc) => [
            'id'              => $doc->id,
            'title'           => $doc->title,
            'document_number' => $doc->document_number,
            'format_choice'   => $doc->format_choice ?? 'baru',
            'visibility'      => $doc->visibility,
            'owner'           => $doc->owner?->name,
            'division'        => $doc->division?->name,
            'division_code'   => $doc->division?->code,
            'type'            => $doc->documentType?->name,
            'type_code'       => $doc->documentType?->code,
            'branch'          => $doc->branch?->name,
            'branch_code'     => $doc->branch?->code,
            'company'         => $doc->company?->name ?? $doc->branch?->company?->name,
            'version'         => $doc->currentVersion?->version_number ?? 1,
            'is_expired'      => (bool) $doc->is_expired,
            'updated_at'      => $doc->updated_at?->diffForHumans() ?? '',
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
