<?php
/**
 * Created by PhpStorm.
 * User: yf
 * Date: 2018-12-27
 * Time: 14:17
 */
use Swoole\Coroutine\Client;

class UnixClient
{
    private $client = null;

    function __construct(string $unixSock)
    {
        $this->client = new Client(SWOOLE_UNIX_STREAM);
        $this->client->set(
            [
                'open_length_check' => true,
                'package_length_type'   => 'N',
                'package_length_offset' => 0,
                'package_body_offset'   => 4,
                'package_max_length'    => 1024*1024
            ]
        );
        $this->client->connect($unixSock, null, 3);
    }

    function client():Client
    {
        return $this->client;
    }

    function __destruct()
    {
        // TODO: Implement __destruct() method.
        if($this->client->isConnected()){
            $this->client->close();
        }
        $this->client = null;
    }

    function send(string $rawData)
    {
        if($this->client->isConnected()){
            return $this->client->send(Protocol::pack($rawData));
        }else{
            return false;
        }
    }

    function recv(float $timeout = 0.1)
    {
        if($this->client->isConnected()){
            $ret = $this->client->recv($timeout);
            if(!empty($ret)){
                return Protocol::unpack($ret);
            }else{
                return null;
            }
        }else{
            return null;
        }
    }
    
    public static function sendAndRecv(Command $command,$timeout,$socketFile)
    {
        $client = new UnixClient($socketFile);
        $ret = $client->send(ActorFactory::pack($command));
        if($ret === false){
            throw new Exception('unix send data error in error code:'.$client->client()->errCode);
        }
        
        $ret = $client->recv($timeout);
        if(!empty($ret)){
            return ActorFactory::unpack($ret);
        }
        
        return null;
    }
}