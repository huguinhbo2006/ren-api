<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Asset\StoreAssetRequest;
use App\Http\Requests\Asset\UpdateAssetRequest;
use App\Http\Requests\Asset\UploadPhotoRequest;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Support\PlanHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class AssetController extends BaseController
{
    /**
     * Lista paginada de activos con filtros y búsqueda
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $assets = QueryBuilder::for(Asset::where('user_id', $userId))
            ->with(['category'])
            ->withCount('rentals')
            ->allowedFilters([
                AllowedFilter::exact('status'),
                AllowedFilter::exact('category_id'),
                AllowedFilter::scope('search'),
            ])
            ->allowedSorts([
                'name',
                'daily_rate_cents',
                'status',
                'created_at',
            ])
            ->defaultSort('-created_at')
            ->paginate($request->input('per_page', 15));

        return $this->paginated(
            $assets,
            AssetResource::collection($assets),
            'Activos obtenidos exitosamente.'
        );
    }

    /**
     * Registrar un nuevo activo (verifica límites del plan)
     */
    public function store(StoreAssetRequest $request): JsonResponse
    {
        $user = $request->user();

        if (! PlanHelper::canCreateAsset($user)) {
            $limit = PlanHelper::getPlanLimit($user, 'max_assets');
            return $this->error(
                "Has alcanzado el límite de {$limit} activos permitido en tu plan. Actualiza a Pro para registrar activos ilimitados.",
                403
            );
        }

        $validated = $request->validated();
        $images = $validated['images'] ?? [];
        unset($validated['images']);

        $asset = Asset::create(array_merge(
            $validated,
            [
                'user_id' => $user->id,
                'images_json' => $images,
            ]
        ));

        $asset->load('category');

        return $this->created(
            new AssetResource($asset),
            'Activo creado exitosamente.'
        );
    }

    /**
     * Detalle de un activo específico
     */
    public function show(Request $request, Asset $asset): JsonResponse
    {
        if ($asset->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $asset->load(['category', 'rentals.customer']);
        $asset->loadCount('rentals');

        return $this->success(
            new AssetResource($asset)
        );
    }

    /**
     * Actualizar activo
     */
    public function update(UpdateAssetRequest $request, Asset $asset): JsonResponse
    {
        if ($asset->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $validated = $request->validated();
        if (isset($validated['images'])) {
            $validated['images_json'] = $validated['images'];
            unset($validated['images']);
        }

        $asset->update($validated);
        $asset->load('category');

        return $this->success(
            new AssetResource($asset),
            'Activo actualizado exitosamente.'
        );
    }

    /**
     * Eliminar activo (Soft delete)
     */
    public function destroy(Request $request, Asset $asset): JsonResponse
    {
        if ($asset->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $asset->delete();

        return $this->success(null, 'Activo eliminado exitosamente.');
    }

    /**
     * Subir fotografía para un activo
     */
    public function uploadPhoto(UploadPhotoRequest $request, Asset $asset): JsonResponse
    {
        if ($asset->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $file = $request->file('photo');
        $path = $file->store('assets', 'public');

        $currentImages = $asset->images_json ?? [];
        $currentImages[] = $path;

        $asset->update(['images_json' => $currentImages]);
        $asset->load('category');

        return $this->success(
            new AssetResource($asset),
            'Fotografía subida exitosamente.'
        );
    }
}
