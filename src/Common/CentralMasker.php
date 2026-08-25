<?php

namespace Nimbbl\Api\Common;

use Nimbbl\Api\Common\JsonKeys;

/**
 * Utility to mask sensitive data in headers and JSON bodies for logging.
 */
class CentralMasker
{
    private static $sensitiveHeaderKeys = [
        JsonKeys::AUTHORIZATION,
    ];

    // Mask sensitive financial/authentication data in INFO logs
    // These are only visible unmasked when DEBUG logging is enabled
    // Based on Nimbbl PII masking guidelines
    private static $sensitiveBodyKeys = [
            // Authentication credentials
        JsonKeys::ACCESS_KEY,
        JsonKeys::ACCESS_SECRET,
            // Authentication tokens
        JsonKeys::TOKEN,
        JsonKeys::REFRESH_TOKEN,
            // Names
        JsonKeys::FIRST_NAME,
        JsonKeys::LAST_NAME,
        JsonKeys::CARD_HOLDER_NAME,
        JsonKeys::UPI_HOLDER,
            // Response-side PII field names (webhook/callback use short forms)
        JsonKeys::NAME,
        JsonKeys::CARD_HOLDER,
            // Mobile/Phone
        JsonKeys::MOBILE_NUMBER,
        JsonKeys::MOBILE,
            // Email
        JsonKeys::EMAIL,
            // Address fields
        JsonKeys::STREET,
        JsonKeys::LANDMARK,
        JsonKeys::AREA,
        JsonKeys::CITY,
        JsonKeys::STATE,
            // Pincode
        JsonKeys::PINCODE,
        JsonKeys::POSTAL_CODE,
        JsonKeys::ZIP_CODE,
            // UPI/VPA
        JsonKeys::VPA,
            // Payment card sensitive data
        JsonKeys::CARD_NO,
        JsonKeys::CARD_NUMBER,
        JsonKeys::CVV,
        JsonKeys::EXPIRY_DATE,
            // Account details
        JsonKeys::ACCOUNT_NUMBER,
        JsonKeys::ACCOUNT_NO,
        JsonKeys::IFSC_CODE,
        JsonKeys::PAN_CARD,
    ];

    /**
     * Mask headers for logging
     * 
     * @param array $headers Request headers
     * @param array|null $contentHeaders Content headers (optional)
     * @return array Headers with sensitive values masked
     */
    public static function maskHeaders($headers, $contentHeaders = null)
    {
        $masked = [];
        // Normalize sensitive keys for checking
        $sensitiveKeysLower = array_map('strtolower', self::$sensitiveHeaderKeys);

        $addMaskedHeader = function ($key, $value) use (&$masked, $sensitiveKeysLower) {
            $keyLower = strtolower($key);
            $valStr = is_array($value) ? implode(', ', $value) : (string) $value;

            if (in_array($keyLower, $sensitiveKeysLower)) {
                $masked[$key] = self::maskString($valStr);
            } else {
                $masked[$key] = $valStr;
            }
        };

        foreach ($headers as $key => $value) {
            $addMaskedHeader($key, $value);
        }

        if ($contentHeaders !== null) {
            foreach ($contentHeaders as $key => $value) {
                $addMaskedHeader($key, $value);
            }
        }

        return $masked;
    }

    /**
     * Get headers without masking (for debug logging)
     * 
     * @param array $headers Request headers
     * @param array|null $contentHeaders Content headers (optional)
     * @return array Headers without masking
     */
    public static function getUnmaskedHeaders($headers, $contentHeaders = null)
    {
        $unmasked = [];

        $addHeader = function ($key, $value) use (&$unmasked) {
            $unmasked[$key] = is_array($value) ? implode(', ', $value) : (string) $value;
        };

        foreach ($headers as $key => $value) {
            $addHeader($key, $value);
        }

        if ($contentHeaders !== null) {
            foreach ($contentHeaders as $key => $value) {
                $addHeader($key, $value);
            }
        }

        return $unmasked;
    }

