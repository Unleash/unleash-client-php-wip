<?php

if (!function_exists('normalizeValue')) {
    function normalizeValue($id, $groupId, $normalizer = 100)
    {
        return murmurhash3_int($groupId . ':' . $id) % $normalizer + 1;
    }
}
