<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function joinUrl(string $roomCode): string
    {
        return url('/student/join?code='.$roomCode);
    }

    public function svg(string $payload, int $size = 256): string
    {
        return QrCode::format('svg')->size($size)->generate($payload);
    }

    public function saveSvg(string $payload, string $relativePath, int $size = 256): string
    {
        $svg = $this->svg($payload, $size);
        Storage::disk('public')->put($relativePath, $svg);

        return $relativePath;
    }
}
