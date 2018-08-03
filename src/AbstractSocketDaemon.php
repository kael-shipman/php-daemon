<?php
namespace KS;
declare(ticks = 1);

abstract class AbstractSocketDaemon extends AbstractDaemon
{
    private $listeningSocket;
    private $initialized = false;

    public function run()
    {
        $this->init();
        $this->preRun();
        $this->log("Begin listening on socket...", LOG_INFO, [ "syslog", STDOUT ], true);
        try {
            // Set up the socket
            if (($this->listeningSocket = BaseSocket::newSocket($this->config->getSocketDomain(), $this->config->getSocketType(), $this->config->getSocketProtocol())) === false) {
                throw new \RuntimeException("Couldn't create a listening socket: ".\socket_strerror(\socket_last_error()));
            }
            if ($this->listeningSocket->setBlocking(false) === Result::FAILED) {
                throw new \RuntimeException("Couldn't make listening socket non-blocking: ".\socket_strerror(\socket_last_error()));
            }
            if ($this->listeningSocket->bind($this->config->getSocketAddress(), $this->config->getSocketPort()) === Result::FAILED) {
                throw new \RuntimeException("Couldn't bind to socket at {$this->config->getSocketAddress()} ({$this->config->getSocketPort()}): " . $this->listeningSocket->getLastErrorStr());
            }
            if ($this->listeningSocket->listen(5) === Result::FAILED) {
                throw new \RuntimeException("Failed to listen on socket: ".$this->listeningSocket->getLastErrorStr());
            }

            $this->onListen();

            $socketLoop = new SelectSocketLoop();
            $timeout = new TimeDuration(TimeDuration::SECONDS, 1);

            $socketLoop->watch($this->listeningSocket);

            // Establish communication loop
            $shuttingDown = false;
            do {
                $socketsReady = $socketLoop->waitForEvents($timeout);

                if ($socketsReady === Result::FAILED) {
                    throw new \RuntimeException("Error processing socket_select: '".BaseSocket::getLastGlobalErrorStr()."'");
                }
                if (\count($socketsReady) === 0) {
                    continue;
                }

                // Process socket events
                foreach ($socketsReady as $socket) {
                    if ($socket === $this->listeningSocket) { 
                        $acceptedSocket = $socket->accept();
                        if ($acceptedSocket === false) {
                            throw new \RuntimeException("Error attempting to accept connection: '".$accepted->getLastErrorStr()."'");
                        }
                        
                        $socketLoop->watch(new BufferedSocket($acceptedSocket));
                        $this->onConnect($acceptedSocket);
                        // Create buffer for connection
                        continue;
                    }

                    // Anything in here is a buffered socket

                    if ($socket->processReadReady() === BufferedSocket::FAILED) {
                        throw new \RuntimeException("Error reading s0ocket: '".$bufferedSocket->getLastErrorStr()."'");
                    }
                    if (!$socket->isSocketValid()) {
                        // Socket has closed
                        $socketLoop->unwatchSocket($socket);
                        continue;
                    }
                    $buffer = $bufferedSocket->getReadBuffer();
                    $newLinePos = \strpos($buffer, "\n");
                    if ($newLinPos === false) {
                        continue; // Message not ready
                    }
                    $buffer = \substr($buffer, 0, $newLinePos+1);
                    $bufferedSocket->consumeReadBuffer(\strlen($buffer));
                    $buffer = trim($buffer, "\r\n");

                    // Process message
                    try {
                        $this->preProcessMessage($socket, $buffer);
                        $response = $this->processMessage($socket, $buffer);
                        $this->preSendResponse($socket, $response);
                        if ($response) {
                            $bufferedSocket->write($response);
                        }
                        $this->postSendResponse($socket);
                    } catch (Exception\ConnectionClose $e) {
                        $this->preDisconnect($socket);
                        $this->postDisconnect($socket);
                        break;
                    } catch (Exception\Shutdown $e) {
                        $shuttingDown = true;
                        $this->preDisconnect($socket);
                        $this->postDisconnect($socket);
                        break;
                    } catch (Exception\UserMessage $e) {
                        $jsonapi = [
                            'errors' => [
                                [
                                    'status' => $e->getCode(),
                                    'title' => 'Error',
                                    'detail' => $e->getMessage(),
                                ]
                            ]
                        ];
                        $this->preSendResponse($socket, $jsonapi);
                        $jsonapi = json_encode($jsonapi);
                        $socket->write($jsonapi);
                        $this->postSendResponse($socket);
                    }
                }

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

    /**
     * To be overridden by child classes
     *
     * Child implementations should always call parent to complete initialization
     */
    protected function init()
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;
        $this->log("Daemon Initialized", LOG_INFO, [ "syslog", STDOUT ], true);
    }

    abstract protected function processMessage(string $msg) : ?string;

    public function shutdown()
    {
        $this->log("Shutting down", LOG_INFO, [ "syslog", STDOUT ], true);
        if ($this->config->getSocketDomain() === AF_UNIX && file_exists($this->config->getSocketAddress())) {
            $this->log("Cleaning up Unix Socket", LOG_INFO);
            \unlink($this->config->getSocketAddress());
        }
    }



    // Hooks

    protected function preRun()
    {
        // Override
    }

    protected function onListen()
    {
        $msg = "Listening on {$this->config->getSocketAddress()}";
        if ($p = $this->config->getSocketPort()) {
            $msg .= ":$p";
        }
        $this->log($msg, LOG_INFO);
    }

    protected function onConnect($socket)
    {
        $this->log("Connected to peer", LOG_DEBUG);
    }

    protected function preProcessMessage($socket, string $msg)
    {
        $this->log("Got a message: $msg", LOG_DEBUG);
    }

    protected function preSendResponse($socket, $msg)
    {
        if (is_array($msg)) {
            $msg = json_encode($msg);
        }
        $this->log("Got a response: $msg", LOG_DEBUG);
    }

    protected function postSendResponse($socket)
    {
        $this->log("Response sent.", LOG_DEBUG);
    }

    protected function preDisconnect($socket)
    {
        $this->log("Disconnecting from peer", LOG_DEBUG);
    }

    protected function postDisconnect()
    {
        $this->log("Disconnected. Waiting.", LOG_DEBUG);
    }

    protected function preShutdown()
    {
        $this->log("Preparing to shutdown.", LOG_DEBUG);
    }

    protected function postShutdown()
    {
        $this->log("Goodbye.", LOG_DEBUG);
    }
}

