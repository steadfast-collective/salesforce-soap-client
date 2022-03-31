<?php

namespace PhpArsenal\SoapClient\Result;

class DescribeGlobalResult
{
    public $encoding;
    public $maxBatchSize;
    /** @var DescribeGlobalSObjectResult[] */
    public $sobjects = [];
}
