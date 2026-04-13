<?php

namespace Analyzer;

use Carbon\Carbon;

class TimeNormalizer
{
    public function normalizeTime(array $array,  string $format = 'Y-m-d'): array
    {
        if (isset($array['created_at'])) {
            $array['created_at'] = Carbon::parse($array['created_at'])->format($format);
            return $array;
        }

        return array_map(function ($item) use ($format) {
            if (isset($item['created_at'])) {
                $item['created_at'] = Carbon::parse($item['created_at'])->setTimezone('Europe/Moscow')->format($format);
            }
            return $item;
        }, $array);
    }
}
