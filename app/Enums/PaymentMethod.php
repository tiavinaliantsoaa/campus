<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case Transfer = 'transfer';
    case Card = 'card';
    case MobileMoney = 'mobile_money';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Espèces',
            self::Transfer => 'Virement',
            self::Card => 'Carte',
            self::MobileMoney => 'Mobile money',
        };
    }
}
