<?php

namespace App\Enums;

enum ServiceState: string
{
    case Draft = 'draft';
    case Published = 'published';
}
