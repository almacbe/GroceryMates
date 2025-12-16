<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class MergeSessionController extends Controller
{
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
        ]);

        $state = Cache::get('merge_state', $this->getInitialState());
        $state[$validated['listName']] = $validated['content'];

        // When a list is updated, the result is cleared
        $state['result'] = '';

        Cache::put('merge_state', $state, now()->addHour());

        return response()->json(['status' => 'success', 'state' => $state]);
    }

    public function getState()
    {
        $state = Cache::get('merge_state', $this->getInitialState());
        return response()->json($state);
    }

    public function clearState()
    {
        Cache::forget('merge_state');
        return response()->json(['status' => 'success']);
    }
}