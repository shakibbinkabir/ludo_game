// js/scripts.js

document.addEventListener("DOMContentLoaded", () => {
    // Initialize the game board
    initGameBoard();

    // Initialize WebSocket connection if game is ongoing
    if (status === 'ongoing') {
        initializeWebSocket();
    }
});

// Global variables
let gameState = {
    players: [],
    tokens: [],
    current_turn: '',
    current_turn_username: '',
    status: 'waiting'
};

const tokenColors = {
    red: '#FF0000',
    blue: '#0000FF',
    green: '#008000',
    yellow: '#FFFF00',
};

// Initialize the canvas and context
const canvas = document.getElementById('game-board');
const ctx = canvas.getContext('2d');

// Define mapping from positions to canvas coordinates
const positionCoordinates = {
    // Example positions; these should map to actual Ludo board positions
    // For simplicity, only a few positions are defined. You should expand this mapping.
    1: { x: 50, y: 50 },
    2: { x: 90, y: 50 },
    3: { x: 130, y: 50 },
    4: { x: 170, y: 50 },
    5: { x: 210, y: 50 },
    6: { x: 250, y: 50 },
    7: { x: 290, y: 50 },
    8: { x: 330, y: 50 },
    9: { x: 370, y: 50 },
    10: { x: 410, y: 50 },
    11: { x: 450, y: 50 },
    12: { x: 490, y: 50 },
    13: { x: 530, y: 50 },
    14: { x: 550, y: 90 },
    15: { x: 550, y: 130 },
    // Continue mapping all positions up to 57
    57: { x: 300, y: 300 }, // Home position
    // Add more positions as needed
};

// Current game status
let gameStatus = 'waiting';

// Function to initialize the game board
function initGameBoard() {
    // Draw the board background
    // Assuming the background image is set via CSS
    // Draw existing tokens
    drawTokens();
}

// Function to draw tokens
function drawTokens() {
    gameState.tokens.forEach(token => {
        if (token.position > 0) {
            const coord = positionCoordinates[token.position];
            if (coord) {
                drawToken(coord.x, coord.y, token.color);
            }
        }
    });
}

// Function to draw a single token
function drawToken(x, y, color) {
    ctx.beginPath();
    ctx.arc(x, y, 15, 0, 2 * Math.PI, false);
    ctx.fillStyle = tokenColors[color];
    ctx.fill();
    ctx.lineWidth = 2;
    ctx.strokeStyle = '#000000';
    ctx.stroke();
    ctx.closePath();
}

// Function to handle dice roll
function handleRollDice() {
    // Check if it's the user's turn
    if (gameState.current_turn_username !== userName) { // Updated line
        alert("It's not your turn!");
        return;
    }

    // Simulate dice roll
    const dice = Math.floor(Math.random() * 6) + 1;
    document.getElementById("dice-result").textContent = `You rolled a ${dice}!`;

    // Send move to server via AJAX
    fetch('game_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'roll_dice', game_code: gameCode, color: userColor, dice: dice })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.extra_turn) {
                alert("You rolled a 6! You get another turn.");
            }
            // Game state will be updated via WebSocket
        } else {
            alert(data.error);
        }
    })
    .catch(error => console.error('Error:', error));
}

// Event listener for dice roll button
document.getElementById("roll-dice").addEventListener("click", handleRollDice);

// Function to initialize WebSocket connection
function initializeWebSocket() {
    const socket = new WebSocket('ws://localhost:8080');

    socket.addEventListener('open', function (event) {
        // Join the specific game room
        socket.send(JSON.stringify({
            type: 'join_game',
            game_code: gameCode
        }));
    });

    socket.addEventListener('message', function (event) {
        const data = JSON.parse(event.data);
        if (data.type === 'update') {
            // Handle game updates
            if (data.action === 'dice_roll') {
                // Fetch updated game state
                fetchGameState();
            }
            // Handle other actions as needed
        }
    });

    socket.addEventListener('close', function (event) {
        console.log('WebSocket connection closed.');
    });

    socket.addEventListener('error', function (event) {
        console.error('WebSocket error:', event);
    });
}

// Function to fetch the latest game state
function fetchGameState() {
    fetch('get_game_state.php?game_code=' + gameCode)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(data.error);
                return;
            }
            // Update game state
            gameState = data;
            // Update the game board
            updateGameBoard();
        })
        .catch(error => console.error('Error fetching game state:', error));
}

// Function to update the game board based on game state
function updateGameBoard() {
    // Clear the canvas
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // Redraw tokens
    drawTokens();

    // Update current turn display
    const turnDisplay = document.getElementById("current-turn");
    const currentPlayer = gameState.current_turn_username;
    turnDisplay.textContent = `Current Turn: ${currentPlayer}`;
}
