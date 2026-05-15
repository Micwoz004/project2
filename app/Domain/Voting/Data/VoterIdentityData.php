<?php

namespace App\Domain\Voting\Data;

class VoterIdentityData
{
    public function __construct(
        public readonly string $pesel,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $motherLastName,
        public readonly ?string $secondName = null,
        public readonly ?string $fatherName = null,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?string $ip = null,
        public readonly ?string $userAgent = null,
        public readonly bool $noPeselNumber = false,
    ) {}
}
