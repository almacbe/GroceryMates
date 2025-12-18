<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\MergeChecklistReplaced;
use App\Services\MergeListsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;
use Throwable;

class MergeListsController extends Controller
{
    private const STATE_KEY = 'merge_state';
    private const CHECKLIST_KEY = 'merge_checklist_state';

    public function __construct(private readonly MergeListsService $service)
    {
    }

    public function index(): Response
    {
        $state = Cache::get(self::STATE_KEY, [
            'listA' => '',
            'listB' => '',
            'result' => '',
        ]);

        return Inertia::render('MergeLists', [
            'listA' => $state['listA'],
            'listB' => $state['listB'],
            'result' => $state['result'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'originId' => 'nullable|string|max:128',
        ]);

        $state = Cache::get(self::STATE_KEY, [
            'listA' => '',
            'listB' => '',
            'result' => '',
        ]);

        try {
            $merged = $this->service->merge([
                $state['listA'] ?? null,
                $state['listB'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['lists' => $exception->getMessage()]);
        }

        $items = array_map(static function (string $text, int $index): array {
            return [
                'id' => sha1($text.'|'.$index),
                'text' => $text,
                'checked' => false,
            ];
        }, $merged, array_keys($merged));

        Cache::put(self::CHECKLIST_KEY, ['items' => $items], now()->addHour());

        try {
            event(new MergeChecklistReplaced(
                items: $items,
                originId: $validated['originId'] ?? null,
            ));
        } catch (Throwable $throwable) {
            report($throwable);
        }

        return redirect()->route('merge.checklist')->with('mergedList', $merged);
    }

    public function checklist(): Response
    {
        $state = Cache::get(self::CHECKLIST_KEY);

        if (! is_array($state) || ! isset($state['items']) || ! is_array($state['items'])) {
            $merged = session('mergedList') ?? [];
            $items = array_map(static function (string $text, int $index): array {
                return [
                    'id' => sha1($text.'|'.$index),
                    'text' => $text,
                    'checked' => false,
                ];
            }, $merged, array_keys($merged));

            $state = ['items' => $items];
            Cache::put(self::CHECKLIST_KEY, $state, now()->addHour());
        }

        return Inertia::render('Checklist', [
            'items' => $state['items'],
        ]);
    }
}
