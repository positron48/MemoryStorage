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
            
            // Check for overflow (should not happen with CRC32)
            if ($hash == PHP_INT_MAX) {
                $overflowCount++;
            }
            
            // Check for collisions
            if (isset($hashes[$hash])) {
                $collisions++;
            } else {
                $hashes[$hash] = $key;
            }
            
            // Verify hash properties
            $this->assertIsInt($hash, "Hash should be an integer");
            $this->assertGreaterThan(0, $hash, "Hash should be positive");
            $this->assertLessThanOrEqual(PHP_INT_MAX, $hash, "Hash should not overflow");
        }
        
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
    }
    
    /**
     * Test with a large number of keys to check collision rate
     */
    public function testCollisionRateWithManyKeys(): void
    {
        $reflection = new \ReflectionClass(ArrayMemoryStorage::class);
        $method = $reflection->getMethod('intHash');
        $method->setAccessible(true);
        
        $testKeys = [];
        for ($i = 0; $i < 1000; $i++) {
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
            
            $this->assertIsInt($hash, "Hash for edge case should be an integer");
            $this->assertGreaterThan(0, $hash, "Hash for edge case should be positive");
            $this->assertLessThanOrEqual(PHP_INT_MAX, $hash, "Hash for edge case should not overflow");
        }
    }
    
    /**
     * Test that new implementation produces different hashes than old one
     */
    public function testNewImplementationProducesDifferentHashes(): void
    {
        $reflection = new \ReflectionClass(ArrayMemoryStorage::class);
        $method = $reflection->getMethod('intHash');
        $method->setAccessible(true);
        
        $testKeys = [
            'very_long_key_that_might_produce_20_char_hash_1',
            'very_long_key_that_might_produce_20_char_hash_2',
            'key_with_numbers_123456789012345678901234567890',
        ];
        
        foreach ($testKeys as $key) {
            $newHash = $method->invoke(null, $key);
            $oldHash = $this->oldIntHash($key);
            
            // New implementation should produce different hashes
            $this->assertNotEquals($oldHash, $newHash, "New implementation should produce different hash for key: {$key}");
        }
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
