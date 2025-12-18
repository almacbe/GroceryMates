<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\MergeChecklistItemUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

class MergeChecklistController extends Controller
{
    private const CHECKLIST_KEY = 'merge_checklist_state';

    /**
     * @return array{items: array<int, array{id: string, text: string, checked: bool}>}
     */
    private function getInitialState(): array
    {
        return ['items' => []];
    }

    public function state(): JsonResponse
    {
        $state = Cache::get(self::CHECKLIST_KEY, $this->getInitialState());

        return response()->json($state);
    }

    public function updateItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => 'required|string',
            'checked' => 'required|boolean',
            'originId' => 'nullable|string|max:128',
        ]);

        $state = Cache::get(self::CHECKLIST_KEY, $this->getInitialState());
        $items = $state['items'] ?? [];

        $updated = false;

        foreach ($items as $index => $item) {
            if (($item['id'] ?? null) !== $validated['id']) {
                continue;
            }

            $items[$index]['checked'] = $validated['checked'];
            $updated = true;
            break;
        }

        if (! $updated) {
            return response()->json(['message' => 'Item not found.'], 404);
        }

        $state['items'] = $items;
        Cache::put(self::CHECKLIST_KEY, $state, now()->addHour());

        try {
            event(new MergeChecklistItemUpdated(
                id: $validated['id'],
                checked: $validated['checked'],
                originId: $validated['originId'] ?? null,
            ));
        } catch (Throwable $throwable) {
            report($throwable);
        }

        return response()->json(['status' => 'success']);
    }
}
