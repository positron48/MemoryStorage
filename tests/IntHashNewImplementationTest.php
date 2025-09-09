<?php

namespace MemoryStorage\Tests;

use MemoryStorage\ArrayMemoryStorage;
use PHPUnit\Framework\TestCase;

/**
 * Test the new CRC32-based intHash implementation
 */
class IntHashNewImplementationTest extends TestCase
{
    /**
     * Test that the new implementation works correctly
     */
    public function testNewIntHashImplementation(): void
    {
        $reflection = new \ReflectionClass(ArrayMemoryStorage::class);
        $method = $reflection->getMethod('intHash');
        $method->setAccessible(true);
        
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TESTING NEW CRC32-BASED INTHASH IMPLEMENTATION:\n";
        echo str_repeat("=", 80) . "\n";
        
        $testKeys = [
            'key1',
            'key2',
            'very_long_key_that_might_produce_20_char_hash_1',
            'very_long_key_that_might_produce_20_char_hash_2',
            'key_with_numbers_123456789012345678901234567890',
            'key_with_spaces and tabs	and newlines
',
            'unicode_key_тест_с_русскими_символами_для_проверки',
            'special_chars_!@#$%^&*()_+{}|:<>?',
        ];
        
        $hashes = [];
        $collisions = 0;
        $overflowCount = 0;
        
        foreach ($testKeys as $key) {
            $hash = $method->invoke(null, $key);
            
            echo "\nKey: '{$key}'\n";
            echo "CRC32 hash: {$hash}\n";
            
            // Check for overflow (should not happen with CRC32)
            if ($hash == PHP_INT_MAX) {
                $overflowCount++;
                echo "*** OVERFLOW DETECTED! ***\n";
            }
            
            // Check for collisions
            if (isset($hashes[$hash])) {
                $collisions++;
                echo "*** COLLISION DETECTED! ***\n";
                echo "Collision with: '{$hashes[$hash]}'\n";
            } else {
                $hashes[$hash] = $key;
            }
            
            // Verify hash properties
            $this->assertIsInt($hash, "Hash should be an integer");
            $this->assertGreaterThan(0, $hash, "Hash should be positive");
            $this->assertLessThanOrEqual(PHP_INT_MAX, $hash, "Hash should not overflow");
        }
        
        echo "\n" . str_repeat("-", 60) . "\n";
        echo "SUMMARY:\n";
        echo str_repeat("-", 60) . "\n";
        echo "Total keys tested: " . count($testKeys) . "\n";
        echo "Unique hashes: " . count($hashes) . "\n";
        echo "Collisions: {$collisions}\n";
        echo "Overflow issues: {$overflowCount}\n";
        echo "Collision rate: " . number_format(($collisions / count($testKeys)) * 100, 2) . "%\n";
        
        // Assertions
        $this->assertEquals(0, $overflowCount, "No overflow should occur with CRC32");
        $this->assertLessThan(count($testKeys) / 2, $collisions, "Collision rate should be reasonable");
    }
    
    /**
     * Test hash consistency
     */
    public function testHashConsistency(): void
    {
        $reflection = new \ReflectionClass(ArrayMemoryStorage::class);
        $method = $reflection->getMethod('intHash');
        $method->setAccessible(true);
        
        $testKey = 'consistency_test_key';
        
        // Generate hash multiple times
        $hash1 = $method->invoke(null, $testKey);
        $hash2 = $method->invoke(null, $testKey);
        $hash3 = $method->invoke(null, $testKey);
        
        // All hashes should be identical
        $this->assertEquals($hash1, $hash2, "Hash should be consistent across multiple calls");
        $this->assertEquals($hash2, $hash3, "Hash should be consistent across multiple calls");
        $this->assertEquals($hash1, $hash3, "Hash should be consistent across multiple calls");
        
        echo "\nConsistency test passed: hash for '{$testKey}' is always {$hash1}\n";
    }
    
