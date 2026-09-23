<?php

namespace App\Enums;

/**
 * The points in a Malay wedding where somebody has to press play, in the order
 * they come on the day.
 */
enum SongMoment: string
{
    case Akad = 'akad';
    case Entrance = 'entrance';
    case MakanBeradab = 'makan_beradab';
    case PotongKek = 'potong_kek';
    case FirstWalk = 'first_walk';
    case Latar = 'latar';
    case Ending = 'ending';

    public function label(): string
    {
        return __('enums.song_moment.'.$this->value);
    }
}
