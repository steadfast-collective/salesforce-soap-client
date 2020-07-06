<?php
namespace Phpforce\SoapClient\Tests;

use Phpforce\SoapClient\BulkSaver;
use Phpforce\SoapClient\Client;
use PHPUnit\Framework\TestCase;

class BulkSaverTest extends TestCase
{
    public function testCreate()
    {
        $client = $this->createMock(Client::class);

        $client
            ->expects($this->exactly(3))
            ->method('create')
            ->with($this->isType('array'), $this->equalTo('Account'))
            ->willReturn([]);

        $bulkSaver = new BulkSaver($client);

        for ($i = 0; $i < 401; $i++) {
            $record = new \stdClass();
            $record->Name = 'An account';
            $bulkSaver->save($record, 'Account');
        }
        $bulkSaver->flush();
    }

    public function testUpdate()
    {
        $client = $this->createMock(Client::class);

        $client
            ->expects($this->exactly(2))
            ->method('update')
            ->with($this->isType('array'), $this->equalTo('Account'))
            ->willReturn([]);

        $bulkSaver = new BulkSaver($client);

        for ($i = 0; $i < 400; $i++) {
            $record = new \stdClass();
            $record->Name = 'An account';
            $record->Id = 123;
            $bulkSaver->save($record, 'Account');
        }
        $bulkSaver->flush();
    }

    public function testDelete()
    {
        $tasks = array();
        for ($i = 0; $i < 202; $i++) {
            $task = new \stdClass();
            $task->Id = $i+1;
            $tasks[] = $task;
        }

        $client = $this->createMock(Client::class);
        $client->expects($this->at(0))
            ->method('delete')
            ->with(range(1, 200))
            ->willReturn([]);

        $client->expects($this->at(1))
            ->method('delete')
            ->with(range(201, 202))
            ->willReturn([]);

        $bulkSaver = new BulkSaver($client);
        foreach ($tasks as $task) {
            $bulkSaver->delete($task);
        }
        $bulkSaver->flush();
    }

    public function testDeleteWithoutIdThrowsException()
    {
        $client = $this->createMock(Client::class);
        $bulkSaver = new BulkSaver($client);
        $invalidRecord = new \stdClass();
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('Only records with an Id can be deleted');
        $bulkSaver->delete($invalidRecord);
    }

    public function testUpsert()
    {
        $client = $this->createMock(Client::class);
        $client->expects($this->exactly(2))
            ->method('upsert')
            ->with('Name', $this->isType('array'), 'Account')
            ->willReturn([]);

        $account = new \stdClass();
        $account->Name = 'Upsert this';
        $account->BillingPostalCode = '1234 AB';
        $bulkSaver = new BulkSaver($client);

        for ($i = 0; $i < 201; $i++) {
            $bulkSaver->save($account, 'Account', 'Name');
        }
        $bulkSaver->flush();
    }
}