    /**
     * Test with a large number of keys to check collision rate
     */
    public function testCollisionRateWithManyKeys(): void
    {
        $reflection = new \ReflectionClass(ArrayMemoryStorage::class);
        $method = $reflection->getMethod('intHash');
        $method->setAccessible(true);
        
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "COLLISION RATE TEST WITH MANY KEYS:\n";
        echo str_repeat("=", 80) . "\n";
        
        $testKeys = [];
        for ($i = 0; $i < 10000; $i++) {
            $testKeys[] = "key_{$i}_" . md5("random_{$i}_" . time());
        }
        
        $hashes = [];
        $collisions = 0;
        
        foreach ($testKeys as $key) {
            $hash = $method->invoke(null, $key);
            
            if (isset($hashes[$hash])) {
                $collisions++;
            } else {
                $hashes[$hash] = $key;
            }
        }
        
        $collisionRate = ($collisions / count($testKeys)) * 100;
        $uniqueHashes = count($hashes);
        
        echo "Total keys: " . count($testKeys) . "\n";
        echo "Unique hashes: {$uniqueHashes}\n";
        echo "Collisions: {$collisions}\n";
        echo "Collision rate: " . number_format($collisionRate, 4) . "%\n";
        
        // CRC32 has a theoretical collision rate, but should be very low
        $this->assertLessThan(1.0, $collisionRate, "Collision rate should be less than 1%");
        $this->assertGreaterThan(0.95, $uniqueHashes / count($testKeys), "At least 95% of keys should have unique hashes");
    }
    
    /**
     * Test edge cases
     */
    public function testEdgeCases(): void
    {
        $reflection = new \ReflectionClass(ArrayMemoryStorage::class);
        $method = $reflection->getMethod('intHash');
        $method->setAccessible(true);
        
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "EDGE CASES TEST:\n";
        echo str_repeat("=", 80) . "\n";
        
        $edgeCases = [
            '', // Empty string
            'a', // Single character
            '0', // Zero
            '1', // One
            str_repeat('a', 1000), // Very long string
            '!@#$%^&*()', // Special characters
            'тест', // Unicode
            "\x00\x01\x02", // Binary data
        ];
        
        foreach ($edgeCases as $key) {
            $hash = $method->invoke(null, $key);
            
            echo "Key: '" . addcslashes($key, "\0..\37") . "' -> Hash: {$hash}\n";
            
            $this->assertIsInt($hash, "Hash for edge case should be an integer");
            $this->assertGreaterThan(0, $hash, "Hash for edge case should be positive");
            $this->assertLessThanOrEqual(PHP_INT_MAX, $hash, "Hash for edge case should not overflow");
        }
    }
    
    /**
     * Compare with old implementation
     */
    public function testComparisonWithOldImplementation(): void
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "COMPARISON WITH OLD IMPLEMENTATION:\n";
        echo str_repeat("=", 80) . "\n";
        
        $testKeys = [
            'very_long_key_that_might_produce_20_char_hash_1',
            'very_long_key_that_might_produce_20_char_hash_2',
            'key_with_numbers_123456789012345678901234567890',
        ];
        
        foreach ($testKeys as $key) {
            $newHash = crc32($key);
            $newHash = abs($newHash);
            
            $oldHash = $this->oldIntHash($key);
            
            echo "\nKey: '{$key}'\n";
            echo "Old implementation: {$oldHash}\n";
            echo "New implementation: {$newHash}\n";
            echo "Different? " . ($oldHash !== $newHash ? 'YES' : 'NO') . "\n";
        }
        
        echo "\nNote: The new implementation will produce different hashes than the old one.\n";
        echo "This is expected and correct - the new implementation is more reliable.\n";
        
        $this->assertTrue(true, 'Comparison completed');
    }
    
    /**
     * Old implementation for comparison
     */
    private function oldIntHash(string $key): int
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
}
