<?php

namespace App\Services;

use App\Models\CourseCategory;
use App\Models\CourseCodeSequence;
use Illuminate\Support\Facades\DB;
use Morilog\Jalali\Jalalian;

class CourseCodeGenerator
{
    /**
     * Generate the next unique business course code for a category, using
     * the current Jalali year/month. This is the only place course codes
     * are produced — never accept one from a request.
     *
     * @return array{code: string, jalali_year: int, jalali_month: int}
     */
    public function generate(CourseCategory $category): array
    {
        $now = Jalalian::now();
        $year = $now->getYear();
        $month = $now->getMonth();

        $sequence = DB::transaction(function () use ($category, $year, $month): int {
            // createOrFirst (not firstOrCreate) so two concurrent requests
            // racing to insert the first row for this category/month can't
            // both succeed — one wins the insert, the other safely re-reads.
            CourseCodeSequence::query()->createOrFirst(
                attributes: [
                    'category_id' => $category->id,
                    'jalali_year' => $year,
                    'jalali_month' => $month,
                ],
                values: ['last_sequence' => 0],
            );

            // The row is now guaranteed to exist — lock it for the increment
            // so concurrent requests serialize instead of losing an update.
            $row = CourseCodeSequence::query()
                ->where('category_id', $category->id)
                ->where('jalali_year', $year)
                ->where('jalali_month', $month)
                ->lockForUpdate()
                ->firstOrFail();

            $row->last_sequence++;
            $row->save();

            return $row->last_sequence;
        });

        $code = $category->code_prefix
            .str_pad((string) $year, config('course_codes.year_digits'), '0', STR_PAD_LEFT)
            .str_pad((string) $month, config('course_codes.month_digits'), '0', STR_PAD_LEFT)
            .str_pad((string) $sequence, config('course_codes.sequence_digits'), '0', STR_PAD_LEFT);

        return [
            'code' => $code,
            'jalali_year' => $year,
            'jalali_month' => $month,
        ];
    }
}
