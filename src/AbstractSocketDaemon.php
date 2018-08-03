<?php
namespace KS;
declare(ticks = 1);

include 'BufferedSocket.php';

abstract class AbstractSocketDaemon extends AbstractDaemon
{
    private $watchedSockets = Array();
    private $bufferedSockets = Array();
    private $listeningSocket;
    private $initialized = false;

    private const SELECT_TIMEOUT_SECS = 1;

    public function run()
    {
        $this->init();
        $this->preRun();
        $this->log("Begin listening on socket...", LOG_INFO, [ "syslog", STDOUT ], true);
        try {
            // Set up the socket
            if (($this->listeningSocket = \socket_create($this->config->getSocketDomain(), $this->config->getSocketType(), $this->config->getSocketProtocol())) === false) {
                throw new \RuntimeException("Couldn't create a listening socket: ".\socket_strerror(\socket_last_error()));
            }
            if (\socket_set_nonblock($this->listeningSocket) === false) {
                throw new \RuntimeException("Couldn't make listening socket non-blocking: ".\socket_strerror(\socket_last_error()));
            }
            if (\socket_bind($this->listeningSocket, $this->config->getSocketAddress(), $this->config->getSocketPort()) === false) {
                throw new \RuntimeException("Couldn't bind to socket at {$this->config->getSocketAddress()} ({$this->config->getSocketPort()}): " . \socket_strerror(\socket_last_error($this->sock)));
            }
            if (\socket_listen($this->listeningSocket, 5) === false) {
                throw new \RuntimeException("Failed to listen on socket: ".\socket_strerror(\socket_last_error($this->sock)));
            }

            $this->onListen();

            $this->watchSocket($this->listeningSocket);

            // Establish communication loop
            $shuttingDown = false;
            do {
                $watchedSockets = $this->watchedSockets;
                $nullWatch = NULL;
                $nullExeception = NULL;
                $socketEventCount = \socket_select($watchedSockets, $nullWatch, $nullException, SELECT_TIMEOUT_SECS);

                if ($watchedSockets === false) {
                    throw new \RuntimeException("Error processing socket_select: '".socket_strerror(socket_last_error())."'");
                }
                if ($watchedSockets === 0) {
                    continue;
                }

                // Process socket events
                foreach ($watchedSockets as $socket) {
                    if ($socket === $this->listeningSocket) { 
                        $acceptedSocket = \socket_accept($socket);
                        if ($acceptedSocket === false) {
                            throw new \RuntimeException("Error attempting to accept connection: '".\socket_strerror(\socket_last_error($accepted))."'");
                        }
                        
                        $this->watchSocket($acceptedSocket);
                        \array_push($this->bufferedSockets, new BufferedSocket($acceptedSocket));
                        $this->onConnect($acceptedSocket);
                        // Create buffer for connection
                        continue;
                    }

                    if ($this->bufferedSockets[$socket]->processReadReady() === BufferedSocket::FAILED) {
                        throw new \RuntimeException("Error reading s0ocket: '".$this->bufferedSockets[$socket]->getLastErrorStr()."'");
                    }
                    if (!$this->bufferedSockets[$socket]->isSocketValid()) {
                        // Socket has closed
                        $this->unwatchSocket($socket);
                        unset($this->bufferedSockets[$socket]);
                        continue;
                    }
                    $buffer = $this->bufferedSockets[$socket]->getReadBuffer();
                    $newLinePos = \strpos($buffer, "\n");
                    if ($newLinPos === false) {
                        continue; // Message not ready
                    }
                    $buffer = \substr($buffer, 0, $newLinePos+1);
                    $this->bufferedSockets[$socket]->consumeReadBuffer(\strlen($buffer));
                    $buffer = trim($buffer, "\r\n");

                    // Process message
                    try {
                        $this->preProcessMessage($socket, $buffer);
                        $response = $this->processMessage($socket, $buffer);
                        $this->preSendResponse($socket, $response);
                        if ($response) {
                            $this->bufferedSockets[$socket]->write($response);
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
                        $this->write($jsonapi);
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
        if ($this->initialized)
            return;
        $this->initialized = true;
        $this->log("Daemon Initialized", LOG_INFO, [ "syslog", STDOUT ], true);
    }

    abstract protected function processMessage(string $msg) : ?string;

    protected function write(string $msg) : void
    {
        \socket_write($this->cnx, $msg, strlen($msg));
    }

    public function shutdown()
    {
        $this->log("Shutting down", LOG_INFO, [ "syslog", STDOUT ], true);
        if ($this->cnx) {
            \socket_close($this->cnx);
        }
        if ($this->sock) {
            \socket_close($this->sock);
        }
        if ($this->config->getSocketDomain() === AF_UNIX && file_exists($this->config->getSocketAddress())) {
            $this->log("Cleaning up Unix Socket", LOG_INFO);
            \unlink($this->config->getSocketAddress());
        }
    }

    private function watchSocket($socket)
    {
        \array_push($this->watchedSockets, $socket);
    }

    private function unwatchSocket($socket)
    {
        if (($key = array_search($socket, $this->watchedSockets)) !== false) {
            unset($this->watchedSockets[$key]);
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

