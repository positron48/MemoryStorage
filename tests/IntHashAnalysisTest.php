<?php

namespace MemoryStorage\Tests;

use MemoryStorage\ArrayMemoryStorage;
use PHPUnit\Framework\TestCase;

/**
 * Detailed analysis of the intHash method and better alternatives
 */
class IntHashAnalysisTest extends TestCase
{
    /**
     * Analyze what the current intHash method actually does
     */
    public function testCurrentIntHashAnalysis(): void
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "ANALYSIS OF CURRENT INTHASH METHOD:\n";
        echo str_repeat("=", 80) . "\n";
        
        $testKey = 'example_key_for_analysis';
        
        // Step 1: MD5 hash
        $md5Hex = md5($testKey);
        $md5Binary = md5($testKey, true);
        
        echo "1. MD5 Hash:\n";
        echo "   Hex: {$md5Hex}\n";
        echo "   Binary length: " . strlen($md5Binary) . " bytes\n";
        echo "   Binary (hex): " . bin2hex($md5Binary) . "\n";
        
        // Step 2: Unpack as two 32-bit unsigned integers (big-endian)
        $unpacked = unpack('N2', $md5Binary);
        
        echo "\n2. Unpack as N2 (two 32-bit unsigned big-endian):\n";
        echo "   First 32-bit: {$unpacked[1]}\n";
        echo "   Second 32-bit: {$unpacked[2]}\n";
        
        // Step 3: Concatenate as strings
        $concatenated = $unpacked[1] . $unpacked[2];
        
        echo "\n3. Concatenate as strings:\n";
        echo "   Result: '{$concatenated}'\n";
        echo "   Length: " . strlen($concatenated) . " characters\n";
        
        // Step 4: Take first 19 characters (current fix)
        $truncated = substr($concatenated, 0, 19);
        
        echo "\n4. Truncate to 19 characters (current fix):\n";
        echo "   Result: '{$truncated}'\n";
        echo "   Length: " . strlen($truncated) . " characters\n";
        
        // Step 5: Cast to int
        $finalInt = (int) $truncated;
        
        echo "\n5. Cast to int:\n";
        echo "   Result: {$finalInt}\n";
        echo "   PHP_INT_MAX: " . PHP_INT_MAX . "\n";
        echo "   Overflow? " . ($finalInt == PHP_INT_MAX ? 'YES' : 'NO') . "\n";
        
        // Show the problem
        echo "\n" . str_repeat("-", 60) . "\n";
        echo "PROBLEMS WITH CURRENT APPROACH:\n";
        echo str_repeat("-", 60) . "\n";
        echo "1. We're concatenating two 32-bit numbers as strings\n";
        echo "2. This can create 20-character strings (10 digits each)\n";
        echo "3. 20-digit numbers exceed PHP_INT_MAX on 64-bit systems\n";
        echo "4. Truncating to 19 characters loses information\n";
        echo "5. Multiple different keys can produce the same truncated result\n";
        
        $this->assertTrue(true, 'Analysis completed');
    }
    
    /**
     * Test better hashing alternatives
     */
    public function testBetterHashingAlternatives(): void
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "BETTER HASHING ALTERNATIVES:\n";
        echo str_repeat("=", 80) . "\n";
        
        $testKeys = [
            'key1',
            'key2', 
            'very_long_key_that_might_produce_20_char_hash_1',
            'very_long_key_that_might_produce_20_char_hash_2',
        ];
        
        foreach ($testKeys as $key) {
            echo "\nKey: '{$key}'\n";
            echo str_repeat("-", 50) . "\n";
            
            // Current method
            $current = $this->intHashCurrent($key);
            echo "Current method: {$current}\n";
            
            // Alternative 1: Use crc32 (32-bit hash)
            $crc32 = crc32($key);
            echo "CRC32: {$crc32}\n";
            
            // Alternative 2: Use first 32 bits of MD5 as integer
            $md5First32 = $this->intHashMD5First32($key);
            echo "MD5 first 32-bit: {$md5First32}\n";
            
            // Alternative 3: Use hash() with different algorithms
            $hashMd5 = hexdec(substr(hash('md5', $key), 0, 8));
            echo "Hash MD5 (first 8 hex): {$hashMd5}\n";
            
            $hashSha1 = hexdec(substr(hash('sha1', $key), 0, 8));
            echo "Hash SHA1 (first 8 hex): {$hashSha1}\n";
            
            // Alternative 4: Use modulo to fit in safe range
            $modulo = $this->intHashModulo($key);
            echo "Modulo approach: {$modulo}\n";
        }
        
        $this->assertTrue(true, 'Alternatives tested');
    }
    
    /**
     * Test collision rates for different approaches
     */
    public function testCollisionRates(): void
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "COLLISION RATE ANALYSIS:\n";
        echo str_repeat("=", 80) . "\n";
        
        // Generate many test keys
        $testKeys = [];
        for ($i = 0; $i < 1000; $i++) {
            $testKeys[] = "key_{$i}_" . md5("random_{$i}_" . time());
        }
        
        $methods = [
            'Current' => [$this, 'intHashCurrent'],
            'CRC32' => [$this, 'intHashCRC32'],
            'MD5 First 32' => [$this, 'intHashMD5First32'],
            'Modulo' => [$this, 'intHashModulo'],
        ];
        
        foreach ($methods as $methodName => $method) {
            $hashes = [];
            $collisions = 0;
            
            foreach ($testKeys as $key) {
                $hash = $method($key);
                if (isset($hashes[$hash])) {
                    $collisions++;
                } else {
                    $hashes[$hash] = $key;
                }
            }
            
            $collisionRate = ($collisions / count($testKeys)) * 100;
            $uniqueHashes = count($hashes);
            
            echo "\n{$methodName}:\n";
            echo "  Total keys: " . count($testKeys) . "\n";
            echo "  Unique hashes: {$uniqueHashes}\n";
            echo "  Collisions: {$collisions}\n";
            echo "  Collision rate: " . number_format($collisionRate, 2) . "%\n";
        }
        
        $this->assertTrue(true, 'Collision analysis completed');
    }
    
    // Helper methods for different hashing approaches
    
    private function intHashCurrent(string $key): int
    {
        $binhash = md5($key, true);
        $numhash = unpack('N2', $binhash);
        $hash = $numhash[1] . $numhash[2];
        $hash = substr($hash, 0, 19);
        if ($hash === '' || $hash === '0') {
            $hash = '1';
        }
        return (int) $hash;
    }
    
    private function intHashCRC32(string $key): int
    {
        return crc32($key);
    }
    
    private function intHashMD5First32(string $key): int
    {
        $binhash = md5($key, true);
        $unpacked = unpack('N', $binhash);
        return $unpacked[1];
    }
    
    private function intHashModulo(string $key): int
    {
        $binhash = md5($key, true);
        $numhash = unpack('N2', $binhash);
        // Use XOR to combine the two 32-bit values
        $combined = $numhash[1] ^ $numhash[2];
        // Ensure positive result
        return abs($combined);
    }
}
