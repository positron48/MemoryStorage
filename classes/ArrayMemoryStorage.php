<?php
namespace MemoryStorage;

use malkusch\lock\mutex\SemaphoreMutex;

/**
 * Class MemoryStorage
 * @package positron48\MemoryStorage
 */
class ArrayMemoryStorage
{
    private $key;
    private $memory;
    private $length;

    private $semaphore;

    /** @var SemaphoreMutex */
    private $mutex;

    /**
     * MemoryStorage constructor.
     * @param string $key
     * @throws \Exception
     */
    public function __construct(string $key, int $length)
    {
        $this->key = self::intHash($key);
        $this->length = $length;
        $this->attach();

        // инициализируем область памяти, если это первое выполнение скрипта на сервере
        $this->mutex
            ->check(function () {
                return !$this->isBootstrapped();
            })
            ->then(function () {
                $this->bootstrap($this->getEmptyVar());
            });
    }

    /**
     * @return array of int
     * @throws \Exception
     */
    public function get()
    {
        if($this->memory === null){
            throw new \Exception("Could not read from shared memory.");
        }
        $bytes = shm_get_var($this->memory, 0);
        if ($bytes === false) {
            throw new \Exception("Could not read from shared memory.");
        }
        return array_values(unpack("i*", $bytes));
    }

    /**
     * @param string $key
     * @param array of int $data
     * @throws \Exception
     */
    public function set(array $data)
    {
        if(count($data) !== $this->length){
            throw new \Exception("Data lenght is not " . $this->length, 400);
        }

        $bytes = pack("i*", ...$data);

        if (!shm_put_var($this->memory, 0, $bytes)) {
            throw new \Exception("Could not store in shared memory.");
        }
    }

    /**
     * @throws \Exception
     */
    public function remove()
    {
        if (!shm_remove($this->memory)) {
            throw new \Exception("Could not release shared memory.");
        }
        $this->memory = null;

        if (!sem_remove($this->semaphore)) {
            throw new \Exception("Could not remove semaphore.");
        }
        $this->semaphore = null;
    }

    /**
     * @return SemaphoreMutex
     */
    public function getMutex()
    {
        return $this->mutex;
    }

    /**
     * @throws \Exception
     */
    protected function attach()
    {
        try {
            $this->semaphore = sem_get($this->key);
            $this->mutex     = new SemaphoreMutex($this->semaphore);
        } catch (\InvalidArgumentException $e) {
            throw new \Exception("Could not get semaphore id.", 0, $e);
        }

        $this->memory = shm_attach($this->key, $this->getMemSize());
        if ($this->memory === false) {
            throw new \Exception("Failed to attach to shared memory.");
        }
    }

    /**
     * @param array $data int
     * @throws \Exception
     */
    protected function bootstrap(array $data)
    {
        if ($this->memory === null) {
            $this->attach();
        }
        $this->set($data);
    }

    protected function isBootstrapped()
    {
        return $this->memory !== null && shm_has_var($this->memory, 0);
    }

    protected function getMemSize()
    {
        $header = 24; // actually 4*4 + 8
        $dataLength = (ceil(strlen(serialize($this->getEmptyVar())) / 4) * 4) + 16;
        return $header + $dataLength;
    }

    /**
     * @return array
     */
    protected function getEmptyVar(): array
    {
        $initArray = [];
        for ($i = 0; $i < $this->length; $i++) {
            $initArray[] = PHP_INT_MAX;
        }
        return $initArray;
    }

    /**
     * @param string $key
     */
    protected static function intHash(string $key)
    {
        $binhash = md5($key, true);
        $numhash = unpack('N2', $binhash);
        $hash = $numhash[1] . $numhash[2];
        $hash = substr($hash, 0, 20);

        return (int) $hash;
    }
}