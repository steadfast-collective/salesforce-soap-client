<?php

namespace PhpArsenal\SoapClient;

use PhpArsenal\SoapClient\Plugin\LogPlugin;
use PhpArsenal\SoapClient\Soap\SoapClientFactory;
use Psr\Log\LoggerInterface;

/**
 * Salesforce SOAP client builder.
 *
 * @author David de Boer <david@ddeboer.nl>
 */
class ClientBuilder
{
    /**
     * @var LoggerInterface
     */
    protected $log;

    /**
     * Construct client builder with required parameters.
     *
     * @param  string  $wsdl  Path to your Salesforce WSDL
     * @param  string  $username  Your Salesforce username
     * @param  string  $password  Your Salesforce password
     * @param  string  $token  Your Salesforce security token
     * @param  array  $soapOptions  Further options to be passed to the SoapClient
     * @param  string  $environment  SoapClient environment. Used to disable WSDL cache for 'dev' environment
     */
    public function __construct($wsdl, $username, $password, $token, array $soapOptions = [], $environment = 'prod')
    {
        $this->wsdl = $wsdl;
        $this->username = $username;
        $this->password = $password;
        $this->token = $token;
        $this->soapOptions = $soapOptions;
        $this->environment = $environment;
    }

    /**
     * Enable logging.
     *
     * @param  LoggerInterface  $log  Logger
     * @return ClientBuilder
     */
    public function withLog(LoggerInterface $log)
    {
        $this->log = $log;

        return $this;
    }

    /**
     * Build the Salesforce SOAP client.
     *
     * @return Client
     */
    public function build()
    {
        $soapClientFactory = new SoapClientFactory();
        $soapClient = $soapClientFactory->factory($this->wsdl, $this->soapOptions, $this->environment);

        $client = new Client($soapClient, $this->username, $this->password, $this->token);

        if ($this->log) {
            $logPlugin = new LogPlugin($this->log);
            $client->getEventDispatcher()->addSubscriber($logPlugin);
        }

        return $client;
    }
}
