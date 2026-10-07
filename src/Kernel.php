<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function __construct(string $environment, bool $debug)
    {
        // MySQL DATETIME columns have no timezone: the whole application works in UTC
        date_default_timezone_set('UTC');

        parent::__construct($environment, $debug);
    }
}
