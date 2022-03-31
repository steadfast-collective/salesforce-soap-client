<?php

namespace PhpArsenal\SoapClient\Result;

class GetUpdatedResult
{
    protected $ids = [];

    protected $latestDateCovered;

    /**
     * @return array
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * @return \DateTime
     */
    public function getLatestDateCovered()
    {
        return $this->latestDateCovered;
    }
}
