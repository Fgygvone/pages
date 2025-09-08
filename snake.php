<?php
header('Content-Type: text/html; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Змейка - Telegram Game</title>
    <script src="https://telegram.org/js/telegram-web-app.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .game-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 100%;
        }
        
        canvas {
            border: 3px solid #dee2e6;
            border-radius: 15px;
            background: #f8f9fa;
            margin: 20px 0;
        }
        
        .score {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin: 10px 0;
        }
        
        button {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            padding: 15px 25px;
            border-radius: 15px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            margin: 5px;
            transition: transform 0.2s;
        }
        
        button:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body>
    <div class="game-container">
        <h1>🐍 Telegram Змейка</h1>
        <div class="score">Очки: <span id="score">0</span></div>
        <canvas id="gameCanvas" width="400" height="400"></canvas>
        <div>
            <button onclick="initGame()">🎮 Начать игру</button>
            <button onclick="closeWebApp()">🔙 Вернуться в бота</button>
        </div>
    </div>

    <script>
        // Инициализация Telegram WebApp
        let tg = window.Telegram?.WebApp;
        
        if (tg) {
            tg.expand();
            tg.enableClosingConfirmation();
            
            // Проверяем, инициализирован ли WebApp
            console.log('Telegram WebApp initialized:', tg.initData);
        } else {
            console.log('Telegram WebApp not available - running in browser mode');
        }
        
        const canvas = document.getElementById('gameCanvas');
        const ctx = canvas.getContext('2d');
        const scoreElement = document.getElementById('score');
        
        const gridSize = 20;
        const gridWidth = canvas.width / gridSize;
        const gridHeight = canvas.height / gridSize;
        
        let snake = [];
        let food = {};
        let direction = 'right';
        let nextDirection = 'right';
        let gameInterval;
        let score = 0;
        let gameRunning = false;
        
        function initGame() {
            snake = [
                {x: 5, y: 10},
                {x: 4, y: 10},
                {x: 3, y: 10}
            ];
            
            generateFood();
            score = 0;
            scoreElement.textContent = score;
            direction = 'right';
            nextDirection = 'right';
            gameRunning = true;
            
            if (gameInterval) {
                clearInterval(gameInterval);
            }
            
            gameInterval = setInterval(gameLoop, 150);
            draw();
        }
        
        function generateFood() {
            food = {
                x: Math.floor(Math.random() * gridWidth),
                y: Math.floor(Math.random() * gridHeight)
            };
            
            for (let segment of snake) {
                if (segment.x === food.x && segment.y === food.y) {
                    return generateFood();
                }
            }
        }
        
        function gameLoop() {
            moveSnake();
            if (checkCollision()) {
                endGame();
                return;
            }
            checkFood();
            draw();
        }
        
        function moveSnake() {
            direction = nextDirection;
            const head = {...snake[0]};
            
            switch (direction) {
                case 'up': head.y--; break;
                case 'down': head.y++; break;
                case 'left': head.x--; break;
                case 'right': head.x++; break;
            }
            
            snake.unshift(head);
            
            if (head.x === food.x && head.y === food.y) {
                score += 10;
                scoreElement.textContent = score;
                generateFood();
            } else {
                snake.pop();
            }
        }
        
        function checkCollision() {
            const head = snake[0];
            
            if (head.x < 0 || head.x >= gridWidth || head.y < 0 || head.y >= gridHeight) {
                return true;
            }
            
            for (let i = 1; i < snake.length; i++) {
                if (head.x === snake[i].x && head.y === snake[i].y) {
                    return true;
                }
            }
            
            return false;
        }
        
        function checkFood() {
            const head = snake[0];
            if (head.x === food.x && head.y === food.y) {
                score += 10;
                scoreElement.textContent = score;
                generateFood();
                snake.push({...snake[snake.length - 1]});
            }
        }
        
        function draw() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Еда
            ctx.fillStyle = 'red';
            ctx.fillRect(food.x * gridSize, food.y * gridSize, gridSize, gridSize);
            
            // Змейка
            ctx.fillStyle = 'green';
            snake.forEach((segment, index) => {
                if (index === 0) {
                    ctx.fillStyle = '#2ecc71'; // Голова
                } else {
                    ctx.fillStyle = '#27ae60'; // Тело
                }
                ctx.fillRect(segment.x * gridSize, segment.y * gridSize, gridSize, gridSize);
            });
        }
        
        function endGame() {
            clearInterval(gameInterval);
            gameRunning = false;
            
            // Отправляем результат в Telegram
            if (tg && tg.sendData) {
                try {
                    const gameData = {
                        score: score,
                        game: 'snake',
                        timestamp: new Date().toISOString(),
                        user: tg.initDataUnsafe?.user?.id || 'unknown'
                    };
                    tg.sendData(JSON.stringify(gameData));
                } catch (error) {
                    console.log('Error sending data to Telegram:', error);
                }
            }
            
            alert(`🎮 Игра окончена!\nВаш счет: ${score} очков`);
        }
        
        function closeWebApp() {
            if (tg && tg.close) {
                tg.close();
            } else {
                alert('Вернитесь в Telegram бота');
            }
        }
        
        // Управление с клавиатуры
        document.addEventListener('keydown', (e) => {
            if (!gameRunning) return;
            
            switch (e.key) {
                case 'ArrowUp':
                    if (direction !== 'down') nextDirection = 'up';
                    break;
                case 'ArrowDown':
                    if (direction !== 'up') nextDirection = 'down';
                    break;
                case 'ArrowLeft':
                    if (direction !== 'right') nextDirection = 'left';
                    break;
                case 'ArrowRight':
                    if (direction !== 'left') nextDirection = 'right';
                    break;
            }
        });
        
        // Кнопки управления для мобильных устройств
        function setupMobileControls() {
            const controls = document.createElement('div');
            controls.style.marginTop = '20px';
            controls.innerHTML = `
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                    <div></div>
                    <button onclick="changeDirection('up')" style="font-size: 20px;">↑</button>
                    <div></div>
                    <button onclick="changeDirection('left')" style="font-size: 20px;">←</button>
                    <div></div>
                    <button onclick="changeDirection('right')" style="font-size: 20px;">→</button>
                    <div></div>
                    <button onclick="changeDirection('down')" style="font-size: 20px;">↓</button>
                    <div></div>
                </div>
            `;
            document.querySelector('.game-container').appendChild(controls);
        }
        
        function changeDirection(dir) {
            if (!gameRunning) return;
            const opposite = {up: 'down', down: 'up', left: 'right', right: 'left'};
            if (dir !== opposite[direction]) nextDirection = dir;
        }
        
        // Инициализация при загрузке
        window.addEventListener('load', () => {
            setupMobileControls();
            draw(); // Начальная отрисовка
        });
    </script>
</body>
</html>