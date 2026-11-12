<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class InspectionVideoController
{
    public function store(Request $request, Inspection $inspection): RedirectResponse
    {
        abort_unless($inspection->status === 'in_progress', 409, 'Video details can only be changed during an active inspection.');
        $packageSnapshot = $inspection->package_snapshot;
        abort_unless($this->supportsVideo($packageSnapshot), 403, 'YouTube video reporting is only available for the Gold package.');

        $validated = $request->validate(['youtube_url' => ['required', 'url', 'max:2048']]);
        $videoId = $this->extractVideoId($validated['youtube_url']);
        if (! $videoId) {
            return back()->withErrors(['youtube_url' => 'Enter a supported YouTube watch, short, embed or youtu.be link.'])->withInput();
        }

        $inspection->video()->updateOrCreate([], [
            'provider' => 'youtube',
            'source_url' => 'https://www.youtube.com/watch?v='.$videoId,
            'video_id' => $videoId,
        ]);

        return back()->with('status', 'YouTube video link saved. Playback depends on the video owner’s embed permissions.');
    }

    public function destroy(Inspection $inspection): RedirectResponse
    {
        abort_unless($inspection->status === 'in_progress', 409, 'Video details can only be changed during an active inspection.');
        abort_unless($this->supportsVideo($inspection->package_snapshot), 403, 'YouTube video reporting is only available for the Gold package.');
        $inspection->video()->delete();

        return back()->with('status', 'YouTube video link removed.');
    }

    private function extractVideoId(string $url): ?string
    {
        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $host = preg_replace('/^www\./', '', $host);
        $id = null;

        if ($host === 'youtu.be') {
            $id = trim($parts['path'] ?? '', '/');
        } elseif (in_array($host, ['youtube.com', 'm.youtube.com', 'music.youtube.com'], true)) {
            parse_str($parts['query'] ?? '', $query);
            $path = trim($parts['path'] ?? '', '/');
            if ($path === 'watch') {
                $id = $query['v'] ?? null;
            } elseif (preg_match('~^(?:embed|shorts|live)/([A-Za-z0-9_-]+)$~', $path, $matches)) {
                $id = $matches[1];
            }
        }

        return is_string($id) && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : null;
    }

    private function supportsVideo(array $packageSnapshot): bool
    {
        return strtolower((string) ($packageSnapshot['code'] ?? '')) === 'gold'
            && (bool) ($packageSnapshot['video_included'] ?? false);
    }
}