    /**
     * Mask JSON body for logging
     * 
     * @param string $body JSON body string
     * @return string Masked JSON body string
     */
    public static function maskBody($body)
    {
        if (empty(trim($body ?? ''))) {
            return $body;
        }

        try {
            $decoded = json_decode($body, true);
            if (json_last_error() === JSON_ERROR_NONE && (is_array($decoded) || is_object($decoded))) {
                $masked = self::maskElement($decoded);
                return json_encode($masked, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
            }
        } catch (\Exception $e) {
            // Fallback to plain text masking if not valid JSON
        }

        // If not JSON, best-effort mask tokens in plain text
        return self::maskPlainText($body);
    }

    /**
     * Recursively mask JSON elements
     * 
     * @param mixed $element JSON element to mask
     * @return mixed Masked element
     */
    private static function maskElement($element)
    {
        if (is_array($element)) {
            // Check if associative array (object)
            if (self::isAssociativeArray($element)) {
                $masked = [];
                // Use lower case set for faster lookup
                $sensitiveSet = array_map('strtolower', self::$sensitiveBodyKeys);

                foreach ($element as $key => $value) {
                    $masked[$key] = $value; // Default assignment

                    if (in_array(strtolower($key), $sensitiveSet)) {
                        if (is_string($value)) {
                            $keyLower = strtolower($key);

                            // Use appropriate masking based on key type (following Nimbbl PII masking guidelines)
                            if ($keyLower === strtolower(JsonKeys::ACCESS_SECRET)) {
                                $masked[$key] = self::maskAccessSecret($value);
                            } elseif ($keyLower === strtolower(JsonKeys::ACCESS_KEY)) {
                                $masked[$key] = self::maskAccessKey($value);
                            } elseif (strpos($keyLower, 'token') !== false) {
                                $masked[$key] = self::maskToken($value);
                            } elseif (
                                strpos($keyLower, 'name') !== false ||
                                in_array($keyLower, [strtolower(JsonKeys::FIRST_NAME), strtolower(JsonKeys::LAST_NAME), strtolower(JsonKeys::CARD_HOLDER_NAME), strtolower(JsonKeys::CARD_HOLDER), strtolower(JsonKeys::UPI_HOLDER)])
                            ) {
                                $masked[$key] = self::maskName($value);
                            } elseif (strpos($keyLower, 'phone') !== false || strpos($keyLower, 'mobile') !== false || strpos($keyLower, 'contact_number') !== false) {
                                $masked[$key] = self::maskPhone($value);
                            } elseif (strpos($keyLower, 'email') !== false) {
                                $masked[$key] = self::maskEmail($value);
                            } elseif (
                                strpos($keyLower, 'address') !== false ||
                                in_array($keyLower, [strtolower(JsonKeys::STREET), strtolower(JsonKeys::LANDMARK), strtolower(JsonKeys::AREA)])
                            ) {
                                $masked[$key] = self::maskAddress($value);
                            } elseif ($keyLower === strtolower(JsonKeys::CITY) || $keyLower === strtolower(JsonKeys::STATE)) {
                                $masked[$key] = self::maskCityArea($value);
                            } elseif (
                                strpos($keyLower, 'pincode') !== false ||
                                in_array($keyLower, [strtolower(JsonKeys::PINCODE), strtolower(JsonKeys::POSTAL_CODE), strtolower(JsonKeys::ZIP_CODE)])
                            ) {
                                $masked[$key] = self::maskPincode($value);
                            } elseif (strpos($keyLower, 'upi') !== false || $keyLower === strtolower(JsonKeys::VPA)) {
                                $masked[$key] = self::maskUpiId($value);
                            } elseif ($keyLower === strtolower(JsonKeys::CARD_NO) || $keyLower === strtolower(JsonKeys::CARD_NUMBER)) {
                                $masked[$key] = self::maskCardNumber($value);
                            } elseif ($keyLower === strtolower(JsonKeys::CVV)) {
                                $masked[$key] = '***';
                            } elseif (strpos($keyLower, 'expiry') !== false || $keyLower === strtolower(JsonKeys::EXPIRY_DATE)) {
                                $masked[$key] = '**/****';
                            } elseif (in_array($keyLower, [strtolower(JsonKeys::ACCOUNT_NUMBER), strtolower(JsonKeys::ACCOUNT_NO)])) {
                                $masked[$key] = self::maskAccountNumber($value);
                            } elseif (strpos($keyLower, 'ifsc') !== false || $keyLower === strtolower(JsonKeys::IFSC_CODE)) {
                                $masked[$key] = self::maskIfsc($value);
                            } elseif ($keyLower === strtolower(JsonKeys::PAN_CARD)) {
                                $masked[$key] = self::maskPan($value);
                            } else {
                                $masked[$key] = self::maskString($value);
                            }
                        } elseif (is_numeric($value)) {
                            // Mask numeric sensitive values as string ***
                            $masked[$key] = "***";
                        } else {
                            // Recurse for nested objects/arrays even if key matches (rare case)
                            $masked[$key] = self::maskElement($value);
                        }
                    } else {
                        // Recurse for non-sensitive keys
                        $masked[$key] = self::maskElement($value);
                    }
                }
                return $masked;
            } else {
                // Indexed array -> iterate items
                return array_map([self::class, 'maskElement'], $element);
            }
        } elseif (is_string($element)) {
            // Strings in array content are not masked by key, only by their parent key if checked above.
            // But if we are here, it means we passed a scalar to maskElement directly.
            return $element;
        } else {
            return $element;
        }
    }

    private static function isAssociativeArray($array)
    {
        if (!is_array($array))
            return false;
        if ($array === [])
            return false;
        return array_keys($array) !== range(0, count($array) - 1);
    }

    // --- Masking Methods ---

    private static function maskString($value)
    {
        if (empty($value))
            return $value;
        if (strlen($value) <= 8)
            return "***";
        // Show first 4 and last 4 characters, mask the middle
        return substr($value, 0, 4) . "***" . substr($value, -4);
    }

    private static function maskToken($value)
    {
        if (empty($value))
            return $value;
        $value = trim($value);
        // Nimbbl API format (mask_token): first 5 + 11 asterisks + last 7
        if (strlen($value) <= 12)
            return str_repeat('*', strlen($value));
        return substr($value, 0, 5) . "***********" . substr($value, -7);
    }

    private static function maskAccessKey($value)
    {
        if (empty($value))
            return $value;
        if (strlen($value) <= 8)
            return str_repeat('*', strlen($value));
        // access_key has no dedicated Nimbbl API rule; show first 4 and last 4
        return substr($value, 0, 4) . "****" . substr($value, -4);
    }

    private static function maskAccessSecret($value)
    {
        if (empty($value))
            return $value;
        $value = trim($value);
        // Nimbbl API format (mask_access_secret): first 17 + 11 asterisks + last 4
        if (strlen($value) <= 21)
            return str_repeat('*', strlen($value));
        return substr($value, 0, 17) . "***********" . substr($value, -4);
    }

    private static function maskName($value)
    {
        if (empty($value) || trim($value) === '')
            return $value;

        $parts = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts))
            return str_repeat('*', strlen($value));

