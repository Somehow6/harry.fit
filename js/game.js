// 游戏配置
const config = {
    blockSize: 30,
    cols: 10,
    rows: 20,
    initialSpeed: 1000,
    speedIncrease: 0.95,
    levelUpScore: 1000,
    maxLevel: 10,
    minSpeed: 100,
    debug: true
};

// 方块形状定义（确保形状是正方形矩阵）
const shapes = [
    [[1,1,1,1],
     [0,0,0,0],
     [0,0,0,0],
     [0,0,0,0]], // I

    [[1,1,1],
     [0,1,0],
     [0,0,0]], // T

    [[1,1,1],
     [1,0,0],
     [0,0,0]], // L

    [[1,1,1],
     [0,0,1],
     [0,0,0]], // J

    [[1,1],
     [1,1]], // O

    [[1,1,0],
     [0,1,1],
     [0,0,0]], // Z

    [[0,1,1],
     [1,1,0],
     [0,0,0]]  // S
];

// 方块颜色
const colors = [
    '#FF69B4', '#FFB6C1', '#FFC0CB', 
    '#FF1493', '#DB7093', '#C71585', '#FF69B4'
];

let currentUser = null;
let game = null;

async function openFileManager() {
    try {
        const response = await fetch('/api/check_login.php');
        const data = await response.json();
        
        if (data.success && data.user) {
            window.location.href = 'files.html';
        } else {
            alert('请先登录');
            showLoginModal();
        }
    } catch (error) {
        console.error('Error checking login status:', error);
        alert('发生错误，请重试');
    }
}
// 初始化游戏
function initGame() {
    console.log('Initializing game...');
    
    game = {
        canvas: document.getElementById('game-area'),
        ctx: null,
        preview: document.getElementById('preview'),
        previewCtx: null,
        currentPiece: null,
        nextPiece: null,
        board: Array(config.rows).fill().map(() => Array(config.cols).fill(0)),
        score: 0,
        level: 1,
        gameInterval: null,
        currentSpeed: config.initialSpeed,
        isAnimating: false,
        isGameOver: false
    };

    // 初始化画布上下文
    game.ctx = game.canvas.getContext('2d');
    game.previewCtx = game.preview.getContext('2d');

    // 设置画布尺寸
    game.canvas.width = config.blockSize * config.cols;
    game.canvas.height = config.blockSize * config.rows;
    game.preview.width = config.blockSize * 4;
    game.preview.height = config.blockSize * 4;

    // 清空画布
    clearCanvas();

    // 如果没有登录，显示登录框
    if (!currentUser) {
        console.log('No current user, showing login modal...');
        showLoginModal();
    } else {
        console.log('User already logged in, starting game...');
        startGame();
    }
}

// 清空画布
function clearCanvas() {
    game.ctx.fillStyle = '#fff';
    game.ctx.fillRect(0, 0, game.canvas.width, game.canvas.height);
    game.previewCtx.fillStyle = '#fff';
    game.previewCtx.fillRect(0, 0, game.preview.width, game.preview.height);
}

// 生成新方块
function generatePiece() {
    const shapeIndex = Math.floor(Math.random() * shapes.length);
    const shape = JSON.parse(JSON.stringify(shapes[shapeIndex])); // 深拷贝形状
    return {
        shape: shape,
        color: colors[shapeIndex],
        x: Math.floor(config.cols/2) - Math.floor(shape[0].length/2),
        y: 0
    };
}

// 开始游戏
function startGame() {
    hideRestartButton();
    resetGame();
    game.currentPiece = generatePiece();
    game.nextPiece = generatePiece();
    game.gameInterval = setInterval(gameLoop, game.currentSpeed);
    document.addEventListener('keydown', handleInput);
}

// 重置游戏
function resetGame() {
    game.board = Array(config.rows).fill().map(() => Array(config.cols).fill(0));
    game.score = 0;
    game.level = 1;
    game.currentSpeed = config.initialSpeed;
    game.isGameOver = false;
    if (game.gameInterval) {
        clearInterval(game.gameInterval);
        game.gameInterval = null;
    }
    updateStats();
    clearCanvas();
}

