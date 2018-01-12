<?php
namespace KS;

/**
 * * Should daemonize, i.e., not return until killed
 * * Should output useful info to stdout
 * * Should output errors to stderr
 * * Should fork logging processes
 * * Should accept command line arguments
 * * Should accept an optional config file
 */

abstract class AbstractMessageDaemon
{
    protected $cnf;
    private $sock;
    private $cnx;

    public function __construct(\KS\BaseConfig $cnf)
    {
        $this->cnf = $cnf;
        error_reporting($this->cnf->getErrorReporting());
        ini_set('display_errors', (int)$this->cnf->getDisplayErrors());
        set_time_limit(0);
    }

    public function run()
    {
        $this->preRun();
        try {
            // Set up the socket
            if (($this->sock = \socket_create($this->cnf->getSocketDomain(), $this->cnf->getSocketType(), $this->cnf->getSocketProtocol())) === false) {
                throw new \RuntimeException("Couldn't establish a socket connection: ".\socket_strerror(\socket_last_error()));
            }
            if (\socket_bind($this->sock, $this->cnf->getSocketAddress(), $this->cnf->getSocketPort()) === false) {
                throw new \RuntimeException("Couldn't bind to socket at {$this->cnf->getSocketAddress()} ({$this->cnf->getSocketPort()}): " . \socket_strerror(\socket_last_error($this->sock)));
            }
            if (\socket_listen($this->sock, 5) === false) {
                throw new \RuntimeException("Failed to listen on socket: ".\socket_strerror(\socket_last_error($this->sock)));
            }

            $this->onListen();

            // Establish communication loop
            $shuttingDown = false;
            do {
                if (($this->cnx = \socket_accept($this->sock)) === false) {
                    throw new \RuntimeException("Error waiting for connections: ".\socket_strerror(\socket_last_error($this->sock)));
                }
                $this->onConnect();
                $buffer = '';
                do {
                    if (false === ($chunk = \socket_read($this->cnx, 2048, PHP_NORMAL_READ))) {
                        throw new \RuntimeException("Socket read from peer failed: ".\socket_strerror(\socket_last_error($this->sock)));
                    }

                    $origLen = strlen($chunk);
                    $chunk = trim($chunk, "\n\r");
                    $buffer .= $chunk;

                    // If we've received a return, time to process
                    if ($origLen > strlen($chunk)) {
                        try {
                            $this->preProcessMessage($buffer);
                            $response = $this->processMessage($buffer);
                            $this->preSendResponse($response);
                            if ($response) {
                                $this->write($response);
                            }
                            $this->postSendResponse();
                        } catch (ConnectionCloseException $e) {
                            $this->preDisconnect();
                            break;
                        } catch (ShutdownException $e) {
                            $shuttingDown = true;
                            $this->preDisconnect();
                            break;
                        } catch (UserMessageException $e) {
                            $jsonapi = [
                                'errors' => [
                                    [
                                        'status' => $e->getCode(),
                                        'title' => 'Error',
                                        'detail' => $e->getMessage(),
                                    ]
                                ]
                            ];
                            $this->preSendResponse($jsonapi);
                            $jsonapi = json_encode($jsonapi);
                            $this->write($jsonapi);
                            $this->postSendResponse();
                        }
                        $buffer = '';
                    }
                } while (true);
                \socket_close($this->cnx);
                $this->postDisconnect();
            } while (!$shuttingDown);
            $this->preShutdown();
            $this->shutdown();
            $this->postShutdown();
        } catch (\Throwable $e) {
            $this->preShutdown();
            $this->shutdown();
            $this->postShutdown();
            throw $e;
        }
    }

    abstract protected function processMessage(string $msg) : ?string;

    protected function write(string $msg) : void
    {
        \socket_write($this->cnx, $msg, strlen($msg));
    }

    protected function say(string $str, int $verbosity = 1, $resource = STDOUT)
    {
        if ($this->cnf->getVerbosity() >= $verbosity) {
            fwrite($resource, $str);
        }
    }





    // Hooks

    protected function preRun()
    {
        $this->say("\nStarting up...");
    }

    protected function onListen()
    {
        $msg = "\nListening on {$this->cnf->getSocketAddress()}";
        if ($p = $this->cnf->getSocketPort()) {
            $msg .= ":$p";
        }
        fwrite(STDOUT, $msg);
    }

    protected function onConnect()
    {
        $this->say("\nConnected to peer", 2);
    }

    protected function preProcessMessage(string $msg)
    {
        $this->say("\nGot a message!!", 3);
    }

    protected function preSendResponse($msg)
    {
        $this->say("\nGot a response!!!", 3);
        if (is_array($msg)) {
            $msg = json_encode($msg);
        }
        $this->say(" Message: $msg", 4);
    }

    protected function postSendResponse()
    {
        $this->say("\nResponse sent.", 3);
    }

    protected function preDisconnect()
    {
        $this->say("\nDisconnecting from peer", 3);
    }

    protected function postDisconnect()
    {
        $this->say("\nDisconnected. Waiting.", 2);
    }

    protected function preShutdown()
    {
        $this->say("\nPreparing to shutdown.", 3);
    }

    protected function shutdown()
    {
        $this->say("\nShutting down");
        if ($this->cnx) {
            \socket_close($this->cnx);
        }
        if ($this->sock) {
            \socket_close($this->sock);
        }
        if ($this->cnf->getSocketDomain() === AF_UNIX && file_exists($this->cnf->getSocketAddress())) {
            $this->say("\nCleaning up Unix Socket", 2);
            unlink($this->cnf->getSocketAddress());
        }
    }

    protected function postShutdown()
    {
        $this->say("\n\nGoodbye.\n");
    }
}

