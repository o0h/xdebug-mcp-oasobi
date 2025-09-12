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

2. **MCPツールが応答しない (No result)**
   - 原因: Xdebugが`develop`モードに設定されている
   - 解決: `trace`モードに明示的に変更が必要
   - 確認: `php -i | grep xdebug.mode` で現在のモードをチェック
   - 対処: `env XDEBUG_MODE=trace` を使用してモードを強制変更

3. **トレースファイルが見つからない**
   - 確認: `ls /usr/local/var/log/php/` でファイルの存在確認
   - 設定で出力先が`/tmp/`以外になっている可能性
   - Xdebug 3.x系では`xdebug.output_dir`が正しいパラメータ

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

# MCPツールが応答しない場合の代替コマンド
env XDEBUG_MODE=trace php -d xdebug.mode=trace -d xdebug.start_with_request=yes -d xdebug.output_dir=/tmp ./app debug:buggy performance 10
```

### トラブルシューティング時の段階的アプローチ

1. **まずMCPツールを試行**
   ```bash
   # mcp__xdebug__x-trace や mcp__xdebug__x-profile を使用
   ```

2. **MCPツールが無反応の場合**
   ```bash
   # 直接バイナリを実行
   env XDEBUG_MODE=trace ./vendor/bin/xdebug-trace --context="分析内容" -- php script.php
   ```

3. **それでも失敗する場合**
   ```bash
   # PHP直接実行でトレースファイル生成
   env XDEBUG_MODE=trace php -d xdebug.mode=trace -d xdebug.start_with_request=yes -d xdebug.output_dir=/tmp script.php
   # 生成されたトレースファイルを手動で解析
   ```
