# itmaroon-demo — Playground デモのキット

sazanka（陶芸工房のサイト）を、WordPress Playground で見せるためのキット。リンクを開くと、ブラウザーの中で、プラグイン・テーマ・見本の中身がそろったサイトが立ち上がる。

## 中身

| ファイル | 役目 |
|---|---|
| `blueprint.json` | Playground の手順書（`build.py` が作る。手で直さない） |
| `theme/sazanka-demo.zip` | block-theme-migration の「Demo export」で書き出したテーマ（リンク・メディアを残し、サイトのアドレスを相対にしたもの） |
| `theme/kiln-theme.zip` | 上を、テーマの標準の形（最上位にフォルダ）に直したもの（`build.py` が作る） |
| `mu-plugin/itmaroon-demo-setup.php` | 投稿タイプ（works、concept、lesson_resource）、タクソノミー（creator、kind）、作品のカスタムフィールドを登録する |
| `seed.json` | 見本の中身: 作品11、コンセプト3、固定ページ（予約・お問い合わせ）、このデモに含めないページ（案内だけ）、画像22、予約のリソース |
| `seed.php` | 見本の中身を、同じ ID で作る。予約の枠は、実行の日から 5 週間分を、毎回作る（古くならない） |
| `uploads/`、`uploads.zip` | 見本の画像（長辺 1200px に縮小）。ロゴ、ボタンの絵、作品の写真 |
| `build.py` | `uploads.zip`、`theme/kiln-theme.zip`、`blueprint.json` を組み立てる |
| `serve.py` | ローカルの確認用。このフォルダを、CORS 付きで配る |
| `tools/kit_generate.php` | `seed.json` と `uploads/` を作る（sazanka を読むだけ） |
| `tools/cgi_server.py` | 複製の WordPress を、ブラウザーで確認するための小さなサーバー（php-cgi を使う。PHP の組み込みサーバーは、1リクエストずつなので、エディターが動かない） |

## Blueprint の手順（`build.py`）

1. wordpress.org から、プラグインを入れて有効にする: secure-custom-fields、block-collections、query-blocks、slide-blocks、form-send-blocks、itmaroon-booking-block、opening-block、markdown-block、itmaroon-ec-relate-blocks
2. `mu-plugin`、`seed.json`、`seed.php` を、WordPress の中へ書く
3. `uploads.zip` を `wp-content/uploads` へ展開する
4. テーマを入れて有効にする
5. `seed.php` を実行する（添付ファイルを同じ ID で作る → 作品・コンセプト → 固定ページ → 予約のリソースと枠）

## 作り直す

```
# 1. block-theme-migration で、sazanka（kiln-theme が有効）から「Demo export」→ theme/sazanka-demo.zip に置く
# 2. 見本の中身を作り直す（sazanka を読むだけ。画像の縮小を含む）
#    WordPress を読み込んだ状態で tools/kit_generate.php を実行する（環境変数 OUT=このフォルダ、DEMO_ZIP=theme/sazanka-demo.zip）
# 3. 組み立て
python build.py https://raw.githubusercontent.com/<owner>/<repo>/main
```

## 公開する前に（ユーザーの作業と、確認）

- **プラグインのリリースが先。** 公式にある版は、いまの開発版と、中身が違う（block-collections は 105 ファイル中 63 が違う、など）。公式の版だけで動かすと、デザインが崩れる（余白と幅がずれ、右の絞り込みが出ず、画像が全幅になる）。開発版で行ったブロックの変更を、リリースしてから、デモを公開する。
- **画像の権利。** 見本の画像は、ユーザーの写真（作品、ロゴ）。公開してよいかを、決めてから、リポジトリに置く。
- **ホスティング。** `BASE_URL`（GitHub の raw など）は、CORS が許可されていること。
- **Playground 上の動作は、未確認。** ここでの確認は、同じ手順を、通常の WordPress（空の WordPress ＋ 公式のプラグイン ＋ このキット）で行ったもの。Playground の中で、`installTheme`（URL の ZIP）、`unzip`、`runPHP` の書き方が、そのまま動くかは、まだ試せていない（内蔵のブラウザーから、ローカルのファイルを取得できなかったため）。公開の URL に置いたあとで、実際に開いて確認する。

## 含めないもの

- ショップ（外部の Shopify が必要）、会員登録、ログイン、技術紹介、教室案内のページ。案内だけのページ（「このデモには含まれていません」）を置く。リンク切れを避けるため。
- メールの送信（フォームは表示されるが、送れない）。
