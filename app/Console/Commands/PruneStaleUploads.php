<?php

namespace App\Console\Commands;


//use Illuminate\Console\Attributes\Description;
//use Illuminate\Console\Attributes\Signature;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class PruneStaleUploads extends Command
{
    protected $signature = 'uploads:prune';
    protected $description = 'Remove stale chunk and assembled temp uploads';

    public function handle(): void
    {
        $disk     = Storage::disk('public');
        $now      = now();
        $pruned   = 0;

        // Prune stale chunks older than 24 hours
        $chunkDirs = $disk->directories('temp/chunks');

        foreach ($chunkDirs as $userDir) {
            $uploadDirs = $disk->directories($userDir);

            foreach ($uploadDirs as $uploadDir) {
                $files = $disk->files($uploadDir);

                if (empty($files)) {
                    $disk->deleteDirectory($uploadDir);
                    $pruned++;
                    continue;
                }

                $lastModified = collect($files)
                    ->map(fn($f) => $disk->lastModified($f))
                    ->max();

                if ($now->timestamp - $lastModified > 86400) {
                    $disk->deleteDirectory($uploadDir);
                    $pruned++;
                }
            }

            // Clean up empty user dirs
            if (empty($disk->directories($userDir))) {
                $disk->deleteDirectory($userDir);
            }
        }

        // Prune stale assembled temp files older than 48 hours
        $tempFiles = $disk->files('temp');

        foreach ($tempFiles as $file) {
            $lastModified = $disk->lastModified($file);

            if ($now->timestamp - $lastModified > 172800) {
                $disk->delete($file);
                $pruned++;
            }
        }

        $this->info("Pruned {$pruned} stale upload(s).");
    }
}