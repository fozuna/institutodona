<?php
namespace App\Core;

final class PdfSupport
{
    public static function isDompdfAvailable(): bool
    {
        if ((string)getenv('PDF_FORCE_MISSING') === '1') {
            return false;
        }
        return class_exists(\Dompdf\Dompdf::class) && class_exists(\Dompdf\Options::class);
    }

    public static function missingDompdfMessage(): string
    {
        return 'Geração de PDF indisponível no momento. Tente novamente em alguns minutos.';
    }
}

