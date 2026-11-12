<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use App\Models\InspectionPhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InspectionPhotoController
{
    public function store(Request $request, Inspection $inspection): RedirectResponse
    {
        abort_unless($inspection->status === 'in_progress', 409, 'Evidence can only be changed during an active inspection.');

        $validated = $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:20'],
            'photos.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
            'captions' => ['sometimes', 'array', 'max:20'],
            'captions.*' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($inspection, $validated): void {
            $nextSortOrder = ((int) $inspection->photos()->max('sort_order')) + 1;

            foreach ($validated['photos'] as $index => $file) {
                $path = $file->store('inspections/'.$inspection->id.'/photos', 'local');
                $filename = basename(str_replace('\\', '/', $file->getClientOriginalName()));
                $filename = mb_substr(preg_replace('/[\x00-\x1F\x7F]/u', '', $filename), 0, 255);

                $inspection->photos()->create([
                    'storage_key' => $path,
                    'original_filename' => $filename !== '' ? $filename : 'inspection-photo',
                    'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                    'byte_size' => $file->getSize(),
                    'caption' => $validated['captions'][$index] ?? null,
                    'sort_order' => $nextSortOrder + $index,
                    'uploaded_at' => now(),
                ]);
            }
        });

        return back()->with('status', count($validated['photos']).' photo(s) added to the inspection.');
    }

    public function show(InspectionPhoto $photo): StreamedResponse
    {
        return Storage::disk('local')->response($photo->storage_key, $photo->original_filename, [
            'Content-Type' => $photo->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, no-store',
        ], 'inline');
    }

    public function destroy(InspectionPhoto $photo): RedirectResponse
    {
        abort_unless($photo->inspection->status === 'in_progress', 409, 'Evidence can only be changed during an active inspection.');
        Storage::disk('local')->delete($photo->storage_key);
        $inspectionId = $photo->inspection_id;
        $photo->delete();

        return redirect()->route('staff.inspections.show', $inspectionId)->with('status', 'Photo removed.');
    }

    public function update(Request $request, InspectionPhoto $photo): RedirectResponse
    {
        abort_unless($photo->inspection->status === 'in_progress', 409, 'Evidence can only be changed during an active inspection.');
        $validated = $request->validate(['caption' => ['nullable', 'string', 'max:500']]);
        $photo->update(['caption' => $validated['caption'] ?? null]);

        return back()->with('status', 'Photo caption updated.');
    }

    public function reorder(Request $request, Inspection $inspection): RedirectResponse
    {
        abort_unless($inspection->status === 'in_progress', 409, 'Evidence can only be changed during an active inspection.');
        $validated = $request->validate(['photo_ids' => ['required', 'array', 'min:1', 'max:500'], 'photo_ids.*' => ['required', 'integer', 'distinct']]);
        $photos = $inspection->photos()->whereIn('id', $validated['photo_ids'])->pluck('id')->map(fn ($id) => (int) $id)->all();
        if (count($photos) !== count($validated['photo_ids'])) {
            return back()->withErrors(['photo_ids' => 'The photo order contains an image outside this inspection.']);
        }

        foreach ($validated['photo_ids'] as $index => $id) {
            $inspection->photos()->whereKey($id)->update(['sort_order' => $index + 1, 'updated_at' => now()]);
        }

        return back()->with('status', 'Photo order updated.');
    }
}
