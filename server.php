<?php
// server.php

require __DIR__ . '/vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;

class LudoServer implements MessageComponentInterface {
    protected $clients;
    protected $gameClients; // Mapping of game_code to client connections

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->gameClients = [];
        echo "WebSocket server started.\n";
    }

    public function onOpen(ConnectionInterface $conn) {
        // Store the new connection
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        // Parse incoming message
        $data = json_decode($msg, true);
        if (!$data) return;

        // Handle different message types
        switch ($data['type']) {
            case 'join_game':
                $game_code = $data['game_code'];
                if (!isset($this->gameClients[$game_code])) {
                    $this->gameClients[$game_code] = new \SplObjectStorage;
                }
                $this->gameClients[$game_code]->attach($from);
                $from->game_code = $game_code;
                echo "Connection {$from->resourceId} joined game {$game_code}\n";
                break;

            case 'update':
                $game_code = $from->game_code;
                if (isset($this->gameClients[$game_code])) {
                    foreach ($this->gameClients[$game_code] as $client) {
                        if ($from !== $client) {
                            $client->send(json_encode($data['payload']));
                        }
                    }
                }
                break;

            // Add more cases as needed
        }
    }

    public function onClose(ConnectionInterface $conn) {
        // The connection is closed, remove it
        $this->clients->detach($conn);

        // Remove from gameClients if necessary
        if (isset($conn->game_code) && isset($this->gameClients[$conn->game_code])) {
            $this->gameClients[$conn->game_code]->detach($conn);
            echo "Connection {$conn->resourceId} left game {$conn->game_code}\n";
        }

        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Create the WebSocket server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new LudoServer()
        )
    ),
    8080
);

echo "WebSocket server listening on port 8080\n";

$server->run();
?>
