<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class MediaUploadController extends Controller
{
    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:512000',
            'editorial_id' => 'nullable|integer',
            'format' => 'required|string',
        ]);

        $file = $request->file('file');
        $type = $request->format;
        $folder = "media/{$type}s";

        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs($folder, $filename, 'public');

        $data = json_encode([
            'path' => $path,
            'filename' => $filename,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'type' => $type,
        ]);

        return response($data, 200)
            ->header('Content-Type', 'text/plain');
    }

    public function revert(Request $request)
    {
        $path = $request->getContent();

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }

        return response('', 200)
            ->header('Content-Type', 'text/plain');
    }
}