<?php

namespace App\Domain\Users\Services;

use App\Domain\Users\Models\Department;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LegacyUserImportService
{
    /**
     * @param  array<string, list<array<string, mixed>>>  $payload
     * @return array<string, int>
     */
    public function import(array $payload): array
    {
        Log::info('legacy_user_import.start', [
            'tables_count' => count($payload),
        ]);

        return DB::transaction(function () use ($payload): array {
            $stats = [
                'departments' => $this->importDepartments($payload['departments'] ?? []),
                'users' => $this->importUsers($payload['users'] ?? []),
            ];

            Log::info('legacy_user_import.success', [
                'entries_count' => array_sum($stats),
            ]);

            return $stats;
        });
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importDepartments(array $rows): int
    {
        foreach ($rows as $row) {
            Department::query()->updateOrCreate([
                'legacy_id' => (int) Arr::get($row, 'id'),
            ], [
                'name' => Arr::get($row, 'name'),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function importUsers(array $rows): int
    {
        $passwordHash = Hash::make(Str::random(40));

        foreach ($rows as $row) {
            $legacyId = (int) Arr::get($row, 'id');
            $departmentId = $this->departmentId(Arr::get($row, 'departmentId'));
            $email = $this->emailFor($row);

            User::query()->updateOrCreate([
                'legacy_id' => $legacyId,
            ], [
                'name' => Arr::get($row, 'username', $email),
                'email' => $email,
                'password' => $passwordHash,
                'status' => (bool) Arr::get($row, 'status', true),
                'pesel' => Arr::get($row, 'pesel'),
                'first_name' => Arr::get($row, 'firstName'),
                'last_name' => Arr::get($row, 'lastName'),
                'phone' => Arr::get($row, 'phone'),
                'street' => Arr::get($row, 'street'),
                'house_no' => Arr::get($row, 'houseNo'),
                'flat_no' => Arr::get($row, 'flatNo'),
                'post_code' => Arr::get($row, 'postCode'),
                'city' => Arr::get($row, 'city'),
                'department_id' => $departmentId,
                'department_text' => Arr::get($row, 'departmentText'),
            ]);
        }

        return count($rows);
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function emailFor(array $row): string
    {
        $legacyId = (int) Arr::get($row, 'id');
        $email = trim((string) Arr::get($row, 'email', ''));

        if ($email === '*') {
            return 'deleted-'.$legacyId.'@anonymous.local';
        }

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return 'legacy-user-'.$legacyId.'@invalid.local';
        }

        if ($this->emailBelongsToAnotherLegacyUser($email, $legacyId)) {
            return 'legacy-user-'.$legacyId.'@duplicate.local';
        }

        return $email;
    }

    private function emailBelongsToAnotherLegacyUser(string $email, int $legacyId): bool
    {
        return User::query()
            ->where('email', $email)
            ->where('legacy_id', '!=', $legacyId)
            ->exists();
    }

    private function departmentId(mixed $legacyDepartmentId): ?int
    {
        if ($legacyDepartmentId === null || $legacyDepartmentId === '') {
            return null;
        }

        return Department::query()
            ->where('legacy_id', (int) $legacyDepartmentId)
            ->value('id');
    }
}
