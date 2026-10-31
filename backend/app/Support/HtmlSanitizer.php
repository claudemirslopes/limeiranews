<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Minimal HTML sanitizer for editorial content.
 *
 * Whitelists tags with strip_tags() and then removes every attribute that is
 * not explicitly allowed (this drops on* handlers, style, etc.) and unsafe URLs.
 */
class HtmlSanitizer
{
    public const ALLOWED_TAGS = '<p><br><strong><em><ul><ol><li><h2><h3><blockquote><a><img><figure><figcaption>';

    /** @var array<string, list<string>> */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
    ];

    public static function clean(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // Remove script/style blocks entirely (strip_tags would keep their text content).
        $html = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $html) ?? '';
        $html = strip_tags($html, self::ALLOWED_TAGS);

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><div id="sanitizer-root">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $xpath = new DOMXPath($dom);
        /** @var \DOMNodeList<DOMElement> $elements */
        $elements = $xpath->query('//*[@id="sanitizer-root"]//*');

        foreach ($elements as $element) {
            self::cleanAttributes($element);
        }

        $root = $dom->getElementById('sanitizer-root') ?? $xpath->query('//*[@id="sanitizer-root"]')->item(0);
        if ($root === null) {
            return trim($html);
        }

        $output = '';
        foreach ($root->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return trim($output);
    }

    private static function cleanAttributes(DOMElement $element): void
    {
        $allowed = self::ALLOWED_ATTRIBUTES[strtolower($element->tagName)] ?? [];

        foreach (iterator_to_array($element->attributes) as $attribute) {
            $name = strtolower($attribute->name);

            if (! in_array($name, $allowed, true)) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if (in_array($name, ['href', 'src'], true) && ! self::isSafeUrl($attribute->value, $name === 'src')) {
                $element->removeAttribute($attribute->name);
            }
        }

        if (strtolower($element->tagName) === 'a' && $element->getAttribute('target') === '_blank') {
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    private static function isSafeUrl(string $url, bool $isImage): bool
    {
        $url = trim(preg_replace('/[\x00-\x20]+/', '', $url) ?? '');

        if ($url === '' || str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        $schemes = $isImage ? ['http', 'https'] : ['http', 'https', 'mailto', 'tel'];
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return $scheme === '' ? ! str_contains($url, ':') : in_array($scheme, $schemes, true);
    }
}
