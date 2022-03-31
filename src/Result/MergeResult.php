<?php

namespace PhpArsenal\SoapClient\Result;

/**
 * Merge result.
 */
class MergeResult
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var bool
     */
    protected $success;

    /**
     * @var array
     */
    protected $errors;

    /**
     * @var array
     */
    protected $mergedRecordIds;

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return bool
     */
    public function getSuccess()
    {
        return $this->success;
    }

    /**
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    public function getMergedRecordIds()
    {
        return $this->mergedRecordIds;
    }
}
