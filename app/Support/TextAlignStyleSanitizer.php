<?php

namespace App\Support;

use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;
use Symfony\Component\HtmlSanitizer\Visitor\AttributeSanitizer\AttributeSanitizerInterface;

/**
 * Atribut style hanya boleh berisi text-align (dipakai ekstensi TextAlign di TipTap).
 */
final class TextAlignStyleSanitizer implements AttributeSanitizerInterface
{
    public function getSupportedElements(): ?array
    {
        return null;
    }

    public function getSupportedAttributes(): ?array
    {
        return ['style'];
    }

    public function sanitizeAttribute(string $element, string $attribute, string $value, HtmlSanitizerConfig $config): ?string
    {
        if (preg_match('/text-align\s*:\s*(left|right|center|justify)\b/i', $value, $match) !== 1) {
            return null;
        }

        return 'text-align: '.strtolower($match[1]);
    }
}
