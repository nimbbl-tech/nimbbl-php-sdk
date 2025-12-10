<?php

namespace Nimbbl\Api;

/**
 * Central masking utilities for sensitive data.
 * 
 * This class provides a centralized approach to masking sensitive information
 * across the SDK, consolidating masking logic from various modules.
 * 
 */
class CentralMasker
{
    // ========== Personal Information Masking ==========
    
    /**
     * Mask personal names for display purposes.
     * 
     * @param string $name The name to mask
     * @return string Masked name string
     */
    public static function maskName($name)
    {
        if (empty($name) || trim($name) === '') {
            return $name;
        }
        
        $name = trim($name);
        $words = explode(' ', $name);
        
        if (empty($words)) {
            return $name;
        }
        
        $maskedWords = [];
        foreach ($words as $word) {
            if (strlen($word) <= 1) {
                $maskedWords[] = $word;
            } else {
                $maskedWords[] = $word[0] . str_repeat('*', strlen($word) - 1);
            }
        }
        
        return implode(' ', $maskedWords);
    }
    
    /**
     * Mask email addresses for display purposes.
     * Handles both standard email format (user@domain) and URL-encoded format.
     * 
     * @param string $email The email address to mask
     * @return string Masked email string
     */
    public static function maskEmail($email)
    {
        if (empty($email)) {
            return $email;
        }
        
        $processedEmail = urldecode($email);
        
        if (strpos($processedEmail, '@') === false) {
            return $email;
        }
        
        list($local, $domain) = explode('@', $processedEmail, 2);
        
        if (strlen($local) <= 2) {
            $maskedLocal = strlen($local) > 1 ? $local[0] . str_repeat('*', strlen($local) - 1) : $local;
        } elseif (strlen($local) <= 4) {
            $maskedLocal = $local[0] . str_repeat('*', strlen($local) - 2) . substr($local, -1);
        } else {
            $maskedLocal = substr($local, 0, 2) . str_repeat('*', strlen($local) - 4) . substr($local, -2);
        }
        
        return $maskedLocal . '@' . $domain;
    }
    
    /**
     * Mask mobile numbers for display purposes.
     * 
     * @param string $mobileNumber The mobile number to mask
     * @return string Masked mobile number string
     */
    public static function maskMobileNumber($mobileNumber)
    {
        if (empty($mobileNumber)) {
            return $mobileNumber;
        }
        
        if (strlen($mobileNumber) <= 4) {
            return str_repeat('*', strlen($mobileNumber));
        }
        
        $mobileNumber = trim($mobileNumber);
        return str_repeat('*', strlen($mobileNumber) - 4) . substr($mobileNumber, -4);
    }
    
    // ========== Address Information Masking ==========
    
    /**
     * Mask address lines for display purposes.
     * 
     * @param string $addressLine The address line to mask
     * @return string Masked address line string
     */
    public static function maskAddressLine($addressLine)
    {
        if (empty($addressLine)) {
            return $addressLine;
        }
        
        $addressLine = trim($addressLine);
        $words = explode(' ', $addressLine);
        
        $maskedWords = [];
        foreach ($words as $word) {
            if (strlen($word) == 1) {
                $maskedWords[] = $word;
            } elseif (strlen($word) == 2) {
                $maskedWords[] = $word[0] . '*';
            } else {
                $maskedWords[] = $word[0] . str_repeat('*', strlen($word) - 1);
            }
        }
        
        return implode(' ', $maskedWords);
    }
    
    /**
     * Mask city or state names for display purposes.
     * Shows only the first 2 letters of each word and masks the rest.
     * 
     * @param string $cityArea The city or state name to mask
     * @return string Masked city/state string
     */
    public static function maskCityArea($cityArea)
    {
        if (empty($cityArea)) {
            return $cityArea;
        }
        
        $cityArea = trim($cityArea);
        $words = explode(' ', $cityArea);
        
        $maskedWords = [];
        foreach ($words as $word) {
            if (strlen($word) <= 2) {
                $maskedWords[] = $word;
            } else {
                $maskedWords[] = substr($word, 0, 2) . str_repeat('*', strlen($word) - 2);
            }
        }
        
        return implode(' ', $maskedWords);
    }
    
    /**
     * Mask pincode for display purposes.
     * 
     * @param string $pincode The pincode to mask
     * @return string Masked pincode string
     */
    public static function maskPincode($pincode)
    {
        if (empty($pincode)) {
            return $pincode;
        }
        
        $pincode = trim($pincode);
        return substr($pincode, 0, 2) . str_repeat('*', strlen($pincode) - 2);
    }
    
    // ========== Financial Information Masking ==========
    
    /**
     * Mask bank account numbers for display purposes.
     * 
     * @param string $accountNumber The account number to mask
     * @return string Masked account number string
     */
    public static function maskAccountNumber($accountNumber)
    {
        if (strlen($accountNumber) < 4) {
            return str_repeat('*', strlen($accountNumber));
        }
        
        $accountNumber = trim($accountNumber);
        return str_repeat('*', strlen($accountNumber) - 4) . substr($accountNumber, -4);
    }
    
