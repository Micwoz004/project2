<?php

namespace App\Domain\Voting\Services;

class VoterHashService
{
    private const SALT = 'D0FB5FC74E';

    public function legacyHash(
        string $pesel,
        string $firstName,
        string $lastName,
        string $motherLastName,
    ): string {
        return md5(
            $pesel
            .$this->normalizeName($firstName)
            .$this->normalizeName($lastName)
            .$this->normalizeName($motherLastName)
            .self::SALT
        );
    }

    public function legacyLookupHash(
        string $pesel,
        string $firstName,
        string $lastName,
        string $motherLastName,
    ): string {
        return strtoupper($this->legacyHash($pesel, $firstName, $lastName, $motherLastName));
    }

    public function normalizeName(string $value): string
    {
        $value = strtr($value, [
            'ą' => 'a',
            'ć' => 'c',
            'ę' => 'e',
            'ł' => 'l',
            'ń' => 'n',
            'ó' => 'o',
            'ś' => 's',
            'ż' => 'z',
            'ź' => 'z',
            'Ą' => 'A',
            'Ć' => 'C',
            'Ę' => 'E',
            'Ł' => 'L',
            'Ń' => 'N',
            'Ó' => 'O',
            'Ś' => 'S',
            'Ż' => 'Z',
            'Ź' => 'Z',
        ]);

        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $value = is_string($transliterated) ? $transliterated : $value;
        $value = str_replace([
            "\r\n",
            "\r",
            "\n",
            "\t",
            "\0",
            "\x0B",
            '\r\n',
            '\r',
            '\n',
            '\t',
            '\0',
            '\x0B',
            ' ',
            '-',
            '_',
        ], '', $value);

        return mb_strtoupper($value);
    }
}
