<?php

namespace App\Domain\Projects\Enums;

enum ProjectStatus: int
{
    case WorkingCopy = 1;
    case Submitted = 2;
    case FormallyVerified = 3;
    case RecommendedWjo = 4;
    case Picked = 5;
    case RejectedFormally = -1;
    case RejectedWjo = -2;
    case RejectedZo = -3;
    case RejectedFinally = -4;
    case DuringFormalVerification = 10;
    case DuringInitialVerification = 11;
    case SentForMeritVerification = 12;
    case DuringMeritVerification = 13;
    case MeritVerificationAccepted = 14;
    case DuringTeamVerification = 15;
    case TeamAccepted = 16;
    case TeamRejected = 17;
    case TeamClosedVerification = 18;
    case TeamForReverification = 19;
    case DuringTeamRecallVerification = 20;
    case TeamRecallClosedVerification = 21;
    case TeamAfterReverification = 22;
    case PickedForRealization = 23;
    case DuringChangesSuggestion = 24;
    case ChangesSuggestionAccepted = 25;
    case Revoked = -10;
    case InitialVerificationRejected = -11;
    case MeritVerificationRejected = -12;
    case TeamRejectedWithRecall = -13;
    case TeamRejectedFinally = -14;

    public function adminLabel(): string
    {
        return match ($this) {
            self::WorkingCopy => 'kopia robocza (wnioskodawca)',
            self::Submitted => 'zgłoszony do Urzędu (wnioskodawca)',
            self::DuringFormalVerification => 'w trakcie weryfikacji formalnej (urzędnik BDO)',
            self::FormallyVerified => 'zweryfikowany formalnie (urzędnik BDO)',
            self::DuringInitialVerification => 'w trakcie weryfikacji wstępnej (urzędnik)',
            self::SentForMeritVerification => 'skierowany do weryfikacji merytorycznej (urzędnik)',
            self::DuringMeritVerification => 'w trakcie weryfikacji merytorycznej (urzędnik)',
            self::DuringChangesSuggestion => 'w trakcie akceptacji propozycji poprawy',
            self::ChangesSuggestionAccepted => 'poprawiony',
            self::MeritVerificationAccepted => 'zweryfikowany pozytywnie (urzędnik)',
            self::DuringTeamVerification => 'w trakcie weryfikacji zespołu społecznego',
            self::TeamAccepted => 'Rada SBO - zweryfikowany pozytywnie',
            self::TeamRejected => 'Rada SBO - zweryfikowany negatywnie',
            self::TeamClosedVerification => 'Rada SBO - głosowanie zamknięte bez rozstrzygnięcia',
            self::TeamForReverification => 'do ponownej weryfikacji',
            self::DuringTeamRecallVerification => 'w trakcie weryfikacji odwołania',
            self::TeamRecallClosedVerification => 'odwołanie - głosowanie zamknięte bez rozstrzygnięcia',
            self::TeamAfterReverification => 'po ponownej weryfikacji',
            self::Picked => 'na listę do głosowania',
            self::PickedForRealization => 'wybrany do realizacji',
            self::RejectedWjo => 'odrzucony w weryfikacji merytorycznej',
            self::RejectedFormally => 'odrzucony formalnie',
            self::InitialVerificationRejected => 'odrzucony w weryfikacji wstępnej',
            self::MeritVerificationRejected => 'zweryfikowany negatywnie',
            self::TeamRejectedWithRecall => 'odrzucony z możliwością odwołania',
            self::TeamRejectedFinally => 'odrzucony ostatecznie',
            self::RejectedZo => 'odrzucony przez Komisję Odwoławczą',
            self::RejectedFinally => 'odrzucony historycznie ostatecznie',
            self::Revoked => 'wycofany przez wnioskodawcę',
            self::RecommendedWjo => 'zarekomendowany przez W/JO',
        };
    }

    public function publicLabel(): string
    {
        return match ($this) {
            self::WorkingCopy => 'kopia robocza',
            self::Submitted => 'zgłoszony do Urzędu',
            self::DuringFormalVerification => 'w trakcie weryfikacji formalnej',
            self::FormallyVerified => 'zweryfikowany formalnie',
            self::DuringInitialVerification => 'w trakcie weryfikacji wstępnej',
            self::SentForMeritVerification => 'skierowany do weryfikacji merytorycznej',
            self::DuringMeritVerification => 'w trakcie weryfikacji merytorycznej',
            self::DuringChangesSuggestion => 'w trakcie akceptacji propozycji poprawy',
            self::ChangesSuggestionAccepted => 'poprawiony',
            self::MeritVerificationAccepted => 'zweryfikowany pozytywnie',
            self::DuringTeamVerification => 'w trakcie głosowania Rady lub Komisji',
            self::TeamAccepted => 'Rada SBO - zweryfikowany pozytywnie',
            self::TeamRejected => 'Rada SBO - zweryfikowany negatywnie',
            self::TeamClosedVerification => 'głosowanie zamknięte bez rozstrzygnięcia',
            self::TeamForReverification => 'do ponownej weryfikacji',
            self::DuringTeamRecallVerification => 'w trakcie weryfikacji odwołania',
            self::TeamRecallClosedVerification => 'odwołanie - głosowanie zamknięte bez rozstrzygnięcia',
            self::TeamAfterReverification => 'po ponownej weryfikacji',
            self::Picked => 'na listę do głosowania',
            self::PickedForRealization => 'wybrany do realizacji',
            self::RejectedWjo => 'odrzucony w weryfikacji merytorycznej',
            self::RejectedFormally => 'odrzucony formalnie',
            self::InitialVerificationRejected => 'odrzucony w weryfikacji wstępnej',
            self::MeritVerificationRejected => 'zweryfikowany negatywnie',
            self::TeamRejectedWithRecall => 'odrzucony z możliwością odwołania',
            self::TeamRejectedFinally => 'odrzucony ostatecznie',
            self::RejectedZo => 'odrzucony przez Komisję Odwoławczą',
            self::RejectedFinally => 'odrzucony ostatecznie',
            self::Revoked => 'wycofany przez wnioskodawcę',
            self::RecommendedWjo => 'zarekomendowany przez W/JO',
        };
    }

    public function isRejected(): bool
    {
        return in_array($this, [
            self::RejectedFormally,
            self::InitialVerificationRejected,
            self::MeritVerificationRejected,
            self::TeamRejectedWithRecall,
            self::TeamRejectedFinally,
            self::TeamRejected,
            self::RejectedWjo,
            self::RejectedZo,
            self::RejectedFinally,
        ], true);
    }
}