// 重新开始游戏
function restartGame() {
    if (game.gameInterval) {
        clearInterval(game.gameInterval);
    }
    hideRestartButton();
    startGame();
}

// 显示重新开始按钮
function showRestartButton() {
    const button = document.getElementById('restart-button');
    if (button) {
        button.style.display = 'block';
    }
}

// 隐藏重新开始按钮
function hideRestartButton() {
    const button = document.getElementById('restart-button');
    if (button) {
        button.style.display = 'none';
    }
}

// 游戏主循环
function gameLoop() {
    if (game.isAnimating || game.isGameOver) return;

    if (!movePiece(0, 1)) {
        placePiece();
        const clearedLines = checkLines();
        if (clearedLines > 0) {
            updateScore(clearedLines);
            animateLineClear();
        }
        
        if (!spawnNewPiece()) {
            gameOver();
            return;
        }
    }
    
    drawGame();
}

// 绘制游戏
function drawGame() {
    clearCanvas();
    
    // 绘制已放置的方块
    for (let y = 0; y < config.rows; y++) {
        for (let x = 0; x < config.cols; x++) {
            if (game.board[y][x]) {
                drawBlock(x * config.blockSize, y * config.blockSize, game.board[y][x]);
            }
        }
    }
    
    // 绘制当前方块
    if (game.currentPiece) {
        drawPiece(game.currentPiece);
    }
    
    // 绘制预览
    drawPreview();
}

// 绘制方块
function drawBlock(x, y, color) {
    game.ctx.fillStyle = color;
    game.ctx.fillRect(x, y, config.blockSize - 1, config.blockSize - 1);
    
    // 添加高光效果
    game.ctx.fillStyle = 'rgba(255,255,255,0.1)';
    game.ctx.fillRect(x, y, config.blockSize - 1, config.blockSize/2);
}

// 绘制当前方块
function drawPiece(piece) {
    for (let y = 0; y < piece.shape.length; y++) {
        for (let x = 0; x < piece.shape[y].length; x++) {
            if (piece.shape[y][x]) {
                drawBlock(
                    (piece.x + x) * config.blockSize,
                    (piece.y + y) * config.blockSize,
                    piece.color
                );
            }
        }
    }
}

// 绘制预览
function drawPreview() {
    game.previewCtx.fillStyle = '#fff';
    game.previewCtx.fillRect(0, 0, game.preview.width, game.preview.height);
    
    if (!game.nextPiece) return;
    
    const offsetX = (game.preview.width - game.nextPiece.shape[0].length * config.blockSize) / 2;
    const offsetY = (game.preview.height - game.nextPiece.shape.length * config.blockSize) / 2;
    
    for (let y = 0; y < game.nextPiece.shape.length; y++) {
        for (let x = 0; x < game.nextPiece.shape[y].length; x++) {
            if (game.nextPiece.shape[y][x]) {
                game.previewCtx.fillStyle = game.nextPiece.color;
                game.previewCtx.fillRect(
                    offsetX + x * config.blockSize,
                    offsetY + y * config.blockSize,
                    config.blockSize - 1,
                    config.blockSize - 1
                );
            }
        }
    }
}

// 移动方块
function movePiece(dx, dy) {
    const newX = game.currentPiece.x + dx;
    const newY = game.currentPiece.y + dy;
    
    if (canMove(newX, newY, game.currentPiece.shape)) {
        game.currentPiece.x = newX;
        game.currentPiece.y = newY;
        drawGame();
        return true;
    }
    return false;
}

// 检查是否可以移动
function canMove(x, y, shape) {
    for (let row = 0; row < shape.length; row++) {
        for (let col = 0; col < shape[row].length; col++) {
            if (shape[row][col]) {
                const newX = x + col;
                const newY = y + row;
                
                if (newX < 0 || newX >= config.cols || 
                    newY < 0 || newY >= config.rows ||
                    game.board[newY] && game.board[newY][newX]) {
                    return false;
                }
            }
        }
    }
    return true;
}

