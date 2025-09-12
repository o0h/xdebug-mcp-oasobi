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
# actions: null, array, loop, memory, divide, recursive, performance
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

## 使い方

### 1. セッション外でのユーザー操作

#### 基本的な動作確認
```bash
# アプリケーションが動作することを確認
./app list

# 各コマンドを単体実行してエラーを再現
./app debug:buggy null     # null参照エラー
./app debug:buggy array 5  # 配列インデックスエラー
./app debug:buggy divide 0 # ゼロ除算エラー

# 通常のコマンドも動作確認
./app posts:fetch 5
./app http:test GET
```

#### エラーログの確認
```bash
# PHP エラーログを確認（環境により異なる）
tail -f /var/log/php_errors.log
# または
php -r "echo ini_get('error_log');"
```

### 2. AIエージェントセッションでの実践例

#### 🔍 デバッグセッション例

**プロンプト例1: null参照エラーの調査**
```
BuggyCommandのnull参照エラーを調査して修正してください。
まずは ./app debug:buggy null でエラーを再現してから、
xdebug-debugツールを使って問題の箇所を特定してください。
```

**AIが自動実行するコマンド:**
```bash
./vendor/bin/xdebug-debug --break='src/Command/BuggyCommand.php:77:$user==null' \
  --exit-on-break --context="Investigating null reference in fetchUser" \
  --json -- php ./app debug:buggy null
```

**プロンプト例2: 配列エラーの調査**
```
debug:buggy array コマンドでインデックス「5」を指定した時のエラーを
xdebug-mcpで詳しく調査して、どの変数がどんな値になっているか教えてください。
```

**プロンプト例3: パフォーマンス分析**
```
posts:fetchコマンドが遅い原因を分析してください。
50件の投稿をコメント付きで取得した時のボトルネックを特定してください。
```

#### 🚀 高度なデバッグセッション例

**プロンプト例4: メモリリーク調査**
```
debug:buggy memory コマンドを実行してメモリリークの様子を詳細に分析してください。
どの行でメモリが急激に増加するか、循環参照がどこで発生するかを特定してください。
```

**プロンプト例5: 無限ループの検出**
```
debug:buggy loop 10.5 を実行した時に無限ループが発生する理由を
ステップ実行で詳しく調べて、float値の比較で起きる問題を説明してください。
```

**プロンプト例6: 再帰関数のバグ**
```
debug:buggy recursive 5 の実行で予想と違う結果になる理由を調査してください。
代入演算子と比較演算子の違いによる問題を特定してください。
```

**プロンプト例7: 高度なパフォーマンス問題（★xdebug-mcpの真価を発揮）**
```
debug:buggy performance 10 コマンドを実行して、隠れたN+1クエリ問題と
複雑なメモリリークパターンを詳細に分析してください。静的解析では
絶対に見抜けないパフォーマンス問題をxdebug-mcpで明らかにしてください。
```

**AIが実行する高度な分析:**
```bash
# パフォーマンスプロファイリングでボトルネック特定
./vendor/bin/xdebug-profile --context="隠れたN+1クエリのパフォーマンス分析" \
  -- php ./app debug:buggy performance 10

# 実行トレースでメソッド呼び出しパターン確認  
./vendor/bin/xdebug-trace --context="バッチ処理のN+1パターン追跡" \
  -- php ./app debug:buggy performance 5

# デバッグで状態変化を詳細分析
./vendor/bin/xdebug-debug --break='src/Command/BuggyCommand.php:175' \
  --context="キャッシュ状態とメモリ使用量の追跡" --steps=50 \
  -- php ./app debug:buggy performance 3
```

**期待される発見:**
- 10ユーザーに対して30回のAPIコール（3倍のN+1問題）
- 1ユーザーあたり3つのキャッシュエントリによるメモリリーク
- プレミアムユーザー処理が実行時間の16%を占有
- 複雑な状態変化による予測不可能なメモリ使用パターン

### 3. AI分析の活用パターン

#### パターンA: エラー特定→修正→テスト
```
1. 「debug:buggy nullの問題を特定して修正してください」
2. AIがxdebug-debugで原因特定
3. AIがコード修正を提案
4. 「修正後のコードでテストしてください」
5. AIが修正後の動作を確認
```

#### パターンB: パフォーマンス調査→最適化
```
1. 「posts:fetch 100 --with-commentsが遅い原因を調査してください」
2. AIがxdebug-profileで分析
3. 「最も時間のかかる処理を最適化してください」
4. AIが改善提案を実施
5. 「パフォーマンスが改善されたか確認してください」
```

#### パターンC: 実行フロー理解→リファクタリング
```
1. 「posts:analyzeの処理フローを詳しく分析してください」
2. AIがxdebug-traceで実行順序を確認
3. 「コードをより読みやすく整理してください」
4. AIがリファクタリングを提案
5. 「動作が変わっていないことを確認してください」
```

### 4. よく使うデバッグコマンド集

```bash
# 条件付きブレークポイント（値がnullの時に停止）
./vendor/bin/xdebug-debug --break='file.php:line:$var==null' --exit-on-break --json -- php script.php

# 特定行で必ず停止
./vendor/bin/xdebug-debug --break='file.php:line' --exit-on-break --json -- php script.php

# 複数の条件で停止
./vendor/bin/xdebug-debug --break='file.php:10:$id==0,file.php:20:empty($name)' --exit-on-break -- php script.php

# パフォーマンス分析
./vendor/bin/xdebug-profile --context="Performance analysis" -- php script.php

# 実行トレース（最初の100ステップ）
./vendor/bin/xdebug-trace --steps=100 --context="Execution flow" -- php script.php

# コードカバレッジ
./vendor/bin/xdebug-coverage --context="Test coverage analysis" -- php script.php
```

### 5. トラブルシューティング

#### Xdebugが動作しない場合
```bash
# Xdebug拡張の確認
php -m | grep xdebug

# xdebug-mcpの動作確認
./vendor/bin/xdebug-debug --help
```

#### ブレークポイントが効かない場合
```bash
# ファイルパスが正しいか確認
ls -la src/Command/BuggyCommand.php

# 相対パスではなくフルパスを使用
./vendor/bin/xdebug-debug --break="$(pwd)/src/Command/BuggyCommand.php:77:$user==null"
```

## 含まれている意図的なバグ

### 基本的なバグ（静的解析でも発見可能）
- `BuggyCommand::handleNullError()` - null参照エラー
- `BuggyCommand::handleArrayError()` - 配列キーエラー  
- `BuggyCommand::handleInfiniteLoop()` - 無限ループの可能性
- `BuggyCommand::recursiveFunction()` - 代入演算子の誤り（`=`を`==`の代わりに使用）

### 高度なパフォーマンス問題（xdebug-mcpでのみ発見可能）
- `BuggyCommand::handlePerformanceIssue()` - 隠れたN+1クエリ問題
  - `UserService::getBatchUsers()` - 見た目はバッチ処理だが実際は個別API呼び出し
  - `CacheManager::storeComplexData()` - 冗長なデータ保存によるメモリリーク
  - `DataProcessor::processPremiumUser()` - 指数的複雑度による処理時間増大
  
**⭐️ 特に`performance`アクションは、Forward Trace™デバッグの真価を体験できる実例です。**

これらのバグをxdebug-mcpで発見・修正してみてください。