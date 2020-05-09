<?php

namespace Knik\GRcon\Interfaces;

interface ConfigurableAdapterInterface
{
    /**
     * ConfigurableAdapterInterface constructor.
     * @param array $config
     */
    public function __construct(array $config);

    /**
     * @param array $config
     * @return $this
     */
    public function setConfig(array $config);
}