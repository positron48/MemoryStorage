<?php
require "classes/ArrayMemoryStorage.php";

/**
 * Class Test
 * @covers \positron48\MemoryStorage\ArrayMemoryStorage
 */
class Test extends \PHPUnit\Framework\TestCase
{
    /**
     * @throws Exception
     */
    public function testOne()
    {
        $memory = new \positron48\MemoryStorage\ArrayMemoryStorage('1_counter', 3);
        $this->assertEquals([-1, -1, -1], $memory->get());

        $time = time();
        $memory->set([$time, $time, 0]);
        $this->assertEquals([$time, $time, 0], $memory->get());

        $memory->set([$time + 5, $time + 5, 0]);
        $this->assertEquals([$time + 5, $time + 5, 0], $memory->get());

        $memory->remove();
    }

    /**
     * @throws Exception
     */
    public function testMany()
    {
        $memory = new \positron48\MemoryStorage\ArrayMemoryStorage('1_counter', 3);
        $memory2 = new \positron48\MemoryStorage\ArrayMemoryStorage('2_counter', 3);
        $this->assertEquals([-1, -1, -1], $memory->get());
        $this->assertEquals([-1, -1, -1], $memory2->get());

        $time = time();
        $memory->set([$time, $time, 0]);
        $this->assertEquals([$time, $time, 0], $memory->get());
        $this->assertEquals([-1, -1, -1], $memory2->get());

        $memory2->set([$time, $time, 0]);
        $this->assertEquals([$time, $time, 0], $memory->get());
        $this->assertEquals([$time, $time, 0], $memory2->get());

        $memory->set([$time + 5, $time + 5, 0]);
        $this->assertEquals([$time + 5, $time + 5, 0], $memory->get());
        $this->assertEquals([$time, $time, 0], $memory2->get());

        $memory->remove();
        $memory2->remove();
    }

    public function testFail()
    {
        $memory = new \positron48\MemoryStorage\ArrayMemoryStorage('1_counter', 3);

        $memory->remove();

        try {
            $memory->get();
        } catch (Exception $e) {
            $this->assertEquals('Could not read from shared memory.', $e->getMessage());
        }
        return false;
    }
}
