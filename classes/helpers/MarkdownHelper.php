<?php
/**
 * MarkdownHelper Class
 * Tro365 - Helper for markdown and text processing
 */

namespace Tro365\Helpers;

class MarkdownHelper
{
    /**
     * Create excerpt from text content
     *
     * @param string $content The content to create excerpt from
     * @param int $length Maximum length of excerpt
     * @return string The excerpt
     */
    public static function createExcerpt($content, $length = 150)
    {
        if (empty($content)) {
            return '';
        }

        // Strip HTML tags and decode entities
        $text = strip_tags($content);
        $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');

        // Remove excessive whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));

        // Truncate if needed
        if (strlen($text) > $length) {
            $text = substr($text, 0, $length);

            // Find the last complete word
            $lastSpace = strrpos($text, ' ');
            if ($lastSpace !== false) {
                $text = substr($text, 0, $lastSpace);
            }

            $text .= '...';
        }

        return $text;
    }

    /**
     * Convert markdown to HTML (basic implementation)
     *
     * @param string $markdown The markdown content
     * @return string HTML content
     */
    public static function toHtml($markdown)
    {
        if (empty($markdown)) {
            return '';
        }

        // Basic markdown to HTML conversion
        $html = $markdown;

        // Convert headers
        $html = preg_replace('/^### (.*$)/m', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*$)/m', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*$)/m', '<h1>$1</h1>', $html);

        // Convert bold and italic
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);

        // Convert line breaks
        $html = nl2br($html);

        return $html;
    }

    /**
     * Strip markdown formatting
     *
     * @param string $markdown The markdown content
     * @return string Plain text
     */
    public static function stripMarkdown($markdown)
    {
        if (empty($markdown)) {
            return '';
        }

        // Remove markdown formatting
        $text = preg_replace('/^#{1,6}\s*/', '', $markdown); // Headers
        $text = preg_replace('/\*\*(.*?)\*\*/', '$1', $text); // Bold
        $text = preg_replace('/\*(.*?)\*/', '$1', $text); // Italic
        $text = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $text); // Links

        return trim($text);
    }
    /**
     * Validate content (accepts HTML or Markdown)
     * Ensures plain text length is >= 50 chars by default.
     * Returns ['valid' => bool, 'errors' => array]
     */
    public static function validate($content, $minLength = 50)
    {
        $content = (string)($content ?? '');
        // If looks like HTML, strip tags; otherwise treat as markdown and strip markdown
        $plain = trim(strip_tags($content));
        if ($plain === $content) {
            // No HTML tags were present; attempt to strip basic markdown
            $plain = self::stripMarkdown($content);
        }
        // Normalize whitespace
        $plain = preg_replace('/\s+/', ' ', $plain);
        $length = mb_strlen(trim($plain), 'UTF-8');
        if ($length < $minLength) {
            return [
                'valid' => false,
                'errors' => ["Nội dung phải có ít nhất {$minLength} ký tự"]
            ];
        }
        return ['valid' => true, 'errors' => []];
    }

}
