<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use OpenApi\Attributes as OA;

#[OA\Info(version: "0.2.1", title: "NKCS CRM API")]
class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
