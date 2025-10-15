<?php

if (!function_exists('highlight')) {
    /**
     * Подсветка найденных совпадений в тексте.
     */
    function highlight($text, $needle)
    {
        if (!$needle) return e($text);

        return preg_replace(
            '/(' . preg_quote($needle, '/') . ')/iu',
            '<mark class="bg-yellow-200">$1</mark>',
            e($text)
        );
    }
}
