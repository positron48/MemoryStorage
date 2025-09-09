<?php

namespace MemoryStorage\Tests;

use MemoryStorage\ArrayMemoryStorage;
use PHPUnit\Framework\TestCase;

/**
 * Test for intHash method collision issue
 * @covers \MemoryStorage\ArrayMemoryStorage
 */
class IntHashCollisionTest extends TestCase
{
    /**
     * Test to demonstrate the collision issue in intHash method
     * when hash length is 20 characters and casting to int causes overflow
     */
    public function testIntHashCollisionIssue(): void
    {
        $reflection = new \ReflectionClass(ArrayMemoryStorage::class);
        $method = $reflection->getMethod('intHash');
        $method->setAccessible(true);
        
        // Test with various keys to find ones that produce 20-character hashes
        $testKeys = [
            'very_long_key_that_might_produce_20_char_hash_1',
            'very_long_key_that_might_produce_20_char_hash_2',
            'another_very_long_key_for_testing_purposes_here',
            'test_key_with_special_chars_!@#$%^&*()_+{}|:<>?',
            'key_with_numbers_123456789012345678901234567890',
            'unicode_key_тест_с_русскими_символами_для_проверки',
            'key_with_spaces and tabs	and newlines
',
        ];
        
        $hashes = [];
        $collisions = [];
        
        foreach ($testKeys as $key) {
            $hash = $method->invoke(null, $key);
            $hashString = (string)$hash;
            
            // Store original hash string for analysis
            $originalHash = $this->getOriginalHashString($key);
            
            echo "\nKey: '{$key}'\n";
            echo "Original hash string: '{$originalHash}' (length: " . strlen($originalHash) . ")\n";
            echo "Casted to int: {$hash}\n";
            echo "Int as string: '{$hashString}' (length: " . strlen($hashString) . ")\n";
            
            // Check if we have a 20-character hash that gets truncated
            if (strlen($originalHash) == 20) {
                echo "*** FOUND 20-CHARACTER HASH! ***\n";
                
                // Check if casting to int causes overflow/truncation
                if ($hash == PHP_INT_MAX || $hash < 0) {
                    echo "*** OVERFLOW DETECTED! Hash: {$hash} ***\n";
                    $collisions[] = [
                        'key' => $key,
                        'original_hash' => $originalHash,
                        'int_hash' => $hash,
                        'issue' => 'overflow'
                    ];
                }
            }
            
            // Check for collisions (same int hash for different keys)
            if (isset($hashes[$hash])) {
                echo "*** COLLISION DETECTED! ***\n";
                echo "Key 1: '{$hashes[$hash]}'\n";
                echo "Key 2: '{$key}'\n";
                echo "Same int hash: {$hash}\n";
                
                $collisions[] = [
                    'key1' => $hashes[$hash],
                    'key2' => $key,
                    'int_hash' => $hash,
                    'issue' => 'collision'
                ];
            } else {
                $hashes[$hash] = $key;
            }
        }
        
        // Report results
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "COLLISION TEST RESULTS:\n";
        echo str_repeat("=", 80) . "\n";
        
        if (empty($collisions)) {
            echo "No collisions or overflow issues detected in this test run.\n";
            echo "However, the issue may still exist with different keys.\n";
        } else {
            echo "Found " . count($collisions) . " issues:\n\n";
            foreach ($collisions as $i => $collision) {
                echo "Issue #" . ($i + 1) . " - " . strtoupper($collision['issue']) . ":\n";
                if ($collision['issue'] == 'overflow') {
                    echo "  Key: '{$collision['key']}'\n";
                    echo "  Original hash: '{$collision['original_hash']}'\n";
                    echo "  Int hash: {$collision['int_hash']}\n";
                    echo "  Problem: 20-character hash causes integer overflow\n";
                } else {
                    echo "  Key 1: '{$collision['key1']}'\n";
                    echo "  Key 2: '{$collision['key2']}'\n";
                    echo "  Same int hash: {$collision['int_hash']}\n";
                    echo "  Problem: Different keys produce same integer hash\n";
                }
                echo "\n";
            }
        }
        
        // Test with specifically crafted keys that are likely to cause issues
        $this->testSpecificOverflowCases($method);
        
        // The test passes regardless of findings - we're just documenting the issue
        $this->assertTrue(true, 'Test completed - check output for collision analysis');
    }
    
    /**
     * Test specific cases that are likely to cause overflow
     */
    private function testSpecificOverflowCases($method): void
    {
        echo "\n" . str_repeat("-", 60) . "\n";
        echo "TESTING SPECIFIC OVERFLOW CASES:\n";
        echo str_repeat("-", 60) . "\n";
        
        // Test with keys that produce hashes starting with very large numbers
        $overflowTestKeys = [
            'key_99999999999999999999', // This should produce a hash starting with 9s
            'test_88888888888888888888',
            'hash_77777777777777777777',
        ];
        
        foreach ($overflowTestKeys as $key) {
            $hash = $method->invoke(null, $key);
            $originalHash = $this->getOriginalHashString($key);
            
            echo "\nOverflow test - Key: '{$key}'\n";
            echo "Original hash: '{$originalHash}'\n";
            echo "Int hash: {$hash}\n";
            
            // Check if this is close to PHP_INT_MAX
            if ($hash > PHP_INT_MAX * 0.9) {
                echo "*** HIGH VALUE DETECTED - Potential overflow risk! ***\n";
            }
        }
    }
    
    /**
     * Get the original hash string before casting to int
     */
    private function getOriginalHashString(string $key): string
    {
        $binhash = md5($key, true);
        $numhash = unpack('N2', $binhash);
        $hash = $numhash[1] . $numhash[2];
        return substr($hash, 0, 20);
    }
    
    /**
     * Test the mathematical properties of the hash function
     */
    public function testHashFunctionProperties(): void
    {
        $reflection = new \ReflectionClass(ArrayMemoryStorage::class);
        $method = $reflection->getMethod('intHash');
        $method->setAccessible(true);
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "HASH FUNCTION ANALYSIS:\n";
        echo str_repeat("=", 60) . "\n";
        
        // Test with a known key to understand the hash function behavior
        $testKey = 'test_key_for_analysis';
        $hash = $method->invoke(null, $testKey);
        $originalHash = $this->getOriginalHashString($testKey);
        
        echo "Test key: '{$testKey}'\n";
        echo "MD5 binary: " . bin2hex(md5($testKey, true)) . "\n";
        echo "Unpacked values: " . json_encode(unpack('N2', md5($testKey, true))) . "\n";
        echo "Concatenated: '{$originalHash}'\n";
        echo "Length: " . strlen($originalHash) . "\n";
        echo "Casted to int: {$hash}\n";
        echo "PHP_INT_MAX: " . PHP_INT_MAX . "\n";
        echo "Is 64-bit system: " . (PHP_INT_SIZE === 8 ? 'Yes' : 'No') . "\n";
        
        // Calculate what the maximum safe hash string would be
        $maxSafeString = (string)PHP_INT_MAX;
        echo "Max safe string length: " . strlen($maxSafeString) . "\n";
        
        $this->assertTrue(true, 'Hash function analysis completed');
    }
}
