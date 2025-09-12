# xdebug-mcp-oasobi

xdebug-mcpの機能を試すためのサンプルアプリケーションです。

## 目的

- xdebug-mcpの各種デバッグツールを実際に試す
- 意図的なバグを使ってデバッグの練習をする
- API Keyなしで動作するモックAPIを使った実践的なCLIツール

## セットアップ

```bash
composer install
```

## コマンド一覧

### `posts:fetch` - 投稿データ取得
```bash
./app posts:fetch [limit] [--with-comments] [--format=table|json|simple] [--cache]
```

### `posts:analyze` - 投稿分析
```bash
./app posts:analyze [--posts=20] [--memory-test] [--batch-size=50]
```

### `http:test` - HTTPテスト
```bash
./app http:test [method] [--delay=3] [--benchmark] [--data='{"key":"value"}']
```

### `debug:buggy` - デバッグ練習用
```bash
./app debug:buggy <action> [value]
# actions: null, array, loop, memory, divide, recursive
```

## xdebug-mcpとの連携

### 1. ブレークポイントデバッグ
```bash
# null参照エラーの場所で止める
./vendor/bin/xdebug-debug --break='src/Command/BuggyCommand.php:75:$user==null' \
  --exit-on-break --context="Debugging null reference" \
  -- php ./app debug:buggy null
```

### 2. パフォーマンスプロファイリング
```bash
# API呼び出しのパフォーマンス分析
./vendor/bin/xdebug-profile --context="API performance analysis" \
  -- php ./app posts:fetch 20 --with-comments
```

### 3. 実行トレース
```bash
# 処理フローの詳細追跡
./vendor/bin/xdebug-trace --context="Execution flow analysis" \
  -- php ./app posts:analyze --memory-test
```

## AIへのサンプルプロンプト

```
# null参照エラーをデバッグして
./vendor/bin/xdebug-debug --break='src/Command/BuggyCommand.php:75:$user==null' --exit-on-break -- php ./app debug:buggy null

# posts:fetchが遅い理由を分析して
./vendor/bin/xdebug-profile --context="Performance bottleneck investigation" -- php ./app posts:fetch 50 --with-comments

# メモリリークを調査して
./vendor/bin/xdebug-trace --context="Memory leak analysis" -- php ./app debug:buggy memory
```

## 含まれている意図的なバグ

- `BuggyCommand::handleNullError()` - null参照エラー
- `BuggyCommand::handleArrayError()` - 配列キーエラー  
- `BuggyCommand::handleInfiniteLoop()` - 無限ループの可能性
- `BuggyCommand::recursiveFunction()` - 代入演算子の誤り（`=`を`==`の代わりに使用）

これらのバグをxdebug-mcpで発見・修正してみてください。