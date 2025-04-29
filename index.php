<?php
// セッションとタイムゾーンの設定
session_start();
date_default_timezone_set('Asia/Tokyo');

// 定数定義
define('APP_NAME', 'ゲーム');
define('STATUS_RUNNING', 'ゲーム中');
define('STATUS_STOPPED', 'お休み中');

// 状態管理用のJSONファイル
$STATE_FILE = 'timer_state.json';
$MAX_TIME_SECONDS = 3600; // 1時間（秒）

// 初期状態の設定
if (!file_exists($STATE_FILE)) {
    $initial_state = [
        'is_running' => false,
        'total_time' => 0,
        'last_start' => null,
        'today_sessions' => [],
        'last_update' => date('Y-m-d H:i:s')
    ];
    file_put_contents($STATE_FILE, json_encode($initial_state));
}

// アクション処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $state = json_decode(file_get_contents($STATE_FILE), true);
    $current_time = time();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'start':
                $state['is_running'] = true;
                $state['last_start'] = $current_time;
                break;
            case 'stop':
                if ($state['is_running']) {
                    $elapsed = $current_time - $state['last_start'];
                    $state['total_time'] += $elapsed;
                    $state['today_sessions'][] = [
                        'start' => date('H:i:s', $state['last_start']),
                        'end' => date('H:i:s', $current_time),
                        'duration' => $elapsed
                    ];
                }
                $state['is_running'] = false;
                break;
        }
        $state['last_update'] = date('Y-m-d H:i:s');
        file_put_contents($STATE_FILE, json_encode($state));
    }
    
    // AJAXリクエストの場合はJSON形式で状態を返す
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($state);
        exit;
    }
}

// 現在の状態を取得
$state = json_decode(file_get_contents($STATE_FILE), true);

// 日付が変わっていた場合はリセット
if (date('Y-m-d', strtotime($state['last_update'])) !== date('Y-m-d')) {
    // 前日のデータを別ファイルに保存
    $yesterday = date('Ymd', strtotime('-1 day'));
    $backup_file = sprintf('%s.json', $yesterday);
    file_put_contents($backup_file, json_encode($state));

    // 状態をリセット
    $state = [
        'is_running' => false,
        'total_time' => 0,
        'last_start' => null,
        'today_sessions' => [],
        'last_update' => date('Y-m-d H:i:s')
    ];
    file_put_contents($STATE_FILE, json_encode($state));
}

