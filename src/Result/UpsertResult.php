<?php

namespace PhpArsenal\SoapClient\Result;

/**
 * Upsert result.
 *
 * @see http://www.salesforce.com/us/developer/docs/api/Content/sforce_api_calls_upsert_upsertresult.htm
 */
class UpsertResult extends SaveResult
{
    /**
     * @var bool
     */
    protected $created;

    public function isCreated()
    {
        return $this->created;
    }
}
