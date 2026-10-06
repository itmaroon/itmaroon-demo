"""ローカルの確認用。このフォルダを、CORS を許可して配る（Playground が、ここから ZIP や画像を取りに来る）。

使い方:  python serve.py [port]    （既定 8100）
そのあと:  https://playground.wordpress.net/?blueprint-url=http://127.0.0.1:8100/blueprint.json
"""
import http.server
import os
import sys

port = int(sys.argv[1]) if len(sys.argv) > 1 else 8100
os.chdir(os.path.dirname(os.path.abspath(__file__)))


class Handler(http.server.SimpleHTTPRequestHandler):
    def end_headers(self):
        self.send_header('Access-Control-Allow-Origin', '*')
        self.send_header('Access-Control-Allow-Private-Network', 'true')  # 公開のページから、ローカルへのアクセス（Chrome の Private Network Access）
        self.send_header('Access-Control-Allow-Headers', '*')
        self.send_header('Cache-Control', 'no-store')
        super().end_headers()

    def do_OPTIONS(self):
        self.send_response(204)
        self.send_header('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS')
        self.end_headers()


http.server.ThreadingHTTPServer(('127.0.0.1', port), Handler).serve_forever()
