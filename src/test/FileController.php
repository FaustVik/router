<?php

namespace FaustVik\Router\test;

final class FileController extends BaseController
{
    private string $st = '';

    public function before()
    {
        $this->st = $_GET['name'] ?? 'TEst';
    }

    public function afterAction()
    {
        echo '<br>DDD<br>';
    }

    public function index(string $id, int $fk): void
    {
        echo "Hello ". $this->st . " | ". $id .' | '. $fk . PHP_EOL;
    }
}
