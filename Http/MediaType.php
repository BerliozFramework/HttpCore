<?php
/*
 * This file is part of Berlioz framework.
 *
 * @license   https://opensource.org/licenses/MIT MIT License
 * @copyright 2026 Ronan GIRON
 * @author    Ronan GIRON <https://github.com/ElGigi>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, to the root.
 */

declare(strict_types=1);

namespace Berlioz\Http\Core\Http;

/**
 * Class MediaType.
 *
 * Media type or media range (RFC 9110, sections 8.3.1 and 12.5.1), with its parameters.
 */
final readonly class MediaType
{
    private const TOKEN = '[!\#$%&\'*+\-.^_`|~0-9A-Za-z]+';

    /**
     * MediaType constructor.
     *
     * @param string $type Lowercase type
     * @param string $subtype Lowercase subtype
     * @param array<string, string> $parameters Decoded parameter values, indexed by lowercase name
     */
    public function __construct(
        public string $type,
        public string $subtype,
        public array $parameters = [],
    ) {
    }

    /**
     * Parse a media type, or a media range if wildcards are allowed.
     *
     * Wildcards are only accepted as "*\/*" or "type/*". Quoted parameter values are decoded (quoted-pairs).
     *
     * @param string $value
     * @param bool $allowWildcard
     *
     * @return self|null NULL if the value is invalid
     */
    public static function parse(string $value, bool $allowWildcard = false): ?self
    {
        if (1 !== preg_match('#^[ \t]*(' . self::TOKEN . ')/(' . self::TOKEN . ')#', $value, $matches)) {
            return null;
        }

        $type = strtolower($matches[1]);
        $subtype = strtolower($matches[2]);

        if (str_contains($type . '/' . $subtype, '*')) {
            $validWildcard = '*' === $subtype && ('*' === $type || !str_contains($type, '*'));

            if (!$allowWildcard || !$validWildcard) {
                return null;
            }
        }

        $length = strlen($value);
        $pos = strlen($matches[0]);
        $parameters = [];

        while (true) {
            $pos += strspn($value, " \t", $pos);
            if ($pos >= $length) {
                break;
            }
            if (';' !== $value[$pos]) {
                return null;
            }

            $pos++;
            $pos += strspn($value, " \t", $pos);
            if ($pos >= $length || ';' === $value[$pos]) {
                continue;
            }

            if (1 !== preg_match('/\G(' . self::TOKEN . ')=/', $value, $matches, 0, $pos)) {
                return null;
            }
            $name = strtolower($matches[1]);
            $pos += strlen($matches[0]);

            if ($pos < $length && '"' === $value[$pos]) {
                if (null === ($parameterValue = self::parseQuotedString($value, $pos))) {
                    return null;
                }
            } else {
                if (1 !== preg_match('/\G' . self::TOKEN . '/', $value, $matches, 0, $pos)) {
                    return null;
                }
                $parameterValue = $matches[0];
                $pos += strlen($matches[0]);
            }

            $parameters[$name] = $parameterValue;
        }

        return new self($type, $subtype, $parameters);
    }

    /**
     * Parse a quoted string (RFC 9110, section 5.6.4) starting at the opening quote.
     *
     * @param string $value
     * @param int $pos Position of the opening quote, moved after the closing quote
     *
     * @return string|null Decoded value, NULL if invalid
     */
    private static function parseQuotedString(string $value, int &$pos): ?string
    {
        $length = strlen($value);
        $decoded = '';
        $pos++;

        while ($pos < $length) {
            $char = $value[$pos++];
            $ord = ord($char);

            if ('"' === $char) {
                return $decoded;
            }

            if ('\\' === $char) {
                if ($pos >= $length) {
                    return null;
                }

                $char = $value[$pos++];
                $ord = ord($char);

                // quoted-pair = "\" ( HTAB / SP / VCHAR / obs-text )
                if (0x09 !== $ord && ($ord < 0x20 || 0x7F === $ord)) {
                    return null;
                }

                $decoded .= $char;
                continue;
            }

            // qdtext = HTAB / SP / %x21 / %x23-5B / %x5D-7E / obs-text
            if (0x09 !== $ord && ($ord < 0x20 || 0x7F === $ord)) {
                return null;
            }

            $decoded .= $char;
        }

        return null;
    }

    /**
     * Get the media type without parameters ("type/subtype").
     *
     * @return string
     */
    public function getMediaType(): string
    {
        return $this->type . '/' . $this->subtype;
    }

    /**
     * Does this media range match the given media type? Parameters are ignored.
     *
     * @param MediaType $mediaType
     *
     * @return bool
     */
    public function matches(MediaType $mediaType): bool
    {
        if ('*' === $this->type) {
            return true;
        }

        if ($this->type !== $mediaType->type) {
            return false;
        }

        return '*' === $this->subtype || $this->subtype === $mediaType->subtype;
    }
}
