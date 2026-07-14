<?php

namespace App\Support;

final class Accion
{
    public const int READ = 1;

    public const int CREATE = 2;

    public const int UPDATE = 4;

    public const int DELETE = 8;

    public const int ALL = 15;

    private function __construct()
    {
        //
    }
}