// 放置方块
function placePiece() {
    for (let y = 0; y < game.currentPiece.shape.length; y++) {
        for (let x = 0; x < game.currentPiece.shape[y].length; x++) {
            if (game.currentPiece.shape[y][x]) {
                const boardY = game.currentPiece.y + y;
                const boardX = game.currentPiece.x + x;
                if (boardY >= 0 && boardY < config.rows && 
                    boardX >= 0 && boardX < config.cols) {
                    game.board[boardY][boardX] = game.currentPiece.color;
                }
            }
        }
    }
}

// 生成新方块
function spawnNewPiece() {
    game.currentPiece = game.nextPiece;
    game.nextPiece = generatePiece();
    
    if (!canMove(game.currentPiece.x, game.currentPiece.y, game.currentPiece.shape)) {
        return false;
    }
    
    drawGame();
    return true;
}

// 检查并消除完整行
function checkLines() {
    let linesCleared = 0;
    
    for (let y = config.rows - 1; y >= 0; y--) {
        if (game.board[y].every(cell => cell !== 0)) {
            game.board.splice(y, 1);
            game.board.unshift(Array(config.cols).fill(0));
            linesCleared++;
            y++;
        }
    }
    
    return linesCleared;
}

// 更新分数
function updateScore(clearedLines) {
    const points = [0, 100, 300, 500, 800];
    game.score += points[clearedLines] * game.level;
    
    const newLevel = Math.floor(game.score / config.levelUpScore) + 1;
    if (newLevel > game.level && newLevel <= config.maxLevel) {
        levelUp(newLevel);
    }
    
    updateStats();
}

// 更新状态显示
function updateStats() {
    document.getElementById('score').textContent = game.score;
    document.getElementById('level').textContent = game.level;
}

// 升级
function levelUp(newLevel) {
    game.level = newLevel;
    game.currentSpeed = Math.max(
        config.minSpeed,
        config.initialSpeed * Math.pow(config.speedIncrease, game.level - 1)
    );
    
    clearInterval(game.gameInterval);
    game.gameInterval = setInterval(gameLoop, game.currentSpeed);
    
    showLevelUpAnimation();
}

// 处理用户输入
function handleInput(event) {
    if (game.isGameOver) return;
    
    switch(event.keyCode) {
        case 37: // 左箭头
            movePiece(-1, 0);
            break;
        case 39: // 右箭头
            movePiece(1, 0);
            break;
        case 40: // 下箭头
            movePiece(0, 1);
            break;
        case 38: // 上箭头
            rotatePiece();
            break;
        case 32: // 空格
            hardDrop();
            break;
    }
}

// 旋转方块
function rotatePiece() {
    console.log('Attempting rotation...');
    const rotated = rotateMatrix(game.currentPiece.shape);
    console.log('Rotated shape:', rotated);
    
    // 尝试不同的位置来适应旋转后的形状
    const kicks = [
        [0, 0], // 原位置
        [-1, 0], // 左移
        [1, 0],  // 右移
        [0, -1], // 上移
        [0, 1]   // 下移
    ];

    for (let [kickX, kickY] of kicks) {
        const newX = game.currentPiece.x + kickX;
        const newY = game.currentPiece.y + kickY;
        
        if (canMove(newX, newY, rotated)) {
            game.currentPiece.x = newX;
            game.currentPiece.y = newY;
            game.currentPiece.shape = rotated;
            drawGame();
            return true;
        }
    }
    return false;
}

// 旋转矩阵
function rotateMatrix(matrix) {
    const N = matrix.length;
    const rotated = Array(N).fill().map(() => Array(N).fill(0));
    
    for (let i = 0; i < N; i++) {
        for (let j = 0; j < N; j++) {
            rotated[j][N - 1 - i] = matrix[i][j];
        }
    }
    
    return rotated;
}

