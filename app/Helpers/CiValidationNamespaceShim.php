<?php

declare(strict_types=1);

namespace {
    use CodeIgniter\Helpers\Array\ArrayHelper;

    if (!function_exists('dot_array_search')) {
        function dot_array_search(string $index, array $array)
        {
            return ArrayHelper::dotSearch($index, $array);
        }
    }

    if (!function_exists('array_flatten_with_dots')) {
        function array_flatten_with_dots(iterable $array, string $id = ''): array
        {
            $flattened = [];

            foreach ($array as $key => $value) {
                $newKey = $id . $key;
                if (is_array($value) && $value !== []) {
                    $flattened = array_merge($flattened, array_flatten_with_dots($value, $newKey . '.'));
                } else {
                    $flattened[$newKey] = $value;
                }
            }

            return $flattened;
        }
    }

    if (!function_exists('helper')) {
        function helper($filenames): void
        {
            $items = is_array($filenames) ? $filenames : [$filenames];
            foreach ($items as $filename) {
                $name = strtolower(trim((string) $filename));
                if ($name === 'array' || $name === 'array_helper') {
                    require_once __DIR__ . '/../../vendor/codeigniter4/framework/system/Helpers/array_helper.php';
                }
            }
        }
    }

    if (!function_exists('lang')) {
        function lang(string $line, array $args = []): string
        {
            $messages = [
                'Validation.required' => '{field} wajib diisi.',
                'Validation.valid_email' => '{field} harus berupa email yang valid.',
                'Validation.min_length' => '{field} minimal {param} karakter.',
                'Validation.max_length' => '{field} maksimal {param} karakter.',
                'Validation.matches' => '{field} harus sama dengan {param}.',
                'Validation.exact_length' => '{field} harus {param} karakter.',
                'Validation.integer' => '{field} harus berupa angka bulat.',
                'Validation.numeric' => '{field} harus berupa angka.',
                'Validation.in_list' => '{field} berisi nilai yang tidak diizinkan.',
                'Validation.regex_match' => 'Format {field} tidak valid.',
                'Validation.alpha_numeric' => '{field} hanya boleh huruf dan angka.',
                'Validation.permit_empty' => '',
            ];

            $template = $messages[$line] ?? $line;
            foreach ($args as $key => $value) {
                $template = str_replace('{' . (string) $key . '}', (string) $value, $template);
            }

            return $template;
        }
    }
}

namespace CodeIgniter\Validation {
    if (!function_exists(__NAMESPACE__ . '\dot_array_search')) {
        function dot_array_search(string $index, array $array)
        {
            return \dot_array_search($index, $array);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\array_flatten_with_dots')) {
        function array_flatten_with_dots(iterable $array, string $id = ''): array
        {
            return \array_flatten_with_dots($array, $id);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\helper')) {
        function helper($filenames): void
        {
            \helper($filenames);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\lang')) {
        function lang(string $line, array $args = []): string
        {
            return \lang($line, $args);
        }
    }
}

namespace CodeIgniter\Validation\StrictRules {
    if (!function_exists(__NAMESPACE__ . '\helper')) {
        function helper($filenames): void
        {
            \helper($filenames);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\lang')) {
        function lang(string $line, array $args = []): string
        {
            return \lang($line, $args);
        }
    }

    if (!function_exists(__NAMESPACE__ . '\service')) {
        function service(string $name, ...$params)
        {
            if (\function_exists('service')) {
                return \service($name, ...$params);
            }

            return null;
        }
    }
}
