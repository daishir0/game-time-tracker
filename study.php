<?php
// セッションとタイムゾーンの設定
session_start();
date_default_timezone_set('Asia/Tokyo');

// 定数定義
define('APP_NAME', '勉強タイマー');
define('STATUS_STUDYING', '勉強中');
define('STATUS_FINISHED', '完了');

// 状態管理用のJSONファイル
$STATE_FILE = 'study_state.json';

// 初期状態の設定
if (!file_exists($STATE_FILE)) {
    $initial_state = [
        'is_studying' => false,
        'current_subject' => '',
        'start_time' => null,
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
                $state['is_studying'] = true;
                $state['start_time'] = $current_time;
                $state['current_subject'] = $_POST['subject'];
                break;
            case 'stop':
                if ($state['is_studying']) {
                    $elapsed = $current_time - $state['start_time'];
                    $state['today_sessions'][] = [
                        'subject' => $state['current_subject'],
                        'start' => date('H:i:s', $state['start_time']),
                        'end' => date('H:i:s', $current_time),
                        'duration' => $elapsed
                    ];
                }
                $state['is_studying'] = false;
                $state['current_subject'] = '';
                break;
        }
        $state['last_update'] = date('Y-m-d H:i:s');
        file_put_contents($STATE_FILE, json_encode($state));
    }
    
    if (isset($_POST['ajax'])) {
        header('Content-Type: application/json');
        echo json_encode($state);
        exit;
    }
}

// 現在の状態を取得
$state = json_decode(file_get_contents($STATE_FILE), true);

// 日付が変わっていた場合はバックアップして初期化
if (date('Y-m-d', strtotime($state['last_update'])) !== date('Y-m-d')) {
    $yesterday = date('Ymd', strtotime('-1 day'));
    $backup_file = sprintf('study_%s.json', $yesterday);
    file_put_contents($backup_file, json_encode($state));

    $state = [
        'is_studying' => false,
        'current_subject' => '',
        'start_time' => null,
        'today_sessions' => [],
        'last_update' => date('Y-m-d H:i:s')
    ];
    file_put_contents($STATE_FILE, json_encode($state));
}

// 過去7日間のデータを取得
function getLastSevenDaysData() {
    $data = [];
    $today_total = 0;
    
    // 本日の学習時間を計算
    global $state;
    foreach ($state['today_sessions'] as $session) {
        $today_total += $session['duration'];
    }
    if ($state['is_studying']) {
        $today_total += time() - $state['start_time'];
    }
    
    // 曜日の配列
    $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Ymd', strtotime("-$i days"));
        $timestamp = strtotime("-$i days");
        $weekday = $weekdays[date('w', $timestamp)];
        
        if ($i === 0) {
            // 本日のデータ
            $data[] = [
                'date' => date('n/j', $timestamp) . '(' . $weekday . ')',
                'total' => round($today_total / 60) // 分単位に変換
            ];
        } else {
            $file = sprintf('study_%s.json', $date);
            if (file_exists($file)) {
                $day_data = json_decode(file_get_contents($file), true);
                $total = 0;
                foreach ($day_data['today_sessions'] as $session) {
                    $total += $session['duration'];
                }
                $data[] = [
                    'date' => date('n/j', $timestamp) . '(' . $weekday . ')',
                    'total' => round($total / 60) // 分単位に変換
                ];
            } else {
                $data[] = [
                    'date' => date('n/j', $timestamp) . '(' . $weekday . ')',
                    'total' => 0
                ];
            }
        }
    }
    return $data;
}

// 科目ごとの合計時間を計算
function calculateSubjectTotals($sessions) {
    $totals = [];
    foreach ($sessions as $session) {
        $subject = $session['subject'];
        if (!isset($totals[$subject])) {
            $totals[$subject] = 0;
        }
        $totals[$subject] += $session['duration'];
    }
    return $totals;
}

$weekly_data = getLastSevenDaysData();
$subject_totals = calculateSubjectTotals($state['today_sessions']);

