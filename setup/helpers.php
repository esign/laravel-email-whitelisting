<?php

if (! function_exists('str_studly')) {
    function str_studly(string $value): string
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
    
        return str_replace(' ', '', $value);
    }
}

if (! function_exists('str_before_last')) {
    function str_before_last(string $subject, string $search): string
    {
        if ($search === '') {
            return $subject;
        }

        $pos = mb_strrpos($subject, $search);

        if ($pos === false) {
            return $subject;
        }

        return mb_substr($subject, 0, $pos, 'UTF-8');
    }
}

if (! function_exists('str_after')) {
    function str_after(string $subject, string $search): string
    {
        return $search === '' ? $subject : array_reverse(explode($search, $subject, 2))[0];
    }
}