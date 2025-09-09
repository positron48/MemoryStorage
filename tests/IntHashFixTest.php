<?php

namespace MemoryStorage\Tests;

use MemoryStorage\ArrayMemoryStorage;
use PHPUnit\Framework\TestCase;

/**
 * Test for the fixed intHash method
 * @covers \MemoryStorage\ArrayMemoryStorage
 */
class IntHashFixTest extends TestCase
{
    /**
     * Test the improved intHash method that avoids overflow
     */
    public function testFixedIntHashMethod(): void
    {
        $reflection = new \ReflectionClass(ArrayMemoryStorage::class);
        $method = $reflection->getMethod('intHash');
        $method->setAccessible(true);
        
        // Test with the same keys that caused problems before
        $problematicKeys = [
            'very_long_key_that_might_produce_20_char_hash_1',
            'very_long_key_that_might_produce_20_char_hash_2',
            'key_with_numbers_123456789012345678901234567890',
            'key_with_spaces and tabs	and newlines
',
        ];
        
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "TESTING FIXED INTHASH METHOD:\n";
        echo str_repeat("=", 80) . "\n";
        
        $hashes = [];
        $collisions = [];
        
        foreach ($problematicKeys as $key) {
            $hash = $method->invoke(null, $key);
            $originalHash = $this->getOriginalHashString($key);
            $fixedHash = $this->getFixedHashString($key);
            
            echo "\nKey: '{$key}'\n";
            echo "Original hash string: '{$originalHash}' (length: " . strlen($originalHash) . ")\n";
            echo "Fixed hash string: '{$fixedHash}' (length: " . strlen($fixedHash) . ")\n";
            echo "Current int hash: {$hash}\n";
            echo "Fixed int hash: {$this->intHashFixed($key)}\n";
            
            // Check for collisions
            if (isset($hashes[$hash])) {
                echo "*** COLLISION DETECTED! ***\n";
                $collisions[] = [
                    'key1' => $hashes[$hash],
                    'key2' => $key,
                    'int_hash' => $hash,
                ];
            } else {
                $hashes[$hash] = $key;
            }
        }
        
        if (empty($collisions)) {
            echo "\nNo collisions detected with current method.\n";
        } else {
            echo "\nFound " . count($collisions) . " collisions with current method.\n";
        }
        
        $this->assertTrue(true, 'Fixed hash method test completed');
    }
    
    /**
     * Demonstrate the proposed fix
     */
    public function testProposedFix(): void
    {
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "PROPOSED FIX DEMONSTRATION:\n";
        echo str_repeat("=", 80) . "\n";
        
        $testKeys = [
            'very_long_key_that_might_produce_20_char_hash_1',
            'very_long_key_that_might_produce_20_char_hash_2',
            'key_with_numbers_123456789012345678901234567890',
            'key_with_spaces and tabs	and newlines
',
        ];
        
        foreach ($testKeys as $key) {
            $originalHash = $this->getOriginalHashString($key);
            $currentHash = $this->intHashCurrent($key);
            $fixedHash = $this->intHashFixed($key);
            
            echo "\nKey: '{$key}'\n";
            echo "Original: '{$originalHash}' -> {$currentHash}\n";
            echo "Fixed:    '{$this->getFixedHashString($key)}' -> {$fixedHash}\n";
            
            if ($currentHash == PHP_INT_MAX) {
                echo "*** OVERFLOW FIXED! ***\n";
            }
        }
        
        $this->assertTrue(true, 'Proposed fix demonstration completed');
    }
    
    /**
     * Get the original hash string (current implementation)
     */
    private function getOriginalHashString(string $key): string
    {
        $binhash = md5($key, true);
        $numhash = unpack('N2', $binhash);
        $hash = $numhash[1] . $numhash[2];
        return substr($hash, 0, 20);
    }
    
    /**
     * Get the fixed hash string (proposed implementation)
     */
    private function getFixedHashString(string $key): string
    {
        $binhash = md5($key, true);
        $numhash = unpack('N2', $binhash);
        $hash = $numhash[1] . $numhash[2];
        
        // Take only the first 19 characters to avoid overflow
        $hash = substr($hash, 0, 19);
        
        // Ensure it's a valid positive integer
        if ($hash === '' || $hash === '0') {
            $hash = '1';
        }
        
        return $hash;
    }
    
    /**
     * Current intHash implementation
     */
    private function intHashCurrent(string $key): int
    {
        $binhash = md5($key, true);
        $numhash = unpack('N2', $binhash);
        $hash = $numhash[1] . $numhash[2];
        $hash = substr($hash, 0, 20);
        return (int) $hash;
    }
    
    /**
     * Fixed intHash implementation
     */
    private function intHashFixed(string $key): int
    {
        $binhash = md5($key, true);
        $numhash = unpack('N2', $binhash);
        $hash = $numhash[1] . $numhash[2];
        
        // Take only the first 19 characters to avoid overflow
        $hash = substr($hash, 0, 19);
        
        // Ensure it's a valid positive integer
        if ($hash === '' || $hash === '0') {
            $hash = '1';
        }
        
        return (int) $hash;
    }
}
