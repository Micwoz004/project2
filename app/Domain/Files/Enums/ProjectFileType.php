<?php

namespace App\Domain\Files\Enums;

enum ProjectFileType: int
{
    case SupportList = 1;
    case OwnerAgreement = 2;
    case Other = 3;
    case Map = 4;
    case ParentAgreement = 5;
    case AppealAgainstDecision = 10;

    public const LEGACY_MAX_FILE_SIZE_BYTES = 1024 * 1024 * 10000;

    /**
     * @return list<string>
     */
    public static function legacyAllowedExtensions(): array
    {
        return [
            'doc',
            'docx',
            'rtf',
            'xls',
            'txt',
            'jpg',
            'png',
            'bmp',
            'gif',
            'tif',
            'pdf',
            'pptx',
        ];
    }

    public function maxFiles(): int
    {
        return match ($this) {
            self::Other => 10,
            self::SupportList,
            self::OwnerAgreement,
            self::Map,
            self::ParentAgreement,
            self::AppealAgainstDecision => 5,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SupportList => 'Lista poparcia',
            self::OwnerAgreement => 'Zgoda właściciela',
            self::Other => 'Inny załącznik',
            self::Map => 'Mapa',
            self::ParentAgreement => 'Zgoda rodzica/opiekuna',
            self::AppealAgainstDecision => 'Odwołanie od decyzji',
        };
    }
}
