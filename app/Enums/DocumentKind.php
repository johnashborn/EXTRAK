<?php

namespace App\Enums;

enum DocumentKind: string
{
    case AccomplishmentReport = 'accomplishment_report';
    case Attendance = 'attendance';
    case Photo = 'photo';
    case Letter = 'letter';
    case Proposal = 'proposal';
    case Moa = 'moa';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::AccomplishmentReport => 'Accomplishment report',
            self::Attendance => 'Attendance sheet',
            self::Photo => 'Photo',
            self::Letter => 'Letter',
            self::Proposal => 'Proposal',
            self::Moa => 'Memorandum of agreement',
            self::Other => 'Other',
        };
    }
}
