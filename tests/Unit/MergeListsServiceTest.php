<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\MergeListsService;
use Tests\TestCase;

class MergeListsServiceTest extends TestCase
{
    public function test_it_appends_household_measures_for_gram_quantities(): void
    {
        $service = new MergeListsService();

        $result = $service->merge([
            "[ ] Tomate: 150g",
        ]);

        $this->assertSame(
            ['Tomate: 150g (10 cucharadas o 0.63 tazas)'],
            $result,
        );
    }

    public function test_it_converts_huevo_crudo_to_egg_counts(): void
    {
        $service = new MergeListsService();

        $result = $service->merge([
            "[ ] Huevo Crudo: 120g",
        ]);

        $this->assertSame(
            ['Huevo Crudo: 120g (2 huevos)'],
            $result,
        );
    }
}