// 本日の合計時間を計算する関数を追加
function calculateTotalMinutes($sessions) {
    $total = 0;
    foreach ($sessions as $session) {
        $total += $session['duration'];
    }
    return $total;
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?>タイマー</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .time-warning {
            background-color: #ffa726 !important;
            transition: background-color 0.5s;
        }
        .time-danger {
            background-color: #ef5350 !important;
            transition: background-color 0.5s;
        }
        #status {
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        #status.running {
            background-color: #e8f5e9;
        }
        #status.stopped {
            background-color: #ffebee;
        }
        body.overtime {
            background-color: #ff9800;
            transition: background-color 0.5s;
        }
        body.danger {
            background-color: #f44336;
            transition: background-color 0.5s;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-center mb-0"><?php echo APP_NAME; ?>タイマー</h2><a href="study.php">&gt;切り替え</a>
                    </div>
                    <div class="card-body" id="timerContainer">
                        <div class="text-center mb-4">
                            <h3 id="status" class="mb-3 <?php echo $state['is_running'] ? 'running' : 'stopped'; ?>">
                                <?php echo $state['is_running'] ? STATUS_RUNNING : STATUS_STOPPED; ?>
                            </h3>
                            <h4 id="timer" class="mb-4">
                                <?php 
                                $total = $state['total_time'];
                                if ($state['is_running']) {
                                    $total += time() - $state['last_start'];
                                }
                                $remaining = max(0, $MAX_TIME_SECONDS - $total);
                                echo sprintf('残り %02d:%02d:%02d', 
                                    floor($remaining / 3600),
                                    floor(($remaining % 3600) / 60),
                                    $remaining % 60
                                );
                                ?>
                            </h4>
                            <div class="d-grid gap-2 d-md-block">
                                <button id="startBtn" class="btn btn-primary btn-lg mx-2" <?php echo $state['is_running'] ? 'disabled' : ''; ?>>
                                    スタート
                                </button>
                                <button id="stopBtn" class="btn btn-danger btn-lg mx-2" <?php echo !$state['is_running'] ? 'disabled' : ''; ?>>
                                    ストップ
                                </button>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <div class="progress" style="height: 25px;">
                                <div id="timeProgress" class="progress-bar" role="progressbar" 
                                     style="width: <?php 
                                        $progress = ($state['total_time'] / $MAX_TIME_SECONDS) * 100;
                                        echo min(100, $progress);
                                     ?>%;"
                                     aria-valuenow="<?php echo min(100, $progress); ?>" 
                                     aria-valuemin="0" 
                                     aria-valuemax="100">
                                    <?php echo round($progress); ?>%
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5>本日の記録</h5>
                                <h5>
                                    合計時間: <?php
                                    $total_minutes = calculateTotalMinutes($state['today_sessions']);
                                    echo sprintf('%02d:%02d', 
                                        floor($total_minutes / 60),
                                        $total_minutes % 60
                                    );
                                    ?>
                                </h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped" id="sessionLog">
                                    <thead>
                                        <tr>
                                            <th>開始時刻</th>
                                            <th>終了時刻</th>
                                            <th>経過時間</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($state['today_sessions'] as $session): ?>
                                        <tr>
                                            <td><?php echo $session['start']; ?></td>
                                            <td><?php echo $session['end']; ?></td>
                                            <td><?php echo sprintf('%02d:%02d',
                                                floor($session['duration'] / 60),
                                                $session['duration'] % 60); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if ($state['is_running']): ?>
                                        <tr>
                                            <td><?php echo date('H:i:s', $state['last_start']); ?></td>
                                            <td><?php echo STATUS_RUNNING; ?></td>
                                            <td>-</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let isRunning = <?php echo $state['is_running'] ? 'true' : 'false'; ?>;
        let startTime = <?php echo $state['last_start'] ?: 'null'; ?>;
        let totalTime = <?php echo $state['total_time']; ?>;
        
        // PHP定数をJavaScript変数として定義
        const STATUS_RUNNING = '<?php echo STATUS_RUNNING; ?>';
        const STATUS_STOPPED = '<?php echo STATUS_STOPPED; ?>';

        function updateTimer() {
            const now = Math.floor(Date.now() / 1000);
            const total = isRunning ? totalTime + (now - startTime) : totalTime;
            const remaining = <?php echo $MAX_TIME_SECONDS; ?> - total;
            
            const absRemaining = Math.abs(remaining);
            const hours = Math.floor(absRemaining / 3600);
            const minutes = Math.floor((absRemaining % 3600) / 60);
            const seconds = absRemaining % 60;
            
            const sign = remaining < 0 ? '-' : '';
            document.getElementById('timer').textContent = 
                `残り ${sign}${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            
            // プログレスバーの更新
            const progress = Math.min(100, (total / <?php echo $MAX_TIME_SECONDS; ?>) * 100);
            const progressBar = document.getElementById('timeProgress');
            progressBar.style.width = `${progress}%`;
            progressBar.setAttribute('aria-valuenow', progress);
            progressBar.textContent = `${Math.round(progress)}%`;
            
            // プログレスバーの色変更
            if (total > <?php echo $MAX_TIME_SECONDS * 2; ?>) {
                progressBar.className = 'progress-bar bg-danger';
            } else if (total > <?php echo $MAX_TIME_SECONDS; ?>) {
                progressBar.className = 'progress-bar bg-warning';
            } else {
                progressBar.className = 'progress-bar bg-primary';
            }
            
            // 警告表示の条件分岐
            if (total > <?php echo $MAX_TIME_SECONDS * 2; ?>) {
                document.getElementById('timerContainer').classList.add('time-danger');
                document.body.classList.remove('overtime');
                document.body.classList.add('danger');
            } else if (total > <?php echo $MAX_TIME_SECONDS; ?>) {
                document.getElementById('timerContainer').classList.add('time-warning');
                document.body.classList.add('overtime');
                document.body.classList.remove('danger');
            }
        }

        function updateState() {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax=1'
            })
            .then(response => response.json())
            .then(state => {
                isRunning = state.is_running;
                startTime = state.last_start;
                totalTime = state.total_time;
                
                const statusElement = document.getElementById('status');
                statusElement.textContent = isRunning ? STATUS_RUNNING : STATUS_STOPPED;
                statusElement.className = `mb-3 ${isRunning ? 'running' : 'stopped'}`;
                
                document.getElementById('startBtn').disabled = isRunning;
                document.getElementById('stopBtn').disabled = !isRunning;
                
                // 計測中の行を更新
                const tbody = document.getElementById('sessionLog').getElementsByTagName('tbody')[0];
                if (isRunning && !previousIsRunning) {
                    const newRow = document.createElement('tr');
                    newRow.innerHTML = `
                        <td>${new Date(startTime * 1000).toTimeString().split(' ')[0]}</td>
                        <td>${STATUS_RUNNING}</td>
                        <td>-</td>
                    `;
                    tbody.appendChild(newRow);
                }
                
                // 停止時のみリロード
                if (!isRunning && previousIsRunning) {
                    location.reload();
                }
                previousIsRunning = isRunning;
            });
        }

        // 状態管理用の変数を追加
        let previousIsRunning = isRunning;

        // ボタンのイベントリスナー
        document.getElementById('startBtn').addEventListener('click', function() {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=start'
            }).then(() => updateState());
        });

        document.getElementById('stopBtn').addEventListener('click', function() {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=stop'
            }).then(() => updateState());
        });

        // タイマーの更新と状態の定期チェック
        setInterval(updateTimer, 1000);
        setInterval(updateState, 5000);

        // ページ読み込み時に初期状態を設定
        document.addEventListener('DOMContentLoaded', function() {
            updateTimer();
        });
    </script>
</body>
</html>
