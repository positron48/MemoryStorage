<?php

namespace MemoryStorage\Tests;

use MemoryStorage\ArrayMemoryStorage;
use PHPUnit\Framework\TestCase;

/**
 * Test to verify the intHash fix works correctly
 * @covers \MemoryStorage\ArrayMemoryStorage
 */
class IntHashFixVerificationTest extends TestCase
{
    /**
     * Test that the fixed intHash method no longer causes overflow
     */
    public function testIntHashNoOverflow(): void
    {
        $reflection = new \ReflectionClass(ArrayMemoryStorage::class);
        $method = $reflection->getMethod('intHash');
        $method->setAccessible(true);
        
        // Test with keys that previously caused overflow
        $problematicKeys = [
            'very_long_key_that_might_produce_20_char_hash_1',
            'very_long_key_that_might_produce_20_char_hash_2',
            'key_with_numbers_123456789012345678901234567890',
            'key_with_spaces and tabs	and newlines
',
            'test_key_99999999999999999999',
            'another_test_88888888888888888888',
        ];
        
        $hashes = [];
        $overflowCount = 0;
        
        foreach ($problematicKeys as $key) {
            $hash = $method->invoke(null, $key);
            
            // Check if hash is still causing overflow (PHP_INT_MAX)
            if ($hash == PHP_INT_MAX) {
                $overflowCount++;
                echo "WARNING: Key '{$key}' still causes overflow: {$hash}\n";
            }
            
            // Check for collisions
            if (isset($hashes[$hash])) {
                echo "INFO: Collision detected for hash {$hash} between keys '{$hashes[$hash]}' and '{$key}'\n";
            } else {
                $hashes[$hash] = $key;
            }
            
            // Verify hash is a valid positive integer
            $this->assertIsInt($hash, "Hash for key '{$key}' should be an integer");
            $this->assertGreaterThan(0, $hash, "Hash for key '{$key}' should be positive");
            $this->assertLessThanOrEqual(PHP_INT_MAX, $hash, "Hash for key '{$key}' should not overflow");
        }
        
        // Assert that no overflows occurred
        $this->assertEquals(0, $overflowCount, "No keys should cause integer overflow after the fix");
        
        echo "\nTest completed successfully:\n";
        echo "- Tested " . count($problematicKeys) . " problematic keys\n";
        echo "- Found {$overflowCount} overflow issues (should be 0)\n";
        echo "- Generated " . count($hashes) . " unique hashes\n";
    }
    
    /**
     * Test that the fix maintains hash consistency
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
     * Test that different keys produce different hashes (when possible)
     */
    public function testHashUniqueness(): void
    {
        $reflection = new \ReflectionClass(ArrayMemoryStorage::class);
        $method = $reflection->getMethod('intHash');
        $method->setAccessible(true);
        
        $testKeys = [
            'key1',
            'key2',
            'key3',
            'different_key_1',
            'different_key_2',
            'completely_different_key',
        ];
        
        $hashes = [];
        $collisionCount = 0;
        
        foreach ($testKeys as $key) {
            $hash = $method->invoke(null, $key);
            
            if (isset($hashes[$hash])) {
                $collisionCount++;
                echo "Collision: '{$hashes[$hash]}' and '{$key}' both produce hash {$hash}\n";
            } else {
                $hashes[$hash] = $key;
            }
        }
        
        echo "\nHash uniqueness test:\n";
        echo "- Tested " . count($testKeys) . " different keys\n";
        echo "- Generated " . count($hashes) . " unique hashes\n";
        echo "- Found {$collisionCount} collisions\n";
        
        // Note: Some collisions are expected due to the nature of hash functions
        // We just want to ensure the fix doesn't make collisions worse
        $this->assertLessThan(count($testKeys) / 2, $collisionCount, "Too many collisions detected");
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
        ];
        
        foreach ($edgeCases as $key) {
            $hash = $method->invoke(null, $key);
            
            $this->assertIsInt($hash, "Hash for edge case '{$key}' should be an integer");
            $this->assertGreaterThan(0, $hash, "Hash for edge case '{$key}' should be positive");
            $this->assertLessThanOrEqual(PHP_INT_MAX, $hash, "Hash for edge case '{$key}' should not overflow");
        }
    }
}
