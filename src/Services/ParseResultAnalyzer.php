<?php

namespace App\Services;

class ParseResultAnalyzer
{
    public static function getAnalysis(array $parseResult): array
    {
        if (!isset($parseResult['error'])) {
            return ['check' => 'success'];
        }

        if (!array_key_exists('status_code', $parseResult)) {
            return [
                'check' => 'danger',
                'message' => 'Произошла ошибка при проверке, не удалось подключиться'
            ];
        }

        return [
            'check' => 'warning',
            'message' => 'Проверка была выполнена, но сервер ответил с ошибкой'
        ];
    }
}
