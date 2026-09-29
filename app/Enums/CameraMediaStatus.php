<?php

namespace App\Enums;

/** Where an upload is on its way into the album. Only Ready is ever shown. */
enum CameraMediaStatus: string
{
    /** Its place is counted; the file is on its way to storage. */
    case Reserved = 'reserved';

    /** The file arrived and is being checked and resized. */
    case Processing = 'processing';

    case Ready = 'ready';
    case Failed = 'failed';
}