        $maskedParts = array_map(function ($part) {
            if (empty($part))
                return $part;
            if (strlen($part) <= 1)
                return $part;
            // First letter + asterisks
            return $part[0] . str_repeat('*', strlen($part) - 1);
        }, $parts);

        return implode(' ', $maskedParts);
    }

    private static function maskPhone($value)
    {
        if (empty($value) || trim($value) === '')
            return $value;

        // Remove spaces and common separators
        $cleaned = str_replace([' ', '-', '(', ')'], '', $value);

        if (strpos($cleaned, '+') === 0) {
            $countryCodeEnd = 1;
            while ($countryCodeEnd < strlen($cleaned) && is_numeric($cleaned[$countryCodeEnd])) {
                $countryCodeEnd++;
            }
            if ($countryCodeEnd < strlen($cleaned)) {
                $countryCode = substr($cleaned, 0, $countryCodeEnd);
                $number = substr($cleaned, $countryCodeEnd);
                if (strlen($number) >= 4) {
                    $last4 = substr($number, -4);
                    $masked = str_repeat('*', strlen($number) - 4);
                    return "{$countryCode} {$masked}{$last4}";
                }
            }
        }

        if (strlen($cleaned) >= 4 && is_numeric($cleaned)) {
            $last4 = substr($cleaned, -4);
            $masked = str_repeat('*', strlen($cleaned) - 4);
            return "{$masked}{$last4}";
        }

        return str_repeat('*', strlen($value));
    }

    private static function maskEmail($value)
    {
        if (empty($value))
            return $value;

        // Nimbbl API (mask_email): URL-decode, then tier by local-part length.
        $processed = urldecode($value);
        $atIndex = strpos($processed, '@');
        if ($atIndex === false)
            return $value;

        $local = substr($processed, 0, $atIndex);
        $domain = substr($processed, $atIndex); // includes '@'
        $len = strlen($local);

        if ($len <= 2) {
            $maskedLocal = $len > 1 ? $local[0] . str_repeat('*', $len - 1) : $local;
        } elseif ($len <= 4) {
            $maskedLocal = $local[0] . str_repeat('*', $len - 2) . substr($local, -1);
        } else {
            $maskedLocal = substr($local, 0, 2) . str_repeat('*', $len - 4) . substr($local, -2);
        }
        return $maskedLocal . $domain;
    }

    private static function maskCityArea($value)
    {
        if (empty($value) || trim($value) === '')
            return $value;

        // Nimbbl API (mask_city_area): first 2 letters of each word shown, rest masked.
        $parts = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts))
            return str_repeat('*', strlen($value));

        $maskedParts = array_map(function ($part) {
            if (strlen($part) <= 2)
                return $part;
            return substr($part, 0, 2) . str_repeat('*', strlen($part) - 2);
        }, $parts);

        return implode(' ', $maskedParts);
    }

    private static function maskAddress($value)
    {
        if (empty($value) || trim($value) === '')
            return $value;

        $parts = preg_split('/\s+/', trim($value), -1, PREG_SPLIT_NO_EMPTY);
        if (empty($parts))
            return str_repeat('*', strlen($value));

        $maskedParts = array_map(function ($part) {
            if (empty($part))
                return $part;
            if (strlen($part) == 1)
                return $part;
            if (strlen($part) == 2)
                return $part[0] . "*";
            // First char + asterisks
            return $part[0] . str_repeat('*', strlen($part) - 1);
        }, $parts);

        return implode(' ', $maskedParts);
    }

    private static function maskPincode($value)
    {
        if (empty($value))
            return $value;

        $cleaned = str_replace([' ', '-'], '', trim($value));
        if (strlen($cleaned) >= 2 && is_numeric($cleaned)) {
            $first2 = substr($cleaned, 0, 2);
            $masked = str_repeat('*', strlen($cleaned) - 2);
            return "{$first2}{$masked}";
        }

        return str_repeat('*', strlen($value));
    }

    private static function maskUpiId($value)
    {
        if (empty($value))
            return $value;

        // Nimbbl API (mask_vpa_id): URL-decode + validate; then tier by user length.
        $processed = urldecode($value);
        $atIndex = strpos($processed, '@');
        if ($atIndex === false)
            return $value;
        if (!preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+$/', $processed))
            return $value;

        $user = substr($processed, 0, $atIndex);
        $domain = substr($processed, $atIndex); // includes '@'
        $len = strlen($user);

        if ($len <= 4) {
            $maskedUser = $len > 2
                ? $user[0] . str_repeat('*', $len - 2) . substr($user, -1)
                : $user;
        } else {
            $maskedUser = substr($user, 0, 2) . str_repeat('*', $len - 4) . substr($user, -2);
        }
        return $maskedUser . $domain;
    }

    private static function maskCardNumber($value)
    {
        if (empty($value)) {
            return $value;
        }

        // Nimbbl API (mask_card): fixed "**** **** **** " + last 4.
        $cleaned = str_replace([' ', '-'], '', $value);
        return "**** **** **** " . substr($cleaned, -4);
    }

    private static function maskAccountNumber($value)
    {
        if (empty($value))
            return $value;

        $cleaned = str_replace([' ', '-'], '', trim($value));
        if (strlen($cleaned) >= 4 && is_numeric($cleaned)) {
            $last4 = substr($cleaned, -4);
            $masked = str_repeat('*', strlen($cleaned) - 4);
            return "{$masked}{$last4}";
        }

        return str_repeat('*', strlen($value));
    }

    private static function maskIfsc($value)
    {
        if (empty($value))
            return $value;

        // Nimbbl API (mask_ifsc_code): <6 returned as-is; >=8 -> first4+*+last2; 6-7 -> first2+*+last2.
        $cleaned = strtoupper(trim($value));
        $len = strlen($cleaned);
        if ($len < 6)
            return $value;
        if ($len >= 8)
            return substr($cleaned, 0, 4) . str_repeat('*', $len - 6) . substr($cleaned, -2);
        return substr($cleaned, 0, 2) . str_repeat('*', $len - 4) . substr($cleaned, -2);
    }

    private static function maskPan($value)
    {
        if (empty($value))
            return $value;

        $cleaned = strtoupper(trim($value));
        if (strlen($cleaned) >= 4) {
            $first3 = substr($cleaned, 0, 3);
            $last1 = substr($cleaned, -1);
            $masked = str_repeat('*', strlen($cleaned) - 4);
            return "{$first3}{$masked}{$last1}";
        }

        return str_repeat('*', strlen($value));
    }

    private static function maskPlainText($value)
    {
        if (empty(trim($value ?? '')))
            return $value;

        $masked = $value;
        $sensitiveParams = ['token', 'refresh_token'];

        foreach ($sensitiveParams as $param) {
            $pattern = '/([?&]' . preg_quote($param, '/') . '=)([^&\s"]+)/i';
            $masked = preg_replace_callback($pattern, function ($matches) {
                return $matches[1] . self::maskString($matches[2]);
            }, $masked);
        }

        // Mask card-like digit sequences
        $masked = preg_replace_callback('/(\d[\s-]?){13,19}/', function ($matches) {
            $digits = preg_replace('/[\s-]/', '', $matches[0]);
            if (strlen($digits) >= 13 && strlen($digits) <= 19) {
                return self::maskString($digits);
            }
            return $matches[0];
        }, $masked);

        return $masked;
    }
}
