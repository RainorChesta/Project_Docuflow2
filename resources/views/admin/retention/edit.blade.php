<x-app-layout>
    <x-slot name="header">Retention</x-slot>

    <div class="py-6">
        <div class="max-w-2xl mx-auto">
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
                                <span class="label-text">Retention period (days)</span>
                            </label>
                            <input type="number" name="retention_days" id="retention_days"
                                   class="input input-bordered w-full" min="1" max="3650"
                                   value="{{ old('retention_days', $retentionDays) }}" required>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
