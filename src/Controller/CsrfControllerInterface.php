<?php

namespace App\Controller;

interface CsrfControllerInterface
{
    public function getCsrfType(): string;
}
