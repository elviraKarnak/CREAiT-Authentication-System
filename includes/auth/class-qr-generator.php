<?php

if (!defined('ABSPATH')) {
    exit;
}

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;

class CAS_QR_Generator {

    public static function generate($text) {

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );

        $writer = new Writer($renderer);

        $qr = $writer->writeString($text);

        return 'data:image/svg+xml;base64,' . base64_encode($qr);

    }

}