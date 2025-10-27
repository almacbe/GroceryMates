<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\MergeListsRequest;
use App\Services\MergeListsService;
use Illuminate\Http\RedirectResponse;
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
        $state = session('merge_state', [
            'listA' => '',
            'listB' => '',
            'result' => '',
        ]);

        return Inertia::render('MergeLists', [
            'listA' => old('listA', $state['listA']),
            'listB' => old('listB', $state['listB']),
            'result' => $state['result'],
        ]);
    }

    public function store(MergeListsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        try {
            $merged = $this->service->merge([
                $data['listA'] ?? null,
                $data['listB'] ?? null,
            ]);
        } catch (InvalidArgumentException $exception) {
            return back()
                ->withInput()
                ->withErrors(['lists' => $exception->getMessage()]);
        }

        session()->flash('merge_state', [
            'listA' => $data['listA'] ?? '',
            'listB' => $data['listB'] ?? '',
            'result' => implode(PHP_EOL, $merged),
        ]);

        return redirect()->route('merge.index');
    }
}
