<?php

namespace PhpArsenal\SoapClient;

use PhpArsenal\SoapClient\Result\SaveResult;

/**
 * Add creates, updates and upserts to the queue, and issue them in bulk to
 * the Salesforce API.
 *
 * @author David de Boer <david@ddeboer.nl>
 */
class BulkSaver implements BulkSaverInterface
{
    /**
     * Maximum number of records that may be updated or created in one call.
     *
     * @var int
     */
    private $bulkSaveLimit = 200;

    /**
     * Maximum number of records that may be deleted in one call.
     *
     * @var int
     */
    private $bulkDeleteLimit = 200;

    /**
     * Salesforce SOAP client.
     *
     * @var ClientInterface
     */
    private $client;

    private $bulkCreateRecords = [];
    private $bulkDeleteRecords = [];
    private $bulkUpdateRecords = [];
    private $bulkUpsertRecords = [];
    private $bulkUpsertMatchFields = [];
    private $results = [];

    /**
     * Construct bulk saver.
     *
     * @param  Client  $client  Salesforce client
     */
    public function __construct(ClientInterface $client)
    {
        $this->client = $client;
        $this->results['created'] = [];
        $this->results['deleted'] = [];
        $this->results['updated'] = [];
        $this->results['upserted'] = [];
    }

    /**
     * Save a record in bulk.
     *
     * @param  mixed  $record
     * @param  string  $objectType  The record type, e.g., Account
     * @param  string  $matchField  Optional match field for upserts
     * @return BulkSaver
     */
    public function save($record, $objectType, $matchField = null)
    {
        if ($matchField) {
            $this->addBulkUpsertRecord($record, $objectType, $matchField);
        } elseif (isset($record->Id) && null !== $record->Id) {
            $this->addBulkUpdateRecord($record, $objectType);
        } else {
            $this->addBulkCreateRecord($record, $objectType);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function delete($record)
    {
        if (! isset($record->Id) || ! $record->Id) {
            throw new \InvalidArgumentException(
                'Only records with an Id can be deleted'
            );
        }

        $this->addBulkDeleteRecord($record);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function flush()
    {
        if (count($this->bulkDeleteRecords) > 0) {
            $this->results['deleted'] = array_merge($this->results['deleted'], $this->flushDeletes());
        }

        foreach ($this->bulkCreateRecords as $type => $objects) {
            if (count($objects) > 0) {
                $this->results['created'] = array_merge($this->results['created'], $this->flushCreates($type));
            }
        }

        foreach ($this->bulkUpdateRecords as $type => $objects) {
            if (count($objects) > 0) {
                $this->results['updated'] = array_merge($this->results['updated'], $this->flushUpdates($type));
            }
        }

        foreach ($this->bulkUpsertRecords as $type => $objects) {
            if (count($objects) > 0) {
                $this->results['upserted'] = array_merge($this->results['upserted'], $this->flushUpserts($type));
            }
        }

        return $this->results;
    }

    /**
     * Get bulk save limit.
     *
     * @return int
     */
    public function getBulkSaveLimit()
    {
        return $this->bulkSaveLimit;
    }

    /**
     * Set bulk Save limit.
     *
     * @param  int  $bulkSaveLimit
     * @return BulkSaver
     */
    public function setBulkSaveLimit($bulkSaveLimit)
    {
        $this->bulkSaveLimit = $bulkSaveLimit;

        return $this;
    }

    /**
     * Get bulk delete limit.
     *
     * @return int
     */
    public function getBulkDeleteLimit()
    {
        return $this->bulkDeleteLimit;
    }

    /**
     * Set bulk delete limit.
     *
     * @param  int  $bulkDeleteLimit
     * @return BulkSaver
     */
    public function setBulkDeleteLimit($bulkDeleteLimit)
    {
        $this->bulkDeleteLimit = $bulkDeleteLimit;

        return $this;
    }

    /**
     * Add a record to the create queue.
     *
     * @param  sObject  $sObject
     * @param  type  $objectType
     */
    private function addBulkCreateRecord($record, $objectType)
    {
        if (isset($this->bulkCreateRecords[$objectType])
            && count($this->bulkCreateRecords[$objectType]) == $this->bulkSaveLimit) {
            $this->results['created'] = array_merge($this->results['created'], $this->flushCreates($objectType));
        }

        $this->bulkCreateRecords[$objectType][] = $record;
    }

    /**
     * Add a record id to the bulk delete queue.
     *
     * (Delete calls
     *
     * @param  string  $id
     */
    private function addBulkDeleteRecord($record)
    {
        if ($this->bulkDeleteLimit === count($this->bulkDeleteRecords)) {
            $this->results['deleted'] = array_merge($this->results['deleted'], $this->flushDeletes());
        }

        $this->bulkDeleteRecords[] = $record;
    }

    /**
     * Add a record to the update queue.
     *
     * @param  sObject  $sObject
     * @param  string  $objectType
     */
    private function addBulkUpdateRecord($sObject, $objectType)
    {
        if (isset($this->bulkUpdateRecords[$objectType])
            && count($this->bulkUpdateRecords[$objectType]) == $this->bulkSaveLimit) {
            $this->results['updated'] = array_merge($this->results['updated'], $this->flushUpdates($objectType));
        }

        $this->bulkUpdateRecords[$objectType][] = $sObject;
    }

    /**
     * Add a record to the update queue.
     *
     * @param  sObject  $sObject
     * @param  string  $objectType
     */
    private function addBulkUpsertRecord($sObject, $objectType, $matchField)
    {
        $this->bulkUpsertMatchFields[$objectType] = $matchField;

        if (isset($this->bulkUpsertRecords[$objectType])
            && count($this->bulkUpsertRecords[$objectType]) == $this->bulkSaveLimit) {
            $this->results['upserted'] = array_merge($this->results['upserted'], $this->flushUpserts($objectType));
        }

        $this->bulkUpsertRecords[$objectType][] = $sObject;
    }

    /**
     * Flush creates.
     *
     * @param  string  $objectType
     * @return SaveResult[]
     */
    private function flushCreates($objectType)
    {
        $result = $this->client->create($this->bulkCreateRecords[$objectType], $objectType);
        $this->bulkCreateRecords[$objectType] = [];

        return $result;
    }

    /**
     * Flush deletes.
     *
     * @return SaveResult[]
     */
    private function flushDeletes()
    {
        $ids = [];
        foreach ($this->bulkDeleteRecords as $record) {
            $ids[] = $record->Id;
        }

        $result = $this->client->delete($ids);
        $this->bulkDeleteRecords = [];

        return $result;
    }

    /**
     * Flush updates.
     *
     * @param  string  $objectType
     * @return SaveResult[]
     */
    private function flushUpdates($objectType)
    {
        $result = $this->client->update($this->bulkUpdateRecords[$objectType], $objectType);
        $this->bulkUpdateRecords[$objectType] = [];

        return $result;
    }

    /**
     * Flush upserts.
     *
     * @param  string  $objectType
     * @return SaveResult[]
     */
    private function flushUpserts($objectType)
    {
        $result = $this->client->upsert(
            $this->bulkUpsertMatchFields[$objectType],
            $this->bulkUpsertRecords[$objectType],
            $objectType);
        $this->bulkUpsertRecords[$objectType] = [];

        return $result;
    }

    /**
     * Clears the results and the records of the bulk saver.
     */
    public function clear()
    {
        $this->bulkCreateRecords = [];
        $this->bulkDeleteRecords = [];
        $this->bulkUpdateRecords = [];
        $this->bulkUpsertRecords = [];
        $this->results['created'] = [];
        $this->results['deleted'] = [];
        $this->results['updated'] = [];
        $this->results['upserted'] = [];
    }
}
