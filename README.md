## Overview
Game Time Tracker is a PHP-based web application that helps users monitor and manage their gaming sessions. It features a real-time timer, session logging, and automatic daily tracking with warning systems when gaming time exceeds preset limits.

Key features:
- Real-time session tracking
- Daily session history
- Visual progress bar
- Automatic warnings for extended gaming periods
- Daily data backup and reset
- Responsive design for all devices

## Installation
1. Clone the repository:
```bash
git clone https://github.com/daishir0/game-time-tracker.git
```

2. Move to your web server directory:
```bash
mv game-time-tracker /path/to/your/web/directory
```

3. Ensure write permissions for the application:
```bash
chmod 755 /path/to/your/web/directory/game-time-tracker
chmod 666 /path/to/your/web/directory/game-time-tracker/timer_state.json
```

4. Configure your web server (Apache/Nginx) to serve the directory

## Usage
1. Access the application through your web browser
2. Click "スタート" to begin a gaming session
3. Click "ストップ" to end the current session
4. Monitor your gaming time through:
   - Real-time countdown timer
   - Progress bar
   - Daily session log
   - Total gaming time for the day

## Notes
- The application automatically resets data at midnight (JST)
- Previous day's data is automatically backed up
- The default maximum gaming time is set to 1 hour (3600 seconds)
- Warning systems activate when:
  - Gaming time exceeds 1 hour (orange warning)
  - Gaming time exceeds 2 hours (red warning)

## License
This project is licensed under the MIT License - see the LICENSE file for details.

---

# ゲームタイムトラッカー
## 概要
ゲームタイムトラッカーは、ゲームプレイ時間を監視・管理するためのPHPベースのWebアプリケーションです。リアルタイムタイマー、セッション記録、プリセットされた制限時間を超えた場合の警告システムを備えています。

主な機能：
- リアルタイムセッション追跡
- 日別セッション履歴
- ビジュアルプログレスバー
- 長時間プレイ時の自動警告
- 日次データバックアップとリセット
- レスポンシブデザイン

## インストール方法
1. リポジトリをクローンします：
```bash
git clone https://github.com/daishir0/game-time-tracker.git
```

2. Webサーバーのディレクトリに移動します：
```bash
mv game-time-tracker /path/to/your/web/directory
```

3. アプリケーションの書き込み権限を設定します：
```bash
chmod 755 /path/to/your/web/directory/game-time-tracker
chmod 666 /path/to/your/web/directory/game-time-tracker/timer_state.json
```

4. WebサーバーGgApache/Nginx)の設定を行います

## 使い方
1. Webブラウザからアプリケーションにアクセス
2. 「スタート」をクリックしてゲームセッションを開始
3. 「ストップ」をクリックしてセッションを終了
4. 以下の方法でゲーム時間を監視：
   - リアルタイムカウントダウンタイマー
   - プログレスバー
   - 日別セッションログ
   - 1日の合計ゲーム時間

## 注意点
- データは毎日深夜に自動的にリセットされます
- 前日のデータは自動的にバックアップされます
- デフォルトの最大ゲーム時間は1時間（3600秒）に設定されています
- 警告システムは以下の場合に作動します：
  - ゲーム時間が1時間を超えた場合（オレンジ警告）
  - ゲーム時間が2時間を超えた場合（赤警告）

## ライセンス
このプロジェクトはMITライセンスの下でライセンスされています。詳細はLICENSEファイルを参照してください。
