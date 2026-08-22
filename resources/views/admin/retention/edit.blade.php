<x-app-layout>
    <x-slot name="header">Retention</x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto w-full px-0">
            @if(session('success'))
                <div class="alert alert-success mb-4">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-error mb-4">{{ $errors->first() }}</div>
            @endif

            <div class="card bg-base-100 border border-base-300 shadow-sm">
                <div class="card-body">
                    <h2 class="card-title text-base">Version Retention</h2>
                    <p class="text-sm text-base-content/60">
                        Non-active document versions (pending, rejected, discarded) older than this many days will be
                        deleted by the daily purge command. The active version is never deleted.
                    </p>

                    <form method="POST" action="{{ route('admin.retention.update') }}" class="mt-4 space-y-4">
                        @csrf @method('PUT')

                        <div class="form-control">
                            <label class="label" for="retention_days">
                                <span class="label-text">Version Retention Period (days)</span>
                            </label>
                            <input type="number" name="retention_days" id="retention_days"
                                   class="input input-bordered w-full" min="1" max="3650"
                                   value="{{ old('retention_days', $retentionDays) }}" required>
                        </div>
                        
                        <div class="divider"></div>
                        
                        <h2 class="card-title text-base mt-4">Document Expiration</h2>
                        <p class="text-sm text-base-content/60">
                            Documents without a manually specified expiration date will automatically expire after this many years.
                        </p>
                        
                        <div class="form-control mt-4">
                            <label class="label" for="document_retention_years">
                                <span class="label-text">Default Document Retention Period (years)</span>
                            </label>
                            <input type="number" name="document_retention_years" id="document_retention_years"
                                   class="input input-bordered w-full" min="1" max="100"
                                   value="{{ old('document_retention_years', $documentRetentionYears) }}" required>
                        </div>

                        <div class="flex flex-wrap justify-end">
                            <button type="submit" class="btn btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                                Save
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
