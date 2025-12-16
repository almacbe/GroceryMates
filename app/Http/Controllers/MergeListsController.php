<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MergeListsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;
use InvalidArgumentException;

class MergeListsController extends Controller
{
    public function __construct(private readonly MergeListsService $service)
    {
    }

    public function index(): Response
    {
        $state = Cache::get('merge_state', [
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

    public function store(): RedirectResponse
    {
        $state = Cache::get('merge_state', [
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

        $state['result'] = implode(PHP_EOL, $merged);

        Cache::put('merge_state', $state, now()->addHour());

        return redirect()->route('merge.index');
    }
}
