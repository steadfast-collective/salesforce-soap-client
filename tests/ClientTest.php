<?php

namespace PhpArsenal\SoapClient\Tests;

use PhpArsenal\SoapClient\Client;
use PhpArsenal\SoapClient\Request;
use PhpArsenal\SoapClient\Result;
use PhpArsenal\SoapClient\Result\LoginResult;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ClientTest extends TestCase
{
    public function testDelete()
    {
        $deleteResult = $this->createObjectMock(new Result\DeleteResult(), [
            'id' => '001M0000008tWTFIA2',
            'success' => true,
        ]);

        $result = new \stdClass;
        $result->result = [$deleteResult];

        $soapClient = $this->getSoapClient(['delete']);
        $soapClient->expects($this->once())
            ->method('delete')
            ->with(['ids' => ['001M0000008tWTFIA2']])
            ->will($this->returnValue($result));

        $this->getClient($soapClient)->delete(['001M0000008tWTFIA2']);
    }

    public function testQuery()
    {
        $soapClient = $this->getSoapClient(['query']);

        $result = $this->getResultMock(new Result\QueryResult, [
            'size' => 1,
            'done' => true,
            'records' => [
                (object) [
                    'Id'    => '001M0000008tWTFIA2',
                    'Name'  => 'Company',
                ],
            ],
        ]);

        $soapClient->expects($this->any())
                ->method('query')
                ->will($this->returnValue($result));

        $client = new Client($soapClient, 'username', 'password', 'token');
        $result = $client->query('Select Name from Account Limit 1');
        $this->assertInstanceOf('PhpArsenal\SoapClient\Result\RecordIterator', $result);
        $this->assertEquals(1, $result->count());
    }

    public function testInvalidQueryThrowsSoapFault()
    {
        $soapClient = $this->getSoapClient(['query']);
        $soapClient
            ->expects($this->once())
            ->method('query')
            ->will($this->throwException(new \SoapFault('C', "INVALID_FIELD:
Select aId, Name from Account LIMIT 1
       ^
ERROR at Row:1:Column:8
No such column 'aId' on entity 'Account'. If you are attempting to use a custom field, be sure to append the '__c' after the custom field name. Please reference your WSDL or the describe call for the appropriate names.")));

        $client = $this->getClient($soapClient);

        $this->expectException('\SoapFault');
        $client->query('Select NonExistingField from Account');
    }

    public function testInvalidUpdateResultsInError()
    {
        $error = $this->createObjectMock(new Result\Error(), [
            'fields' => ['Id'],
            'message' => 'Account ID: id value of incorrect type: 001M0000008tWTFIA3',
            'statusCode' => 'MALFORMED_ID',
        ]);

        $saveResult = $this->createObjectMock(new Result\SaveResult(), [
            'errors' => [$error],
            'success' => false,
        ]);

        $result = new \stdClass();
        $result->result = [$saveResult];

        $soapClient = $this->getSoapClient(['update']);
        $soapClient
            ->expects($this->once())
            ->method('update')
            ->will($this->returnValue($result));

        $this->expectException('\PhpArsenal\SoapClient\Exception\SaveException');
        $this->getClient($soapClient)->update([
            (object) [
                'Id'    => 'invalid-id',
                'Name'  => 'Some name',
            ],
        ], 'Account');
    }

    public function testMergeMustThrowException()
    {
        $soapClient = $this->getSoapClient(['merge']);
        $this->expectException('\InvalidArgumentException');
        $this->expectExceptionMessage('must be an instance of');
        $this->getClient($soapClient)->merge([new \stdClass], 'Account');
    }

    public function testMerge()
    {
        $soapClient = $this->getSoapClient(['merge']);

        $mergeRequest = new Request\MergeRequest();
        $masterRecord = new \stdClass();
        $masterRecord->Id = '001M0000007UvSjIAK';
        $masterRecord->Name = 'This will be the new name';
        $mergeRequest->masterRecord = $masterRecord;
        $mergeRequest->recordToMergeIds = ['001M0000008uw8JIAQ'];

        $mergeResult = $this->createObjectMock(new Result\MergeResult(), [
            'id' => '001M0000007UvSjIAK',
            'mergedRecordIds' => ['001M0000008uw8JIAQ'],
            'success' => true,
        ]);

        $result = new \stdClass();
        $result->result = [$mergeResult];

        $soapClient
            ->expects($this->any())
            ->method('merge')
            ->will($this->returnValue($result));

        $this->getClient($soapClient)->merge([$mergeRequest], 'Account');
    }

    public function testWithEventDispatcher()
    {
        $this->markTestSkipped('Some code commented out in the client?');

        $response = new \stdClass();

        $error = $this->createObjectMock(new Result\Error(), [
            'fields' => ['Id'],
            'message' => 'Account ID: id value of incorrect type: 001M0000008tWTFIA3',
            'statusCode' => 'MALFORMED_ID',
        ]);

        $saveResult = $this->createObjectMock(new Result\SaveResult(), [
            'errors' => [$error],
            'success' => false,
        ]);

        $response->result = [$saveResult];

        $soapClient = $this->getSoapClient(['create']);
        $soapClient
            ->expects($this->once())
            ->method('create')
            ->will($this->returnValue($response));

        $client = $this->getClient($soapClient);

        $dispatcher = $this
            ->getMockBuilder('\Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $c = new \stdClass();
        $c->AccountId = '123';

        $params = [
            'sObjects'  => [new \SoapVar($c, SOAP_ENC_OBJECT, 'Contact', Client::SOAP_NAMESPACE)],
        ];

//        $dispatcher
//            ->expects($this->at(0))
//            ->method('dispatch')
//            ->with('php_force.soap_client.request', new Event\RequestEvent('create', $params));

        $dispatcher
            ->expects($this->at(1))
            ->method('dispatch');

//        $dispatcher
//            ->expects($this->at(2))
//            ->method('dispatch')
//            ->with('php_force.soap_client.error');

        $this->expectException('\PhpArsenal\SoapClient\Exception\SaveException');

        $client->setEventDispatcher($dispatcher);
        $client->create([$c], 'Contact');
    }

    protected function getClient(\SoapClient $soapClient)
    {
        return new Client($soapClient, 'username', 'password', 'token');
    }

    protected function getSoapClient($methods)
    {
        $soapClient = $this->getMockBuilder('PhpArsenal\SoapClient\Soap\SoapClient')
            ->setMethods(array_merge($methods, ['login']))
            ->setConstructorArgs([__DIR__ . '/Fixtures/sandbox.enterprise.wsdl.xml'])
            ->getMock();

        $result = $this->getResultMock(new LoginResult(), [
            'sessionId' => '123',
            'serverUrl' => 'http://dinges',
        ]);

        $soapClient
            ->expects($this->any())
            ->method('login')
            ->will($this->returnValue($result));

        return $soapClient;
    }

    /**
     * Set a protected property on an object for testing purposes.
     *
     * @param  object  $object  Object
     * @param  string  $property  Property name
     * @param  mixed  $value  Value
     */
    protected function setProperty($object, $property, $value)
    {
        $reflClass = new ReflectionClass($object);
        $reflProperty = $reflClass->getProperty($property);
        $reflProperty->setAccessible(true);
        $reflProperty->setValue($object, $value);

        return $this;
    }

    protected function createObjectMock($object, array $values = [])
    {
        foreach ($values as $key => $value) {
            $this->setProperty($object, $key, $value);
        }

        return $object;
    }

    protected function getResultMock($object, array $values = [])
    {
        $mock = $this->createObjectMock($object, $values);

        $result = new \stdClass();
        $result->result = $mock;

        return $result;
    }
}
