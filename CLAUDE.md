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

### 実行時環境変数設定（推奨アプローチ）

**最も効果的な方法：.xdebugrc設定ファイルアプローチ**
```bash
# 1回目のセットアップ（プロジェクトルートに作成）
echo "export XDEBUG_MODE=develop,trace" > .xdebugrc
echo "export XDEBUG_OUTPUT_DIR=/tmp" >> .xdebugrc

# 以降の使用（推奨）
source .xdebugrc && ./vendor/bin/xdebug-profile --context="分析内容" -- php script.php
source .xdebugrc && ./vendor/bin/xdebug-trace --context="分析内容" -- php script.php

# 従来の方法（代替手段）
XDEBUG_MODE=develop,trace ./vendor/bin/xdebug-trace --context="説明" -- php script.php
XDEBUG_MODE=develop,trace XDEBUG_OUTPUT_DIR=/tmp ./vendor/bin/xdebug-trace --context="説明" -- php script.php
```

**なぜ.xdebugrcアプローチが優れているか:**
- ✅ php.ini変更不要（システム影響なし）
- ✅ MCPツールのフォールバックを避けられる
- ✅ 設定を1箇所で管理
- ✅ チーム内で設定共有可能
- ✅ プロジェクト固有の設定が可能

### xdebug-mcpツール実行時のベストプラクティス
```bash
# ✅ 推奨：.xdebugrc使用パターン
source .xdebugrc && ./vendor/bin/xdebug-trace --context="N+1クエリとメモリリーク分析" -- php ./app debug:buggy performance 10
source .xdebugrc && ./vendor/bin/xdebug-profile --context="パフォーマンス分析" -- php ./app debug:buggy performance 10
source .xdebugrc && ./vendor/bin/xdebug-debug --context="ブレークポイント分析" -- php script.php

# 従来の環境変数直指定パターン（代替手段）
XDEBUG_MODE=develop,trace ./vendor/bin/xdebug-trace --context="N+1クエリとメモリリーク分析" -- php ./app debug:buggy performance 10
XDEBUG_MODE=develop,trace ./vendor/bin/xdebug-debug --context="ブレークポイント分析" -- php script.php

# 最終手段：PHP直接実行パターン（MCPツール完全失敗時のみ）
env XDEBUG_MODE=trace php -d xdebug.mode=trace -d xdebug.start_with_request=yes -d xdebug.output_dir=/tmp ./app debug:buggy performance 10
```

### トラブルシューティング時の段階的アプローチ（2024年9月更新版）

**推奨フロー：.xdebugrcアプローチ最優先**

1. **.xdebugrc設定ファイルアプローチ（最優先）**
   ```bash
   # セットアップ（1回のみ）
   echo "export XDEBUG_MODE=develop,trace" > .xdebugrc
   echo "export XDEBUG_OUTPUT_DIR=/tmp" >> .xdebugrc
   
   # 実行
   source .xdebugrc && ./vendor/bin/xdebug-profile --context="分析内容" -- php script.php
   ```

2. **従来のMCPツール試行**
   ```bash
   # mcp__xdebug__x-trace や mcp__xdebug__x-profile を使用
   # ただし"No result"が返ることが多い
   ```

3. **環境変数直指定による代替実行**
   ```bash
   # 直接バイナリを実行（環境変数付き）
   XDEBUG_MODE=develop,trace ./vendor/bin/xdebug-trace --context="分析内容" -- php script.php
   ```

4. **最終手段：PHP直接実行**
   ```bash
   # PHP直接実行でトレースファイル生成
   env XDEBUG_MODE=trace php -d xdebug.mode=trace -d xdebug.start_with_request=yes -d xdebug.output_dir=/tmp script.php
   # 生成されたトレースファイルを手動で解析
   ```

### AIエージェント向けの重要な実行指示

**MCPツール失敗時の自動対処法:**
- MCPツール（`mcp__xdebug__x-trace`, `mcp__xdebug__x-profile`）が"No result"を返す場合
- 即座に環境変数付きコマンドで再試行すること
- `XDEBUG_MODE=develop,trace`を前置して実行

**コンテキスト指定の重要性:**
- `--context`フラグには具体的で説明的な内容を記述
- 例：`--context="N+1クエリ問題：10ユーザー処理でのAPI呼び出し分析"`
- 分析結果の可読性と再現性確保のため

**トレースファイル確認コマンド:**
```bash
# 最近生成されたトレースファイル検索
find /usr/local/var/log/php -name "*.xt" -mmin -2 | head -1
find /tmp -name "*.xt" -mmin -2 | head -1
```
