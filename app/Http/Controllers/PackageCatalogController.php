<?php

namespace App\Http\Controllers;

use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PackageCatalogController
{
    public function index(): JsonResponse
    {
        $packages = Package::query()->where('active', true)
            ->with(['includedFeatures', 'eligibleAddons'])
            ->orderBy('sort_order')
            ->get();

        return response()->json(['data' => $packages->map(fn (Package $package) => $this->serialize($package))]);
    }

    public function show(int $packageId): JsonResponse
    {
        $package = Package::query()->where('active', true)
            ->with(['includedFeatures', 'eligibleAddons'])
            ->findOrFail($packageId);

        return response()->json(['data' => $this->serialize($package)]);
    }

    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'package_id' => ['required', 'integer', Rule::exists('packages', 'id')->where('active', true)],
            'addons' => ['sometimes', 'array', 'max:20'],
            'addons.*' => ['required', 'integer', 'distinct'],
        ]);

        $package = Package::query()->where('active', true)->with('eligibleAddons')->findOrFail($validated['package_id']);
        $requestedIds = collect($validated['addons'] ?? [])->map(fn ($id) => (int) $id)->unique()->values();
        $eligibleIds = $package->eligibleAddons->modelKeys();

        if ($requestedIds->diff($eligibleIds)->isNotEmpty()) {
            return response()->json(['message' => 'One or more selected add-ons are not eligible for this package.'], 422);
        }

        $addons = $package->eligibleAddons->whereIn('id', $requestedIds);
        $totalCents = (int) round((float) $package->base_price * 100);
        foreach ($addons as $addon) {
            $totalCents += (int) round((float) $addon->addon_price * 100);
        }

        return response()->json([
            'currency' => $package->currency,
            'total' => number_format($totalCents / 100, 2, '.', ''),
            'addons' => $addons->map(fn ($addon) => ['id' => $addon->id, 'name' => $addon->name, 'price' => $addon->addon_price])->values(),
        ]);
    }

    private function serialize(Package $package): array
    {
        return [
            'id' => $package->id,
            'code' => $package->code,
            'name' => $package->name,
            'description' => $package->description,
            'base_price' => $package->base_price,
            'currency' => $package->currency,
            'video_included' => $package->videoEnabled(),
            'is_demo' => $package->is_demo,
            'included_features' => $package->includedFeatures->map(fn ($feature) => [
                'id' => $feature->id, 'code' => $feature->code, 'name' => $feature->name, 'description' => $feature->description,
            ])->values(),
            'eligible_addons' => $package->eligibleAddons->map(fn ($feature) => [
                'id' => $feature->id, 'code' => $feature->code, 'name' => $feature->name,
                'description' => $feature->description, 'price' => $feature->addon_price,
            ])->values(),
        ];
    }
}
