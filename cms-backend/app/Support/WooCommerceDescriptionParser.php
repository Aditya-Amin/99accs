<?php

namespace App\Support;

/**
 * Minimal cleanup for WooCommerce HTML descriptions.
 *
 * We do NOT split into sections / extract highlights / parse FAQ here.
 * The WC editor produces too many shapes for an automated parser to be
 * correct, and the admin can fill structured fields manually for the
 * products that benefit from rich layouts.
 *
 * We just:
 *   - Normalize "\n" escape sequences to real newlines
 *   - Strip noisy empty class="" attributes the WC editor leaves behind
 *   - Trim whitespace
 */
class WooCommerceDescriptionParser
{
    /** @return array{description: string|null} */
    public function parse(string $rawHtml): array
    {
        $rawHtml = trim($rawHtml);
        if ($rawHtml === '') {
            return ['description' => null];
        }

        $html = str_replace(['\\n', "\r\n", "\r"], "\n", $rawHtml);
        $html = preg_replace('/\s+class=""+\w*"?/u', '', $html) ?? $html;
        $html = preg_replace('/\s+class=""/u', '', $html) ?? $html;
        $html = preg_replace('/\n{3,}/u', "\n\n", $html) ?? $html;

        return ['description' => trim($html)];
    }
}
