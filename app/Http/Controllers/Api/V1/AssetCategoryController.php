<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\AssetCategory\StoreAssetCategoryRequest;
use App\Http\Requests\AssetCategory\UpdateAssetCategoryRequest;
use App\Http\Resources\AssetCategoryResource;
use App\Models\AssetCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AssetCategoryController extends BaseController
{
    /**
     * Lista de categorías del usuario autenticado
     */
    public function index(Request $request): JsonResponse
    {
        $categories = AssetCategory::where('user_id', $request->user()->id)
            ->withCount('assets')
            ->orderBy('name')
            ->get();

        return $this->success(
            AssetCategoryResource::collection($categories),
            'Categorías obtenidas exitosamente.'
        );
    }

    /**
     * Crear una nueva categoría
     */
    public function store(StoreAssetCategoryRequest $request): JsonResponse
    {
        $category = AssetCategory::create(array_merge(
            $request->validated(),
            ['user_id' => $request->user()->id]
        ));

        return $this->created(
            new AssetCategoryResource($category),
            'Categoría creada exitosamente.'
        );
    }

    /**
     * Mostrar una categoría específica
     */
    public function show(Request $request, AssetCategory $assetCategory): JsonResponse
    {
        if ($assetCategory->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $assetCategory->loadCount('assets');

        return $this->success(
            new AssetCategoryResource($assetCategory)
        );
    }

    /**
     * Actualizar categoría
     */
    public function update(UpdateAssetCategoryRequest $request, AssetCategory $assetCategory): JsonResponse
    {
        if ($assetCategory->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $assetCategory->update($request->validated());

        return $this->success(
            new AssetCategoryResource($assetCategory),
            'Categoría actualizada exitosamente.'
        );
    }

    /**
     * Eliminar categoría
     */
    public function destroy(Request $request, AssetCategory $assetCategory): JsonResponse
    {
        if ($assetCategory->user_id !== $request->user()->id) {
            return $this->forbidden();
        }

        $assetCategory->delete();

        return $this->success(null, 'Categoría eliminada exitosamente.');
    }
}
