<?php

namespace App\Validators;

class UrlValidator
{
    public static function validate(string $url): array
    {
        $errors = [];
        if (empty(trim($url))) {
            $errors['name'] = 'URL не должен быть пустым';
            return $errors;
        }

        if (strlen($url) > 255) {
            $errors['name'] = 'Некорректный URL';
            return $errors;
        }

        if (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('~^(https?|ftp)://~i', $url)) {
            $errors['name'] = 'Некорректный URL';
        }
        return $errors;
    }
}
