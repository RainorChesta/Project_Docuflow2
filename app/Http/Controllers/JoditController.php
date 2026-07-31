<?php

namespace App\Http\Controllers;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class JoditController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'files.*' => 'required|image|max:5120', // 5MB
        ]);

        $urls = [];

        foreach ($request->file('files', []) as $file) {
            $path = $file->store('jodit-uploads', 'public');
            $urls[] = Storage::url($path); // hasil: /storage/jodit-uploads/xxxx.jpg
        }

        return response()->json([
            'success' => true,
            'data' => [
                'files' => $urls,
                'path' => '',
                'baseurl' => '',
                'error' => 0,
                'msg' => '',
            ],
        ]);
    }
}
