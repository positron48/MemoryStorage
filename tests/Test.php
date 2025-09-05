<?php

namespace MemoryStorage\Tests;

use MemoryStorage\ArrayMemoryStorage;
use PHPUnit\Framework\TestCase;

/**
 * Legacy tests adapted for current library limitations
 * @covers \MemoryStorage\ArrayMemoryStorage
 */
class Test extends TestCase
{
    /**
     * Test basic functionality
     */
    public function testBasicFunctionality()
    {
        $storage = new ArrayMemoryStorage('1_counter', 3);
        
        // Test initial data
        $initialData = $storage->get();
        $this->assertIsArray($initialData);
        $this->assertCount(3, $initialData);
        
        // Test setting data
        $testData = [100, 200, 300];
        $storage->set($testData);
        $this->assertEquals($testData, $storage->get());
        
        // Test updating data
        $testData2 = [400, 500, 600];
        $storage->set($testData2);
        $this->assertEquals($testData2, $storage->get());
        
        $storage->remove();
    }

    /**
     * Test multiple storage instances
     */
    public function testMultipleStorageInstances()
    {
        $storage1 = new ArrayMemoryStorage('storage_1', 2);
        $storage2 = new ArrayMemoryStorage('storage_2', 2);
        
        // Set different data in each storage
        $data1 = [10, 20];
        $data2 = [30, 40];
        
        $storage1->set($data1);
        $storage2->set($data2);
        
        // Verify they don't interfere with each other
        $this->assertEquals($data1, $storage1->get());
        $this->assertEquals($data2, $storage2->get());
        
        $storage1->remove();
        $storage2->remove();
    }

    /**
     * Test mutex functionality
     */
    public function testMutexFunctionality()
    {
        $storage = new ArrayMemoryStorage('mutex_test', 2);
        $mutex = $storage->getMutex();
        
        $this->assertInstanceOf(\malkusch\lock\mutex\SemaphoreMutex::class, $mutex);
        
        // Test synchronized execution
        $executed = false;
        $mutex->synchronized(function () use (&$executed, $storage) {
            $executed = true;
            $storage->set([1, 2]);
        });
        
        $this->assertTrue($executed);
        $this->assertEquals([1, 2], $storage->get());
        
        $storage->remove();
    }
}
