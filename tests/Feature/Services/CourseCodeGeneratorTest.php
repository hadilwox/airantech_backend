<?php

use App\Models\CourseCategory;
use App\Services\CourseCodeGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Morilog\Jalali\Jalalian;

uses(RefreshDatabase::class);

it('generates a code in prefix+year+month+sequence format', function () {
    $category = CourseCategory::factory()->create(['code_prefix' => '3']);

    $result = app(CourseCodeGenerator::class)->generate($category);

    $now = Jalalian::now();
    $expectedYear = str_pad((string) $now->getYear(), 4, '0', STR_PAD_LEFT);
    $expectedMonth = str_pad((string) $now->getMonth(), 2, '0', STR_PAD_LEFT);

    expect($result['code'])->toBe("3{$expectedYear}{$expectedMonth}01");
    expect($result['jalali_year'])->toBe($now->getYear());
    expect($result['jalali_month'])->toBe($now->getMonth());
});

it('increments the sequence within the same category and month', function () {
    $category = CourseCategory::factory()->create(['code_prefix' => '5']);
    $generator = app(CourseCodeGenerator::class);

    $first = $generator->generate($category);
    $second = $generator->generate($category);
    $third = $generator->generate($category);

    expect($first['code'])->toEndWith('01');
    expect($second['code'])->toEndWith('02');
    expect($third['code'])->toEndWith('03');
});

it('keeps sequences independent per category', function () {
    $categoryA = CourseCategory::factory()->create(['code_prefix' => '1']);
    $categoryB = CourseCategory::factory()->create(['code_prefix' => '2']);
    $generator = app(CourseCodeGenerator::class);

    $generator->generate($categoryA);
    $resultB = $generator->generate($categoryB);

    expect($resultB['code'])->toEndWith('01');
    expect($resultB['code'])->toStartWith('2');
});

it('produces unique codes across many rapid generations for the same category', function () {
    $category = CourseCategory::factory()->create(['code_prefix' => '7']);
    $generator = app(CourseCodeGenerator::class);

    $codes = collect(range(1, 15))->map(fn () => $generator->generate($category)['code']);

    expect($codes->unique())->toHaveCount(15);
});