// 快速下降
function hardDrop() {
    while (movePiece(0, 1)) {}
}

// 游戏结束
function gameOver() {
    game.isGameOver = true;
    clearInterval(game.gameInterval);
    
    // 添加半透明遮罩
    game.ctx.fillStyle = 'rgba(0, 0, 0, 0.5)';
    game.ctx.fillRect(0, 0, game.canvas.width, game.canvas.height);
    
    // 显示游戏结束文字
    game.ctx.fillStyle = '#fff';
    game.ctx.font = '30px Arial';
    game.ctx.textAlign = 'center';
    game.ctx.fillText('游戏结束!', game.canvas.width/2, game.canvas.height/2 - 30);
    game.ctx.fillText(`得分: ${game.score}`, game.canvas.width/2, game.canvas.height/2 + 20);
    
    // 显示重新开始按钮
    showRestartButton();
    
    if (currentUser) {
        saveScore();
    }
}

// API通信函数
async function apiRequest(endpoint, data) {
    try {
        const response = await fetch(`/api/${endpoint}.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        });
        return await response.json();
    } catch (error) {
        console.error('API请求错误:', error);
        return { error: '网络错误' };
    }
}

// 保存分数
async function saveScore() {
    if (!currentUser) return;
    
    await apiRequest('save_score', {
        user_id: currentUser.id,
        score: game.score,
        level: game.level
    });
    
    updateLeaderboard();
}

// 更新排行榜
async function updateLeaderboard() {
    const data = await apiRequest('get_leaderboard', {});
    if (!data.error && data.leaderboard) {
        displayLeaderboard(data.leaderboard);
    }
}

// 显示排行榜
function displayLeaderboard(leaderboard) {
    const list = document.getElementById('leaderboard-list');
    list.innerHTML = '';
    
    leaderboard.forEach((entry, index) => {
        const item = document.createElement('div');
        item.className = 'score-item';
        item.textContent = `${index + 1}. ${entry.username}: ${entry.score}`;
        list.appendChild(item);
    });
}

// 显示登录框
function showLoginModal() {
    console.log('Displaying login modal...');
    const modal = document.getElementById('login-modal');
    if (modal) {
        modal.style.display = 'block';
    } else {
        console.error('Login modal element not found!');
    }
}

// 隐藏登录框
function hideLoginModal() {
    const modal = document.getElementById('login-modal');
    if (modal) {
        modal.style.display = 'none';
    }
}

// 登录函数
async function login() {
    console.log('Login attempt...');
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
        alert('请输入用户名和密码');
        return;
    }
    
    try {
        const data = await apiRequest('login', { username, password });
        
        if (data.success) {
            currentUser = {
                id: data.user_id,
                username: username
            };
            
            console.log('Login successful:', currentUser);
            hideLoginModal();
            startGame();
            updateLeaderboard();
        } else {
            alert(data.error || '登录失败');
        }
    } catch (error) {
        console.error('Login error:', error);
        alert('登录出错，请重试');
    }
}

// 注册函数
async function register() {
    console.log('Register attempt...');
    const username = document.getElementById('username').value;
    const password = document.getElementById('password').value;
    
    if (!username || !password) {
        alert('请输入用户名和密码');
        return;
    }
    
    try {
        const data = await apiRequest('register', { username, password });
        
        if (data.success) {
            alert('注册成功！请登录');
        } else {
            alert(data.error || '注册失败');
        }
    } catch (error) {
        console.error('Register error:', error);
        alert('注册出错，请重试');
    }
}

// 显示升级动画
function showLevelUpAnimation() {
    const levelText = document.createElement('div');
    levelText.className = 'level-up-animation';
    levelText.textContent = `Level ${game.level}!`;
    document.body.appendChild(levelText);
    
    setTimeout(() => {
        levelText.remove();
    }, 1000);
}

// 初始化
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM Content Loaded');
    initGame();
});