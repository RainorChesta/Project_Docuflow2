<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = auth()->user();

        // Search across every document the user may see.
        $results = null;
        if ($search = $request->get('search')) {
            $results = Document::with('owner', 'division', 'currentVersion')
                ->visibleTo($user)
                ->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('document_number', 'like', "%{$search}%");
                })
                ->latest()
                ->paginate(10)
                ->withQueryString();
        }

        $recent = $user->documents()->with('division', 'currentVersion')
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact('results', 'recent'));
    }
}
