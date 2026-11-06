<?php

namespace App\Support;

/**
 * Prototype only: the role picked on the sign-in form, and the dashboard it opens.
 * The keys match the people in extrack.js (ed, asst, cd, cdasst, pres).
 */
final class PrototypeRole
{
    public const SESSION_KEY = 'prototype_role';

    public const EXTENSION_DIRECTOR = 'ed';

    public const LABELS = [
        'ed' => 'Extension Director',
        'asst' => "Extension Director's Assistant",
        'cd' => 'Campus Director',
        'cdasst' => "Campus Director's Assistant",
        'pres' => 'University President',
    ];

    /** Short names for the role tabs on the sign-in form. */
    public const TAB_LABELS = [
        'ed' => 'Extension Director',
        'asst' => 'Extension Assistant',
        'cd' => 'Campus Director',
        'cdasst' => 'Campus Assistant',
        'pres' => 'President',
    ];

    /** The dashboard route for each role; assistants use their director's dashboard. */
    private const ROUTES = [
        'ed' => 'prototype.extension-director',
        'asst' => 'prototype.extension-director',
        'cd' => 'prototype.campus-director',
        'cdasst' => 'prototype.campus-director',
        'pres' => 'prototype.president',
    ];

    public static function valid(?string $role): string
    {
        return $role !== null && isset(self::LABELS[$role]) ? $role : self::EXTENSION_DIRECTOR;
    }

    public static function current(): ?string
    {
        $role = session(self::SESSION_KEY);

        return is_string($role) && isset(self::LABELS[$role]) ? $role : null;
    }

    public static function route(?string $role): string
    {
        return $role !== null ? (self::ROUTES[$role] ?? 'prototype.index') : 'prototype.index';
    }
}
