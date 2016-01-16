<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Cache;

use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Core\Configuration\Compiler;
use Monolog\Logger;

class Manager
{

    /**
     * @var Cache[]
     */
    private $caches = [];

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @param Logger $logger
     * @param Factory $factory
     */
    public function __construct(Logger $logger, Factory $factory)
    {
        $this->logger = $logger;
        $this->factory = $factory;
    }

    public function flushAll()
    {
        foreach ($this->caches as $cache) {
            $cache->flushAll();
        }

        return true;
    }

    /**
     * @param string $label
     * @param string $name
     * @param array  $options
     *
     * @return Cache
     */
    public function factory($label, $name, array $options)
    {
        if (! isset($options['namespace']) || ! is_string($options['namespace'])) {
            $options['namespace'] = md5(gethostname() . '-' . __DIR__);
        }

        $cacheHash = $this->getCacheHash($label, $name, $options);

        if (isset($this->caches[$cacheHash])) {
            return $this->caches[$cacheHash];
        }

        try {
            $cache = $this->factory->create($name, $options);
        } catch (RuntimeException $e) {
            $this->logger->error($e->getMessage());
            $cache = $this->factory->create('array', $options);
        }

        $cache->setNamespace($options['namespace']);

        $this->caches[$cacheHash] = $cache;

        return $cache;
    }

    private function getCacheHash($label, $name, array $options)
    {
        return md5($label . $name .serialize($options));
    }
}
