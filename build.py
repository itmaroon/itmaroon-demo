"""デモキットを組み立てる。

使い方:  python build.py <BASE_URL>
  BASE_URL: このキットのファイルを置く、公開の URL（末尾の / なし）。例:
            https://raw.githubusercontent.com/<owner>/<repo>/main
            ローカルの確認では、http://127.0.0.1:8100

出力（このフォルダの中）:
  uploads.zip          … uploads/ を、画像の展開用に固めたもの
  theme/kiln-theme.zip … theme/sazanka-demo.zip を、テーマの標準の形（最上位にフォルダ）に直したもの
  blueprint.json       … BASE_URL を入れた Blueprint
"""
import json
import os
import sys
import zipfile

here = os.path.dirname(os.path.abspath(__file__))
base = sys.argv[1].rstrip('/') if len(sys.argv) > 1 else 'http://127.0.0.1:8100'

# 1) uploads.zip
with zipfile.ZipFile(os.path.join(here, 'uploads.zip'), 'w', zipfile.ZIP_DEFLATED) as z:
    root = os.path.join(here, 'uploads')
    for d, _, files in os.walk(root):
        for f in files:
            full = os.path.join(d, f)
            z.write(full, os.path.relpath(full, root).replace('\\', '/'))

# 2) テーマ（最上位に kiln-theme/ のフォルダ）
src = zipfile.ZipFile(os.path.join(here, 'theme', 'sazanka-demo.zip'))
with zipfile.ZipFile(os.path.join(here, 'theme', 'kiln-theme.zip'), 'w', zipfile.ZIP_DEFLATED) as z:
    for info in src.infolist():
        if info.is_dir():
            continue
        z.writestr('kiln-theme/' + info.filename, src.read(info.filename))

# 3) Blueprint
def plugin(slug):
    return {'step': 'installPlugin', 'pluginData': {'resource': 'wordpress.org/plugins', 'slug': slug}, 'options': {'activate': True}}


def url(path):
    return {'resource': 'url', 'url': base + '/' + path}


blueprint = {
    '$schema': 'https://playground.wordpress.net/blueprint-schema.json',
    'meta': {'title': 'ITmaroon blocks — sample pottery studio site', 'author': 'itmaroon'},
    'landingPage': '/',
    'preferredVersions': {'php': '8.2', 'wp': 'latest'},
    'features': {'networking': True},
    'steps': [
        plugin('secure-custom-fields'),
        plugin('block-collections'),
        plugin('query-blocks'),
        plugin('slide-blocks'),
        plugin('form-send-blocks'),
        plugin('itmaroon-booking-block'),
        plugin('opening-block'),
        plugin('markdown-block'),
        plugin('itmaroon-ec-relate-blocks'),
        {'step': 'mkdir', 'path': '/wordpress/wp-content/itmaroon-demo'},
        {'step': 'mkdir', 'path': '/wordpress/wp-content/mu-plugins'},
        {'step': 'writeFile', 'path': '/wordpress/wp-content/mu-plugins/itmaroon-demo-setup.php', 'data': url('mu-plugin/itmaroon-demo-setup.php')},
        {'step': 'writeFile', 'path': '/wordpress/wp-content/itmaroon-demo/seed.json', 'data': url('seed.json')},
        {'step': 'writeFile', 'path': '/wordpress/wp-content/itmaroon-demo/seed.php', 'data': url('seed.php')},
        {'step': 'unzip', 'zipFile': url('uploads.zip'), 'extractToPath': '/wordpress/wp-content/uploads'},
        {'step': 'installTheme', 'themeData': url('theme/kiln-theme.zip'), 'options': {'activate': True}},
        {'step': 'runPHP', 'code': "<?php require '/wordpress/wp-content/itmaroon-demo/seed.php';"},
    ],
}
with open(os.path.join(here, 'blueprint.json'), 'w', encoding='utf-8') as f:
    json.dump(blueprint, f, ensure_ascii=False, indent=2)

print('built for', base)
for name in ('uploads.zip', 'theme/kiln-theme.zip', 'blueprint.json', 'seed.json'):
    print(f'  {name}: {os.path.getsize(os.path.join(here, name)) / 1024:.0f} KB')
