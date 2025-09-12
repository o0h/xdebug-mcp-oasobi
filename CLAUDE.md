@vendor/koriym/xdebug-mcp/docs/debug_guideline_for_ai.md

## Xdebug環境設定のトラブルシューティング

### 事前チェックコマンド
```bash
# Xdebugが読み込まれているか確認
php -m | grep xdebug

# 現在のXdebugモード確認
php -i | grep xdebug.mode

# トレースファイル出力先確認
php -i | grep xdebug.output_dir
```

### 一般的な問題と解決法

1. **"Trace file not created"エラー**
   - 原因: Xdebugがtraceモードになっていない
   - 解決: `env XDEBUG_MODE=trace php -d xdebug.mode=trace [コマンド]`

2. **トレースファイルが見つからない**
   - 確認: `ls /usr/local/var/log/php/` でファイルの存在確認
   - 設定で出力先が`/tmp/`以外になっている可能性

### 推奨設定
環境に応じてXdebugモードを適切に設定:
```bash
# 開発時（デバッグ+トレース両方有効）
export XDEBUG_MODE=develop,trace
```

### xdebug-mcpツール実行時のベストプラクティス
```bash
# トレース実行の推奨コマンド
env XDEBUG_MODE=trace ./vendor/koriym/xdebug-mcp/bin/xdebug-trace --context="説明" -- php script.php

# デバッグ実行の推奨コマンド  
env XDEBUG_MODE=trace ./vendor/koriym/xdebug-mcp/bin/xdebug-debug --context="説明" -- php script.php
```
