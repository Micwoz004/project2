<?php

namespace App\Domain\Settings\Services;

use App\Domain\Settings\Models\ApplicationSetting;

class ApplicationSettings
{
    public function string(string $category, string $key, ?string $default = null): ?string
    {
        $value = $this->raw($category, $key);

        return $value ?? $default;
    }

    public function integer(string $category, string $key, ?int $default = null): ?int
    {
        $value = $this->raw($category, $key);

        if ($value === null || ! is_numeric($value)) {
            return $default;
        }

        return (int) $value;
    }

    public function boolean(string $category, string $key, ?bool $default = null): ?bool
    {
        $value = $this->raw($category, $key);

        if ($value === null) {
            return $default;
        }

        $normalized = strtolower(trim($value));

        return match ($normalized) {
            '1', 'true', 'yes', 'tak', 'on' => true,
            '0', 'false', 'no', 'nie', 'off' => false,
            default => $default,
        };
    }

    private function raw(string $category, string $key): ?string
    {
        return ApplicationSetting::query()
            ->where('category', $category)
            ->where('key', $key)
            ->value('value');
    }
}
