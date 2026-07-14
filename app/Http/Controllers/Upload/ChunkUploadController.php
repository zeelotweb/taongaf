<?php

namespace App\Http\Controllers\Upload;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMediaJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChunkUploadController extends Controller
{
    protected string $disk = 'public';

    protected array $allowedMimes = [
        'video' => ['video/mp4', 'video/quicktime', 'video/x-msvideo'],
        'audio' => ['audio/mpeg', 'audio/wav', 'audio/aac', 'audio/x-m4a', 'audio/mp4'],
        'pdf'   => ['application/pdf'],
        'image' => ['image/jpeg', 'image/png', 'image/webp', 'image/heic'],
    ];

    protected array $maxSizes = [
        'video' => 500 * 1024 * 1024,
        'audio' => 100 * 1024 * 1024,
        'pdf'   => 50  * 1024 * 1024,
        'image' => 10  * 1024 * 1024,
    ];

    public function chunk(Request $request)
    {
        $key = 'chunk-upload:' . Auth::id();
        if (RateLimiter::tooManyAttempts($key, 200)) {
            return response()->json(['error' => 'Too many requests.'], 429);
        }
        RateLimiter::hit($key, 60);

        $request->validate([
            'upload_id'   => 'required|uuid',
            'chunk_index' => 'required|integer|min:0',
            'file'        => 'required|file|max:10240',
            'model_type' => 'nullable|in:editorial,book,chapter,user,message,chat_room_message',
        ]);

        $userId = Auth::id();
        $dir    = "temp/chunks/{$userId}/{$request->upload_id}";

        Storage::disk($this->disk)->putFileAs(
            $dir,
            $request->file('file'),
            $request->chunk_index
        );

        return response()->json(['status' => 'chunk_received']);
    }

    public function complete(Request $request)
    {
        Log::info('complete called', [
            'purpose'    => $request->purpose,
            'model_type' => $request->model_type,
            'model_id'   => $request->model_id,
            'type'       => $request->type,
            'filename'   => $request->filename,
        ]);

        $request->validate([
            'upload_id'    => 'required|uuid',
            'total_chunks' => 'required|integer|min:1',
            'filename'     => 'required|string',
            'type'         => 'required|in:video,audio,pdf,image',
            'purpose'      => 'required|in:content,cover,profile,message',
            'model_type' => 'nullable|in:editorial,book,chapter,user,message,chat_room_message',
            'model_id'     => 'nullable|integer',
        ]);

        $userId   = Auth::id();
        $chunkDir = "temp/chunks/{$userId}/{$request->upload_id}";

        if (!Storage::disk($this->disk)->exists($chunkDir)) {
            return response()->json(['error' => 'Upload not found.'], 404);
        }

        $extension = strtolower(pathinfo($request->filename, PATHINFO_EXTENSION));
        $finalName = Str::uuid() . ".{$extension}";
        $folder    = "media/{$request->type}s";
        $finalPath = "{$folder}/{$finalName}";

        Storage::disk($this->disk)->makeDirectory($folder);

        $absoluteFinal = Storage::disk($this->disk)->path($finalPath);
        $output        = fopen($absoluteFinal, 'ab');

        try {
            for ($i = 0; $i < $request->total_chunks; $i++) {
                $chunkPath = "{$chunkDir}/{$i}";

                if (!Storage::disk($this->disk)->exists($chunkPath)) {
                    throw new \Exception("Missing chunk {$i}");
                }

                $chunkStream = Storage::disk($this->disk)->readStream($chunkPath);
                stream_copy_to_stream($chunkStream, $output);
                fclose($chunkStream);
            }
        } catch (\Exception $e) {
            fclose($output);
            Storage::disk($this->disk)->delete($finalPath);
            Log::error('Chunk assembly failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], 422);
        }

        fclose($output);
        Storage::disk($this->disk)->deleteDirectory($chunkDir);

        $size    = Storage::disk($this->disk)->size($finalPath);
        $maxSize = $this->maxSizes[$request->type] ?? 200 * 1024 * 1024;

        if ($size > $maxSize) {
            Storage::disk($this->disk)->delete($finalPath);
            return response()->json(['error' => 'File exceeds maximum allowed size.'], 422);
        }

        $mime         = Storage::disk($this->disk)->mimeType($finalPath);
        $allowedMimes = $this->allowedMimes[$request->type] ?? [];

        if (!in_array($mime, $allowedMimes)) {
            Storage::disk($this->disk)->delete($finalPath);
            return response()->json(['error' => 'Invalid file type.'], 422);
        }

        // Dispatch processing job
        ProcessMediaJob::dispatch(
            userId:    $userId,
            path:      $finalPath,
            disk:      $this->disk,
            type:      $request->type,
            mime:      $mime,
            size:      $size,
            filename:  $finalName,
            original:  $request->filename,
            purpose:   $request->purpose,
            modelType: $request->model_type,
            modelId:   $request->model_id,
        );

        return response($finalPath, 200)
            ->header('Content-Type', 'text/plain');
    }

    public function revert(Request $request)
    {
        $path = $request->getContent() ?: $request->input('filename');

        if ($path && Storage::disk($this->disk)->exists($path)) {
            Storage::disk($this->disk)->delete($path);
        }

        return response('', 200)
            ->header('Content-Type', 'text/plain');
    }
}