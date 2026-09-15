<?php

namespace App\CoreFacturalo\WS\Services;

class GreServiceException extends \RuntimeException
{
    protected $sunatCode;

    public function __construct($sunatCode, $message)
    {
        $this->sunatCode = (string) $sunatCode;
        parent::__construct($message);
    }

    public function getSunatCode()
    {
        return $this->sunatCode;
    }
}
