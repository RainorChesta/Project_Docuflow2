<?php

namespace App\Http\Controllers;


use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class JoditController extends Controller
{
    public function upload(Request $request)
    {
        // 1. CEK MANUAL: Apakah ada file yang dikirim?
        // Jika tidak ada, langsung return JSON error (mencegah redirect 302).
        // Catatan: kalau request POST kosong padahal JS sudah append file,
        // kemungkinan besar file TIDAK sampai ke PHP karena melebihi
        // upload_max_filesize / post_max_size di php.ini — PHP diam-diam
        // membuang file-nya. Pesan ini menjelaskan dua-duanya.
        if (!$request->hasFile('files')) {
            $fileCount = count($request->allFiles());
            $msg = 'Tidak ada file yang ditemukan dalam request.';
            if ($fileCount === 0 && $request->isMethod('post') && count($request->all()) === 0) {
                $msg .= ' Kemungkinan file melebihi batas upload server (upload_max_filesize/post_max_size di php.ini), atau field bukan "files[]".';
            } else {
                $msg .= ' Pastikan nama field adalah "files[]".';
            }
            return response()->json([
                'success' => false,
                'data' => [
                    'files' => [],
                    'error' => 1,
                    'msg' => $msg,
                ]
            ], 422);
        }

        // 2. VALIDASI MANUAL (Bukan $request->validate)
        // Ini mencegah Laravel otomatis redirect 302 saat validasi gagal
        $validator = Validator::make($request->all(), [
            'files.*' => 'required|file|max:10240|mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,zip,txt',
        ]);

        if ($validator->fails()) {
            // Paksa return JSON jika gagal, JANGAN redirect
            return response()->json([
                'success' => false,
                'data' => [
                    'files' => [],
                    'error' => 1,
                    'msg' => $validator->errors()->first() // Ambil pesan error pertama (misal: "The file must not be greater than 10240 kilobytes")
                ]
            ], 422);
        }

        // 3. PROSES UPLOAD (Jika lolos validasi)
        $urls = [];
        foreach ($request->file('files') as $file) {
            $path = $file->store('jodit-uploads', 'public');
            $urls[] = Storage::url($path); 
        }

        // 4. RETURN SUKSES
        return response()->json([
            'success' => true,
            'data' => [
                'files' => $urls,
                'path' => '',
                'baseurl' => '',
                'error' => 0,
                'msg' => 'Upload berhasil',
            ],
        ]);
    }
}