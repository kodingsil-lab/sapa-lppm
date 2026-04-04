<?php

declare(strict_types=1);

use CodeIgniter\Validation\StrictRules\FormatRules;
use CodeIgniter\Validation\StrictRules\Rules;
use CodeIgniter\Validation\Validation;
use CodeIgniter\View\RendererInterface;
use CodeIgniter\Helpers\Array\ArrayHelper;

if (!function_exists('bootstrapCiValidationRuntime')) {
    function bootstrapCiValidationRuntime(): void
    {
        require_once __DIR__ . '/CiValidationNamespaceShim.php';
    }
}

if (!function_exists('ciValidator')) {
    function ciValidator(): Validation
    {
        static $validator = null;
        if ($validator instanceof Validation) {
            return $validator;
        }

        bootstrapCiValidationRuntime();

        $config = (object) [
            'ruleSets' => [
                Rules::class,
                FormatRules::class,
            ],
        ];

        $view = new class () implements RendererInterface {
            public function render(string $view, ?array $options = null, bool $saveData = false): string
            {
                return '';
            }

            public function renderString(string $view, ?array $options = null, bool $saveData = false): string
            {
                return '';
            }

            public function setData(array $data = [], ?string $context = null)
            {
                return $this;
            }

            public function setVar(string $name, $value = null, ?string $context = null)
            {
                return $this;
            }

            public function resetData()
            {
                return $this;
            }
        };

        $validator = new Validation($config, $view);
        return $validator;
    }
}

if (!function_exists('ciValidateData')) {
    /**
     * @return array{valid: bool, errors: array<string, string>, validated: array<string, mixed>}
     */
    function ciValidateData(array $data, array $rules, array $messages = []): array
    {
        $validator = ciValidator();
        $validator->reset();
        $validator->setRules($rules, $messages);
        $isValid = $validator->run($data);

        return [
            'valid' => $isValid,
            'errors' => $validator->getErrors(),
            'validated' => $validator->getValidated(),
        ];
    }
}
