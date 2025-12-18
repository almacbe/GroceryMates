<?php

namespace App\Http\Controllers;

use App\Events\MergeStateUpdated;
use App\Events\MergeChecklistReplaced;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

class MergeSessionController extends Controller
{
    private const STATE_KEY = 'merge_state';
    private const CHECKLIST_KEY = 'merge_checklist_state';

    private function getInitialState(): array
    {
        return [
            'listA' => '',
            'listB' => '',
            'result' => '',
        ];
    }

    public function updateList(Request $request)
    {
        $validated = $request->validate([
            'listName' => 'required|in:listA,listB',
            'content' => 'nullable|string',
            'originId' => 'nullable|string|max:128',
        ]);

        $state = Cache::get(self::STATE_KEY, $this->getInitialState());
        $state[$validated['listName']] = $validated['content'];

        // When a list is updated, the result is cleared
        $state['result'] = '';

        Cache::put(self::STATE_KEY, $state, now()->addHour());
        Cache::forget(self::CHECKLIST_KEY);

        try {
            event(new MergeStateUpdated(
                updates: [
                    $validated['listName'] => (string) ($validated['content'] ?? ''),
                ],
                originId: $validated['originId'] ?? null,
            ));

            event(new MergeChecklistReplaced(items: [], originId: $validated['originId'] ?? null));
        } catch (Throwable $throwable) {
            report($throwable);
        }

        return response()->json(['status' => 'success', 'state' => $state]);
    }

    public function getState()
    {
        $state = Cache::get(self::STATE_KEY, $this->getInitialState());
        return response()->json($state);
    }

    public function clearState()
    {
        Cache::forget(self::STATE_KEY);
        Cache::forget(self::CHECKLIST_KEY);

        try {
            event(new MergeStateUpdated(
                updates: [
                    'listA' => '',
                    'listB' => '',
                ],
            ));

            event(new MergeChecklistReplaced(items: []));
        } catch (Throwable $throwable) {
            report($throwable);
        }

        return response()->json(['status' => 'success']);
    }
}
