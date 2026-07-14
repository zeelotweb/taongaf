<?php

namespace App\Jobs;

use App\Models\Media;
use App\Models\Editorial;
use App\Models\Chapter;
use App\Models\User;
use App\Models\Book;
use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;

class ProcessMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 3;
    public int $timeout = 300;

    public function __construct(
        public readonly int     $userId,
        public readonly string  $path,
        public readonly string  $disk,
        public readonly string  $type,
        public readonly string  $mime,
        public readonly int     $size,
        public readonly string  $filename,
        public readonly string  $original,
        public readonly string  $purpose,
        public readonly ?string $modelType,
        public readonly ?int    $modelId,
    ) {}

    public function handle(): void
    {
        try {
            $thumbnailUrl = null;
            $duration     = null;

            match ($this->type) {
                'video' => [$thumbnailUrl, $duration] = $this->processVideo(),
                'audio' => $duration                  = $this->processAudio(),
                'pdf'   => $thumbnailUrl              = $this->processPdf(),
                'image' => null,
                default => null,
            };

            match ($this->purpose) {
                'cover'   => $this->handleCover($thumbnailUrl),
                'content' => $this->handleContent($thumbnailUrl, $duration),
                'profile' => $this->handleProfile(),
                'message' => $this->handleMessage($thumbnailUrl, $duration),
                default   => null,
            };

        } catch (\Exception $e) {
            Log::error('ProcessMediaJob failed', [
                'error'   => $e->getMessage(),
                'path'    => $this->path,
                'purpose' => $this->purpose,
            ]);
            $this->fail($e);
        }
    }

    private function handleCover(?string $thumbnailUrl): void
    {
        match ($this->modelType) {
            'editorial' => Editorial::where('id', $this->modelId)
                ->update(['cover_image' => $this->path]),
            'chapter'   => Chapter::where('id', $this->modelId)
                ->update(['cover_image' => $this->path]),
            'book'      => Book::where('id', $this->modelId)
                ->update(['cover_image' => $this->path]),
            default     => null,
        };
    }

    private function handleContent(?string $thumbnailUrl, ?string $duration): void
    {
        $model = $this->resolveModel();

        Media::create([
            'user_id'       => $this->userId,
            'mediable_type' => get_class($model),
            'mediable_id'   => $this->modelId,
            'disk'          => $this->disk,
            'path'          => $this->path,
            'filename'      => $this->filename,
            'original_name' => $this->original,
            'mime_type'     => $this->mime,
            'size'          => $this->size,
            'type'          => $this->type,
            'thumbnail_url' => $thumbnailUrl,
            'duration'      => $duration,
            'is_processed'  => true,
            'is_failed'     => false,
            'sort_order'    => Media::where('mediable_type', get_class($model))
                ->where('mediable_id', $this->modelId)
                ->count(),
        ]);

        if ($this->modelType === 'chapter') {
            $chapter = Chapter::find($this->modelId);
            if ($chapter) {
                $chapter->book->update(["has_{$this->type}" => true]);
            }
        }
    }

    private function handleProfile(): void
    {
        User::where('id', $this->userId)
            ->update(['avatar_path' => $this->path]);
    }

private function handleMessage(?string $thumbnailUrl, ?string $duration): void
{
    $mediableType = match($this->modelType) {
        'chat_room_message' => \App\Models\ChatRoomMessage::class,
        default             => Message::class,
    };

    Media::create([
        'user_id'       => $this->userId,
        'mediable_type' => $mediableType,
        'mediable_id'   => $this->modelId,
        'disk'          => $this->disk,
        'path'          => $this->path,
        'filename'      => $this->filename,
        'original_name' => $this->original,
        'mime_type'     => $this->mime,
        'size'          => $this->size,
        'type'          => $this->type,
        'thumbnail_url' => $thumbnailUrl,
        'duration'      => $duration,
        'is_processed'  => true,
        'is_failed'     => false,
    ]);
}

    private function resolveModel(): object
    {
        return match ($this->modelType) {
            'editorial' => Editorial::findOrFail($this->modelId),
            'chapter'   => Chapter::findOrFail($this->modelId),
            'user'      => User::findOrFail($this->modelId),
            default     => throw new \Exception("Unknown model type: {$this->modelType}"),
        };
    }

    private function processVideo(): array
    {
        $thumbnailName = Str::uuid() . '.jpg';
        $thumbnailPath = "media/thumbnails/{$thumbnailName}";

        Storage::disk($this->disk)->makeDirectory('media/thumbnails');

        FFMpeg::fromDisk($this->disk)
            ->open($this->path)
            ->getFrameFromSeconds(3)
            ->export()
            ->toDisk($this->disk)
            ->save($thumbnailPath);

        $duration = (string) FFMpeg::fromDisk($this->disk)
            ->open($this->path)
            ->getDurationInSeconds();

        return [
            Storage::disk($this->disk)->url($thumbnailPath),
            $duration,
        ];
    }

    private function processAudio(): string
    {
        return (string) FFMpeg::fromDisk($this->disk)
            ->open($this->path)
            ->getDurationInSeconds();
    }

    private function processPdf(): string
    {
        $thumbnailName = Str::uuid() . '.jpg';
        $thumbnailPath = "media/thumbnails/{$thumbnailName}";

        Storage::disk($this->disk)->makeDirectory('media/thumbnails');

        $absolutePath      = Storage::disk($this->disk)->path($this->path);
        $absoluteThumbnail = Storage::disk($this->disk)->path($thumbnailPath);

        $pdf = new \Spatie\PdfToImage\Pdf($absolutePath);
        $pdf->selectPage(1)->save($absoluteThumbnail);

        return Storage::disk($this->disk)->url($thumbnailPath);
    }
}