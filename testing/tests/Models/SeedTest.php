<?php

use App\Models\Seed;

it('can retrieve seeds using eloquent methods', function () {
    $seeds = Seed::published()->get();

    expect($seeds)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
        ->each->toBeInstanceOf(\App\Models\Seed::class);
});

it('recent seeds method returns collection with limit', function () {
    $recentSeeds = Seed::recent(3)->get();

    expect($recentSeeds)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
        ->and($recentSeeds->count())->toBeLessThanOrEqual(3);
});

it('seed model has expected methods', function () {
    $seed = new Seed();

    expect(method_exists($seed, 'categories'))->toBeTrue()
        ->and(method_exists($seed, 'nextSeed'))->toBeTrue()
        ->and(method_exists($seed, 'previousSeed'))->toBeTrue();
});
