<?php

namespace FaustVik\Router\interfaces\Additional;

interface AdditionalActionCollectionInterface
{
    public function addBeforeRun(AdditionalActionInterface...$actions): void;

    public function addAfterRun(AdditionalActionInterface...$actions): void;

    public function getBeforeList(): array;

    public function getAfterList(): array;
}
