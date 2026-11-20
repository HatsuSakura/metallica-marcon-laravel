#!/usr/bin/env python3
"""Export pushed, clean application source and a handoff for QA/Jarvis."""
import argparse
import hashlib
import json
from pathlib import Path
import subprocess
import tarfile

root = Path(__file__).resolve().parents[1]
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--output', type=Path, default=root / '!RELEASE')
args = parser.parse_args()
def git(*arguments):
    return subprocess.check_output(['git', *arguments], cwd=root, text=True).strip()
if git('status', '--porcelain'):
    raise SystemExit('Commit all source changes before packaging')
commit = git('rev-parse', 'HEAD')
if not any(line.strip().startswith('origin/') for line in git('branch', '-r', '--contains', commit).splitlines()):
    raise SystemExit('Push the commit before packaging')
output = args.output.resolve() / commit
output.mkdir(parents=True, exist_ok=False)
archive = output / ('metallica-marcon-source-' + commit[:12] + '.tar.gz')
subprocess.run(['git', 'archive', '--format=tar.gz', '--output', str(archive), commit], cwd=root, check=True)
with tarfile.open(archive) as source:
    names = source.getnames()
    forbidden = [name for name in names if name == '.env' or name.startswith(('.git/', '.ai/', '.claude/', '.codex/', '.agents/', 'vendor/', 'node_modules/', 'storage/app/private/')) or name.endswith(('.sqlite', '.sqlite3', '.pem', '.key'))]
    if forbidden:
        raise SystemExit('Unexpected private/runtime files in archive: ' + ', '.join(forbidden))
checksum = hashlib.sha256(archive.read_bytes()).hexdigest()
manifest = {'source_commit': commit, 'source_branch': git('branch', '--show-current'),
            'source_archive': archive.name, 'source_sha256': checksum,
            'targets': {'qa': 'prepared-source-not-deployed', 'jarvis': 'prepared-source-not-deployed'},
            'session_lifetime_minutes': 720, 'runtime_data_included': False}
(output / 'release.json').write_text(json.dumps(manifest, indent=2) + '\n')
(output / 'HANDOFF.md').write_text((root / 'docs/qa/2026-11-20-dev-release.md').read_text())
(output / 'SHA256SUMS').write_text(''.join(hashlib.sha256(path.read_bytes()).hexdigest() + '  ' + path.name + '\n' for path in [archive, output / 'release.json', output / 'HANDOFF.md']))
print(json.dumps({'source_commit': commit, 'package': str(output), 'source_archive': str(archive), 'source_sha256': checksum}, indent=2))
