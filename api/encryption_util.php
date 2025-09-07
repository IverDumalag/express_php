<?php
/**
 * Encryption utility class for symmetric key cryptography
 * Uses AES-256-CBC encryption to protect sensitive data
 */
class EncryptionUtil {
    private static $encryption_key;
    private static $cipher_method = 'AES-256-CBC';
    
    /**
     * Initialize encryption key from environment or config
     */
    public static function init() {
        // Use the same key as in the frontend environment
        self::$encryption_key = getenv('ENCRYPTION_KEY');
        
        // Ensure key is exactly 32 characters for AES-256
        self::$encryption_key = hash('sha256', self::$encryption_key);
    }
    
    /**
     * Encrypt data
     * @param string $data - Data to encrypt
     * @return string - Encrypted data
     */
    public static function encrypt($data) {
        if (empty($data) || !is_string($data)) {
            return $data; // Return as-is if not string or empty
        }
        
        try {
            if (empty(self::$encryption_key)) {
                self::init();
            }
            
            $iv = openssl_random_pseudo_bytes(16);
            $encrypted = openssl_encrypt($data, self::$cipher_method, self::$encryption_key, 0, $iv);
            
            // Combine IV and encrypted data (similar to CryptoJS format)
            return base64_encode($iv . $encrypted);
        } catch (Exception $e) {
            error_log("Encryption error: " . $e->getMessage());
            return $data; // Return original data if encryption fails
        }
    }
    
    /**
     * Decrypt data
     * @param string $encryptedData - Data to decrypt
     * @return string - Decrypted data
     */
    public static function decrypt($encryptedData) {
        if (empty($encryptedData) || !is_string($encryptedData)) {
            return $encryptedData; // Return as-is if not string or empty
        }
        
        try {
            if (empty(self::$encryption_key)) {
                self::init();
            }
            
            $data = base64_decode($encryptedData);
            if ($data === false) {
                return $encryptedData; // Not base64 encoded, probably not encrypted
            }
            
            $iv = substr($data, 0, 16);
            $encrypted = substr($data, 16);
            
            $decrypted = openssl_decrypt($encrypted, self::$cipher_method, self::$encryption_key, 0, $iv);
            
            return $decrypted !== false ? $decrypted : $encryptedData;
        } catch (Exception $e) {
            error_log("Decryption error: " . $e->getMessage());
            return $encryptedData; // Return encrypted data if decryption fails
        }
    }
    
    /**
     * Encrypt specified fields in an array
     * @param array $data - Data array
     * @param array $fieldsToEncrypt - Fields to encrypt
     * @return array - Array with encrypted fields
     */
    public static function encryptArray($data, $fieldsToEncrypt = []) {
        if (!is_array($data)) {
            return $data;
        }
        
        $encrypted = $data;
        
        foreach ($fieldsToEncrypt as $field) {
            if (isset($encrypted[$field]) && !empty($encrypted[$field])) {
                $encrypted[$field] = self::encrypt($encrypted[$field]);
            }
        }
        
        return $encrypted;
    }
    
    /**
     * Decrypt specified fields in an array
     * @param array $data - Data array
     * @param array $fieldsToDecrypt - Fields to decrypt
     * @return array - Array with decrypted fields
     */
    public static function decryptArray($data, $fieldsToDecrypt = []) {
        if (!is_array($data)) {
            return $data;
        }
        
        $decrypted = $data;
        
        foreach ($fieldsToDecrypt as $field) {
            if (isset($decrypted[$field]) && !empty($decrypted[$field])) {
                $decrypted[$field] = self::decrypt($decrypted[$field]);
            }
        }
        
        return $decrypted;
    }
    
    /**
     * Fields that should be encrypted for user data
     */
    public static function getUserEncryptedFields() {
        return ['email', 'f_name', 'm_name', 'l_name', 'phone', 'address'];
    }
    
    /**
     * Fields that should be encrypted for phrases/words data
     */
    public static function getPhrasesEncryptedFields() {
        return ['phrase', 'word', 'description'];
    }
    
    /**
     * Fields that should be encrypted for feedback data
     */
    public static function getFeedbackEncryptedFields() {
        return ['feedback_text', 'user_email'];
    }
}

// Initialize encryption on include
EncryptionUtil::init();
?>
