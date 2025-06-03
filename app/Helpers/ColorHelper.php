<?php

namespace App\Helpers;

class ColorHelper
{
    /**
     * Determina el color de texto contrastante
     */
    public static function getContrastColor($hexColor)
    {
        $hexColor = ltrim($hexColor, '#');

        // Manejar colores de 3 dígitos
        if (strlen($hexColor) == 3) {
            $hexColor = $hexColor[0].$hexColor[0].$hexColor[1].$hexColor[1].$hexColor[2].$hexColor[2];
        }

        // Convertir a valores RGB
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        // Calcular luminancia (fórmula WCAG)
        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b) / 255;

        return $luminance > 0.5 ? 'text-dark' : 'text-white';
    }

    /**
     * Convierte HEX a RGBA con transparencia
     */
    public static function hexToRgba($hexColor, $alpha = 1.0)
    {
        $hexColor = ltrim($hexColor, '#');

        if (strlen($hexColor) == 3) {
            $hexColor = $hexColor[0].$hexColor[0].$hexColor[1].$hexColor[1].$hexColor[2].$hexColor[2];
        }

        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        return "rgba($r, $g, $b, $alpha)";
    }

    /**
     * Oscurece un color hexadecimal
     * @param string $hexColor Color en formato HEX (con o sin #)
     * @param int $percent Porcentaje a oscurecer (0-100)
     * @return string Color HEX oscurecido
     */
    public static function darkenColor($hexColor, $percent = 20)
    {
        $hexColor = ltrim($hexColor, '#');

        if (strlen($hexColor) == 3) {
            $hexColor = $hexColor[0].$hexColor[0].$hexColor[1].$hexColor[1].$hexColor[2].$hexColor[2];
        }

        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));

        // Calcular el factor de oscurecimiento
        $factor = 1 - ($percent / 100);

        $r = max(0, min(255, round($r * $factor)));
        $g = max(0, min(255, round($g * $factor)));
        $b = max(0, min(255, round($b * $factor)));

        // Convertir de nuevo a HEX
        $darkened = sprintf("%02x%02x%02x", $r, $g, $b);

        return '#'.$darkened;
    }
}