// 科目ごとの色を動的に生成
function getSubjectColor($subject) {
    return '#' . substr(md5($subject), 0, 6);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        #status {
            padding: 10px;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        #status.studying {
            background-color: #e8f5e9;
        }
        #status.finished {
            background-color: #f5f5f5;
        }
        .chart-container {
            position: relative;
            margin: auto;
            height: 300px;
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-center mb-0"><?php echo APP_NAME; ?></h2><a href="index.php">&gt;切り替え</a>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <h3 id="status" class="mb-3 <?php echo $state['is_studying'] ? 'studying' : 'finished'; ?>">
                                <?php echo $state['is_studying'] ? STATUS_STUDYING : STATUS_FINISHED; ?>
                            </h3>
                            
                            <div class="mb-3">
                                <input type="text" id="subjectInput" class="form-control form-control-lg"
                                       placeholder="勉強内容を入力（例：英語のライティング、漢字の練習など）"
                                       <?php echo $state['is_studying'] ? 'disabled' : ''; ?>>
                            </div>

                            <div class="d-grid gap-2 d-md-block">
                                <button id="startBtn" class="btn btn-primary btn-lg mx-2" <?php echo $state['is_studying'] ? 'disabled' : ''; ?>>
                                    スタート
                                </button>
                                <button id="stopBtn" class="btn btn-secondary btn-lg mx-2" <?php echo !$state['is_studying'] ? 'disabled' : ''; ?>>
                                    ストップ
                                </button>
                            </div>
                        </div>

                        <!-- 科目ごとの勉強時間グラフ -->
                        <div class="chart-container mb-4">
                            <canvas id="subjectChart"></canvas>
                        </div>

                        <!-- 本日の記録 -->
                        <div class="mt-4">
                            <h4>本日の記録</h4>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>科目</th>
                                            <th>開始時刻</th>
                                            <th>終了時刻</th>
                                            <th>学習時間</th>
                                        </tr>
                                    </thead>
                                    <tbody id="sessionLog">
                                        <?php foreach ($state['today_sessions'] as $session): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($session['subject']); ?></td>
                                            <td><?php echo $session['start']; ?></td>
                                            <td><?php echo $session['end']; ?></td>
                                            <td><?php echo sprintf('%d分', round($session['duration'] / 60)); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if ($state['is_studying']): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($state['current_subject']); ?></td>
                                            <td><?php echo date('H:i:s', $state['start_time']); ?></td>
                                            <td><?php echo STATUS_STUDYING; ?></td>
                                            <td>-</td>
                                        </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- 過去7日間の勉強時間グラフ -->
                        <div class="chart-container mt-4">
                            <canvas id="weeklyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let isStudying = <?php echo $state['is_studying'] ? 'true' : 'false'; ?>;
        let startTime = <?php echo $state['start_time'] ?: 'null'; ?>;
        let currentSubject = '<?php echo addslashes($state['current_subject']); ?>';
        
        // グラフの初期化
        const subjectData = <?php echo json_encode($subject_totals); ?>;
        const weeklyData = <?php echo json_encode($weekly_data); ?>;

        // 科目別グラフの描画
        const subjectCtx = document.getElementById('subjectChart').getContext('2d');
        new Chart(subjectCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(subjectData),
                datasets: [{
                    label: '学習時間（分）',
                    data: Object.values(subjectData).map(time => Math.round(time / 60)),
                    backgroundColor: Object.keys(subjectData).map(subject => '#' + subject.split('').reduce((hash, char) => {
                        return ((hash << 5) - hash) + char.charCodeAt(0) | 0;
                    }, 0).toString(16).slice(-6)),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: '時間（分）'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: '科目別学習時間'
                    }
                }
            }
        });

        // 週間グラフの描画
        const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
        new Chart(weeklyCtx, {
            type: 'bar',
            data: {
                labels: weeklyData.map(day => day.date),
                datasets: [{
                    label: '1日の学習時間（分）',
                    data: weeklyData.map(day => day.total),
                    backgroundColor: '#4CAF50',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: '時間（分）'
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: '過去7日間の学習時間'
                    }
                }
            }
        });

        // ボタンのイベントリスナー
        document.getElementById('startBtn').addEventListener('click', function() {
            const subject = document.getElementById('subjectInput').value.trim();
            if (!subject) {
                alert('科目を入力してください');
                return;
            }
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=start&subject=${encodeURIComponent(subject)}`
            }).then(() => location.reload());
        });

        document.getElementById('stopBtn').addEventListener('click', function() {
            fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=stop'
            }).then(() => location.reload());
        });

        // 状態の定期更新
        setInterval(() => {
            if (isStudying) {
                fetch(window.location.href, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'ajax=1'
                })
                .then(response => response.json())
                .then(state => {
                    if (state.is_studying !== isStudying) {
                        location.reload();
                    }
                });
            }
        }, 5000);
    </script>
</body>
</html>
