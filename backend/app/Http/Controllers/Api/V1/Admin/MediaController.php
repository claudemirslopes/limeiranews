<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('upload-media');

        $media = Media::query()
            ->when(! $request->user()->isAdmin() && ! $request->user()->isEditor(), fn ($q) => $q->where('user_id', $request->user()->id))
            ->latest()
            ->paginate(min((int) $request->query('per_page', 24), 100));

        return response()->json([
            'data' => $media->getCollection()->map(fn (Media $m) => $this->payload($m))->values(),
            'meta' => [
                'current_page' => $media->currentPage(),
                'last_page' => $media->lastPage(),
                'per_page' => $media->perPage(),
                'total' => $media->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        Gate::authorize('upload-media');

        $data = $request->validate([
            'file' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'caption' => ['nullable', 'string', 'max:255'],
            'credit' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('file');
        $disk = 'public';
        $directory = 'media/'.now()->format('Y/m');
        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $storedName = Str::uuid()->toString().'.'.$extension;

        [$width, $height] = array_pad(@getimagesize($file->getRealPath()) ?: [], 2, null);

        $path = $file->storeAs($directory, $storedName, $disk);

        $media = Media::query()->create([
            'user_id' => $request->user()->id,
            'disk' => $disk,
            'path' => $path,
            'filename' => Str::limit($file->getClientOriginalName(), 200, ''),
            'mime_type' => $file->getMimeType() ?? 'application/octet-stream',
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'alt_text' => $data['alt_text'] ?? null,
            'caption' => $data['caption'] ?? null,
            'credit' => $data['credit'] ?? null,
        ]);

        AuditLog::record('media.uploaded', $media, ['path' => $path], $request->user(), $request);

        return response()->json(['data' => $this->payload($media)], 201);
    }

    public function destroy(Request $request, Media $media): JsonResponse
    {
        Gate::authorize('upload-media');

        $user = $request->user();
        abort_unless($user->isAdmin() || $user->isEditor() || $media->user_id === $user->id, 403);

        AuditLog::record('media.deleted', $media, [], $user, $request);
        $media->delete();

        return response()->json(['message' => 'Arquivo removido.']);
    }

    /** @return array<string, mixed> */
    private function payload(Media $media): array
    {
        return [
            'id' => $media->id,
            'url' => Storage::disk($media->disk)->url($media->path),
            'path' => $media->path,
            'filename' => $media->filename,
            'mime_type' => $media->mime_type,
            'size' => $media->size,
            'width' => $media->width,
            'height' => $media->height,
            'alt_text' => $media->alt_text,
            'caption' => $media->caption,
            'credit' => $media->credit,
            'created_at' => $media->created_at?->toIso8601String(),
        ];
    }
}
