<?php

namespace App\Enums;

/** A guest's wish in a Neekah Kenangan album: written on every tier, spoken on Pro. */
enum CameraWishType: string
{
    case Text = 'text';
    case Voice = 'voice';
}
