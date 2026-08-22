<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RetentionController extends Controller
{
    public function edit(): View
    {
        $this->authorize('admin');

        $retentionDays = (int) Setting::get('version_retention_days', config('app.version_retention_days', 365));
        $documentRetentionYears = (int) Setting::get('document_retention_years', config('app.document_retention_years', 2));

        return view('admin.retention.edit', compact('retentionDays', 'documentRetentionYears'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->authorize('admin');

        $validated = $request->validate([
            'retention_days' => 'required|integer|min:1|max:3650',
            'document_retention_years' => 'required|integer|min:1|max:100',
        ]);

        Setting::set('version_retention_days', $validated['retention_days']);
        Setting::set('document_retention_years', $validated['document_retention_years']);

        return back()->with('success', 'Retention periods saved successfully.');
    }
}
