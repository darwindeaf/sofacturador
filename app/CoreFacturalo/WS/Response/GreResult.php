<?php

namespace App\CoreFacturalo\WS\Response;

class GreResult extends BillResult
{
    protected $ticket;
    protected $statusCode;
    protected $receptionDate;
    protected $rawResponse = [];

    public function getTicket()
    {
        return $this->ticket;
    }

    public function setTicket($ticket)
    {
        $this->ticket = $ticket;

        return $this;
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function setStatusCode($statusCode)
    {
        $this->statusCode = (string) $statusCode;

        return $this;
    }

    public function getReceptionDate()
    {
        return $this->receptionDate;
    }

    public function setReceptionDate($receptionDate)
    {
        $this->receptionDate = $receptionDate;

        return $this;
    }

    public function getRawResponse()
    {
        return $this->rawResponse;
    }

    public function setRawResponse(array $rawResponse)
    {
        $this->rawResponse = $rawResponse;

        return $this;
    }

    public function isPending()
    {
        return $this->statusCode === '98';
    }
}
