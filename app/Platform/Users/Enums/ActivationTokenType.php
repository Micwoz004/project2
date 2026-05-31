<?php

namespace App\Platform\Users\Enums;

enum ActivationTokenType: int
{
    case AccountActivationEmail = 1;
    case AccountActivationSms = 2;
    case PasswordReset = 3;
}
