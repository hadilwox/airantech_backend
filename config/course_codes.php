<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Course Code Format
    |--------------------------------------------------------------------------
    |
    | The business course code is built as:
    |   {category.code_prefix}{jalali year, zero-padded}{jalali month, zero-padded}{sequence, zero-padded}
    |
    | The category prefix lives on the course_categories table (configurable
    | per category). The digit widths below control the year/month/sequence
    | segments only. Sequence numbers still grow past the configured width
    | if a category exceeds it in a single month (e.g. "100" instead of
    | erroring) — the width only controls the minimum zero-padding.
    |
    */

    'year_digits' => 4,

    'month_digits' => 2,

    'sequence_digits' => 2,

];
