<?php

namespace App\Services;

use App\Models\Classroom;
use Illuminate\Support\Str;

class RoomCodeService
{
    public function generate(int $length = 6): string
    {
        do {
            $code = strtoupper(Str::random($length));
            $code = preg_replace('/[^A-Z0-9]/', '', $code) ?? '';
            while (strlen($code) < $length) {
                $code .= strtoupper(Str::random(1));
                $code = preg_replace('/[^A-Z0-9]/', '', $code) ?? '';
            }
            $code = substr($code, 0, $length);
        } while (Classroom::where('room_code', $code)->exists());

        return $code;
    }
}
