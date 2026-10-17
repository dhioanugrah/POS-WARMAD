<?php

namespace App\Support;

class CodeGenerator
{
    /**
     * Generate the next sequential code like "PLG-0001" for a model/column.
     */
    public static function next(string $modelClass, string $prefix, int $padding = 4): string
    {
        $last = $modelClass::query()
            ->where('code', 'like', "{$prefix}-%")
            ->orderByDesc('id')
            ->value('code');

        $next = $last ? ((int) substr($last, strlen($prefix) + 1)) + 1 : 1;

        return sprintf('%s-%0'.$padding.'d', $prefix, $next);
    }
}
