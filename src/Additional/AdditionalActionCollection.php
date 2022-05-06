<?php

namespace FaustVik\Router\Additional;

use FaustVik\Router\interfaces\Additional\AdditionalActionCollectionInterface;
use FaustVik\Router\interfaces\Additional\AdditionalActionInterface;

final class AdditionalActionCollection implements AdditionalActionCollectionInterface
{
    protected array $beforeList = [];
    protected array $afterList = [];

    public function addBeforeRun(AdditionalActionInterface...$actions): void
    {
        foreach ($actions as $action){
            $this->beforeList[$action->getClass()] = $action;
        }
    }

    public function addAfterRun(AdditionalActionInterface...$actions): void
    {
        foreach ($actions as $action){
            $this->afterList[$action->getClass()] = $action;
        }
    }

    /**
     * @return array
     */
    public function getBeforeList(): array
    {
        return $this->beforeList;
    }

    /**
     * @return array
     */
    public function getAfterList(): array
    {
        return $this->afterList;
    }
}
