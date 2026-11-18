<?php

namespace App\Enums;

enum Role: string
{
    case President = 'president';
    case CampusUploader = 'campus_uploader';
    case CampusDirector = 'campus_director';

    public function label(): string
    {
        return config("extrack.role_labels.{$this->value}") ?? match ($this) {
            self::President => 'University President',
            self::CampusUploader => 'Campus Uploader',
            self::CampusDirector => 'Campus Director',
        };
    }
}
