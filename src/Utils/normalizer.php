<?php

if (!function_exists('normalizeValue')) {
    function normalizeValue(string $id, string $groupId, $normalizer = 100): int
    {
        return \lastguest\Murmur::hash3_int($groupId . ':' . $id) % $normalizer + 1;
    }
}