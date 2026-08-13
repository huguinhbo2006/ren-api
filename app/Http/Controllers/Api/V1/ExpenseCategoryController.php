<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\ExpenseCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseCategoryController extends BaseController
{
    /**
     * Listar categorías de egresos del usuario (más categorías globales por defecto)
     */
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $categories = ExpenseCategory::where(function ($query) use ($userId) {
            $query->where('user_id', $userId)
                  ->orWhereNull('user_id');
        })
        ->orderBy('name')
        ->get();

        return $this->success($categories, 'Categorías de egresos obtenidas exitosamente.');
    }

    /**
     * Crear una nueva categoría de egreso personalizada
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:255',
        ]);

        $category = ExpenseCategory::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'icon' => $validated['icon'] ?? 'bi-tag',
            'color' => $validated['color'] ?? '#2563eb',
            'description' => $validated['description'] ?? null,
        ]);

        return $this->created($category, 'Categoría de egreso creada exitosamente.');
    }

    /**
     * Actualizar categoría de egreso
     */
    public function update(Request $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        if ($expenseCategory->user_id !== $request->user()->id) {
            return $this->forbidden('No tienes permiso para modificar esta categoría.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'icon' => 'nullable|string|max:50',
            'color' => 'nullable|string|max:20',
            'description' => 'nullable|string|max:255',
        ]);

        $expenseCategory->update($validated);

        return $this->success($expenseCategory, 'Categoría de egreso actualizada exitosamente.');
    }

    /**
     * Eliminar categoría de egreso
     */
    public function destroy(Request $request, ExpenseCategory $expenseCategory): JsonResponse
    {
        if ($expenseCategory->user_id !== $request->user()->id) {
            return $this->forbidden('No tienes permiso para eliminar esta categoría.');
        }

        $expenseCategory->delete();

        return $this->noContent('Categoría de egreso eliminada exitosamente.');
    }
}
