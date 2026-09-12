<?php

namespace App\Services;

class HtmlSanitizerService
{
    /**
     * Tags that are completely dangerous and must be removed along with their content.
     */
    private const STRIP_TAGS = [
        'script',
        'iframe',
        'object',
        'embed',
        'applet',
        'form',
        'input',
        'button',
        'textarea',
        'select',
        'link',
        'meta',
        'base',
    ];

    /**
     * Tags that are allowed in email presentation.
     */
    private const ALLOWED_TAGS = [
        'p', 'div', 'span', 'br', 'hr',
        'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'b', 'strong', 'i', 'em', 'u', 's', 'strike', 'sub', 'sup', 'small',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption', 'colgroup', 'col',
        'ul', 'ol', 'li', 'dl', 'dt', 'dd',
        'blockquote', 'pre', 'code',
        'a', 'img', 'font', 'center',
    ];

    /**
     * Allowed attributes on elements.
     */
    private const ALLOWED_ATTRIBUTES = [
        'class', 'id', 'style', 'title', 'align', 'valign', 'width', 'height',
        'border', 'cellpadding', 'cellspacing', 'bgcolor', 'color', 'colspan', 'rowspan',
        'href', 'src', 'alt', 'target', 'rel',
    ];

    /**
     * Sanitize raw email HTML string.
     */
    public function sanitize(?string $html): string
    {
        if ($html === null || trim($html) === '') {
            return '';
        }

        // Quick cleanup of null bytes and weird control characters
        $html = str_replace(chr(0), '', $html);

        // Pre-strip dangerous script and iframe blocks before DOM parsing
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);

        $libxmlState = libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');

        // Wrap in a UTF-8 container to avoid encoding mangling
        $encodedHtml = '<?xml encoding="utf-8" ?><div>'.$html.'</div>';
        $dom->loadHTML($encodedHtml, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        libxml_use_internal_errors($libxmlState);

        $xpath = new \DOMXPath($dom);

        // 1. Remove dangerous nodes completely
        foreach (self::STRIP_TAGS as $tagName) {
            $nodes = $xpath->query('//'.$tagName);
            if ($nodes) {
                foreach ($nodes as $node) {
                    $node->parentNode?->removeChild($node);
                }
            }
        }

        // 2. Inspect all remaining elements
        $allElements = $dom->getElementsByTagName('*');
        $elementsToStrip = [];

        for ($i = $allElements->length - 1; $i >= 0; $i--) {
            /** @var \DOMElement $element */
            $element = $allElements->item($i);
            if (! $element) {
                continue;
            }

            $tag = strtolower($element->nodeName);

            // Skip root wrapper
            if ($tag === 'div' && $element->parentNode === null) {
                continue;
            }

            // If tag is not in allowed list, mark for unwrap
            if (! in_array($tag, self::ALLOWED_TAGS, true) && $tag !== 'html' && $tag !== 'body') {
                $elementsToStrip[] = $element;
                continue;
            }

            // Sanitize attributes
            $this->cleanAttributes($element);
        }

        // Unwrap elements that were not allowed (preserve inner text)
        foreach ($elementsToStrip as $el) {
            $parent = $el->parentNode;
            if (! $parent) {
                continue;
            }
            while ($el->hasChildNodes()) {
                $child = $el->firstChild;
                $parent->insertBefore($child, $el);
            }
            $parent->removeChild($el);
        }

        // Extract body or inner content
        $output = '';
        $body = $dom->getElementsByTagName('body')->item(0);
        if ($body) {
            foreach ($body->childNodes as $child) {
                $output .= $dom->saveHTML($child);
            }
        } else {
            $output = $dom->saveHTML();
        }

        // Remove XML declaration if present
        $output = preg_replace('/<\?xml.*?\?>/i', '', $output);

        return trim((string) $output);
    }

    /**
     * Clean and restrict attributes on an element.
     */
    private function cleanAttributes(\DOMElement $element): void
    {
        $tag = strtolower($element->nodeName);
        $attributesToRemove = [];

        foreach ($element->attributes as $attr) {
            $attrName = strtolower($attr->nodeName);
            $attrValue = trim($attr->nodeValue);

            // Strip ALL event handlers: onclick, onload, onerror, etc.
            if (str_starts_with($attrName, 'on')) {
                $attributesToRemove[] = $attrName;
                continue;
            }

            // Anchor link check
            if ($tag === 'a' && $attrName === 'href') {
                if ($this->isDangerousUrl($attrValue)) {
                    $attributesToRemove[] = $attrName;
                } else {
                    $element->setAttribute('target', '_blank');
                    $element->setAttribute('rel', 'noopener noreferrer nofollow');
                }
                continue;
            }

            // Image src check
            if ($tag === 'img' && $attrName === 'src') {
                if ($this->isDangerousUrl($attrValue)) {
                    $attributesToRemove[] = $attrName;
                }
                continue;
            }

            // Inline styles check: remove expression(), javascript:, url(javascript:...)
            if ($attrName === 'style') {
                $cleanStyle = preg_replace('/expression\s*\(.*?\)/is', '', $attrValue);
                $cleanStyle = preg_replace('/behavior\s*:.*?;/is', '', $cleanStyle);
                $cleanStyle = preg_replace('/javascript\s*:/is', '', $cleanStyle);
                $element->setAttribute('style', (string) $cleanStyle);
                continue;
            }

            // If attribute is not in allowed list, remove it
            if (! in_array($attrName, self::ALLOWED_ATTRIBUTES, true)) {
                $attributesToRemove[] = $attrName;
            }
        }

        foreach ($attributesToRemove as $name) {
            $element->removeAttribute($name);
        }
    }

    /**
     * Check if a URL protocol is dangerous.
     */
    private function isDangerousUrl(string $url): bool
    {
        $cleaned = strtolower(trim($url));

        // Detect javascript:, vbscript:, data: (except safe images in src)
        return str_starts_with($cleaned, 'javascript:')
            || str_starts_with($cleaned, 'vbscript:')
            || (str_starts_with($cleaned, 'data:') && ! str_starts_with($cleaned, 'data:image/'));
    }
}