    /**
     * Mask IFSC codes for display purposes.
     * 
     * @param string $ifscCode The IFSC code to mask
     * @return string Masked IFSC code string
     */
    public static function maskIfscCode($ifscCode)
    {
        if (empty($ifscCode) || strlen($ifscCode) < 6) {
            return $ifscCode;
        }
        
        $ifscCode = strtoupper(trim($ifscCode));
        
        if (strlen($ifscCode) >= 8) {
            return substr($ifscCode, 0, 4) . str_repeat('*', strlen($ifscCode) - 6) . substr($ifscCode, -2);
        } else {
            return substr($ifscCode, 0, 2) . str_repeat('*', strlen($ifscCode) - 4) . substr($ifscCode, -2);
        }
    }
    
    /**
     * Mask PAN card numbers for display purposes.
     * Shows first 3 characters and last 1 character, masks middle 6.
     * 
     * @param string $panCard The PAN card number to mask
     * @return string Masked PAN card string
     */
    public static function maskPanCard($panCard)
    {
        if (empty($panCard)) {
            return $panCard;
        }
        
        $panCard = strtoupper(trim($panCard));
        return substr($panCard, 0, 3) . str_repeat('*', strlen($panCard) - 4) . substr($panCard, -1);
    }
    
    // ========== UPI and VPA Masking ==========
    
    /**
     * Mask UPI VPA IDs for display purposes.
     * 
     * @param string $vpaId The VPA ID to mask
     * @return string Masked VPA ID string
     */
    public static function maskVpaId($vpaId)
    {
        if (empty($vpaId)) {
            return $vpaId;
        }
        
        $processedVpa = urldecode($vpaId);
        
        if (strpos($processedVpa, '@') === false) {
            return $vpaId;
        }
        
        if (!preg_match('/^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+$/', $processedVpa)) {
            return $vpaId;
        }
        
        list($user, $domain) = explode('@', $processedVpa, 2);
        $lenOfUser = strlen($user);
        
        if ($lenOfUser <= 4) {
            $masked = $lenOfUser > 2 ? $user[0] . str_repeat('*', $lenOfUser - 2) . substr($user, -1) : $user;
        } else {
            $masked = substr($user, 0, 2) . str_repeat('*', $lenOfUser - 4) . substr($user, -2);
        }
        
        return $masked . '@' . $domain;
    }
    
    // ========== Token and Secret Masking ==========
    
    /**
     * Mask authentication tokens for display purposes.
     * 
     * @param string $token The token to mask
     * @return string Masked token string
     */
    public static function maskToken($token)
    {
        if (empty($token)) {
            return $token;
        }
        
        $token = trim($token);
        if (strlen($token) <= 12) {
            return str_repeat('*', strlen($token));
        }
        return substr($token, 0, 5) . '***********' . substr($token, -7);
    }
    
    /**
     * Mask access secrets for display purposes.
     * 
     * @param string $secret The secret to mask
     * @return string Masked secret string
     */
    public static function maskAccessSecret($secret)
    {
        if (empty($secret)) {
            return $secret;
        }
        
        $secret = trim($secret);
        if (strlen($secret) <= 21) {
            return str_repeat('*', strlen($secret));
        }
        return substr($secret, 0, 17) . '***********' . substr($secret, -4);
    }
    
    // ========== Card-related Masking ==========
    
    /**
     * Mask CVV codes for display purposes.
     * 
     * @return string Always returns "***"
     */
    public static function maskCvv()
    {
        return '***';
    }
    
    /**
     * Mask expiry month for display purposes.
     * 
     * @return string Always returns "**"
     */
    public static function maskExpiryMonth()
    {
        return '**';
    }
    
    /**
     * Mask expiry year for display purposes.
     * 
     * @return string Always returns "****"
     */
    public static function maskExpiryYear()
    {
        return '****';
    }
    
    /**
     * Mask expiry date for display purposes.
     * 
     * @return string Always returns masked expiry date (month/year format)
     */
    public static function maskExpiryDate()
    {
        return '**/****';
    }
    
    /**
     * Mask cryptogram for display purposes.
     * 
     * @param string $cryptogram The cryptogram to mask
     * @return string Masked cryptogram string
     */
    public static function maskCryptogram($cryptogram)
    {
        if (empty($cryptogram) || strlen($cryptogram) < 6) {
            return $cryptogram;
        }
        
        $cryptogram = trim($cryptogram);
        return substr($cryptogram, 0, 3) . '***************' . substr($cryptogram, -3);
    }
    
    /**
     * Mask network tokens for display purposes.
     * 
     * @param string $tokenPan The network token to mask
     * @return string Masked network token string
     */
    public static function maskNetworkToken($tokenPan)
    {
        if (empty($tokenPan) || strlen($tokenPan) < 8) {
            return $tokenPan;
        }
        
        $tokenPan = trim($tokenPan);
        return substr($tokenPan, 0, 4) . '********' . substr($tokenPan, -4);
    }
    
    /**
     * Mask header values for display purposes.
     * 
     * @param string $value The header value to mask
     * @return string Masked header value string
     */
    public static function maskHeaderValue($value)
    {
        if (empty($value)) {
            return '';
        }
        $value = (string)$value;
        if (strlen($value) <= 8) {
            return str_repeat('*', strlen($value));
        }
        return substr($value, 0, 4) . '************' . substr($value, -4);
    }
    
    /**
     * Mask card numbers for display purposes.
     * Legacy card masking function for backward compatibility.
     * Masks card numbers with **** **** **** format.
     * 
     * @param string $card The card number to mask
     * @return string Masked card string
     */
    public static function maskCard($card)
    {
        if (empty($card)) {
            return '';
        }
        $card = (string)$card;
        return '**** **** **** ' . substr($card, -4);
    }
}

