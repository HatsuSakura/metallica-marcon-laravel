#!/usr/bin/env python3
"""Build a reviewable Jarvis image without switching any running service."""
import argparse
import hashlib
import json
import os
from pathlib import Path
import re
import subprocess
import tarfile

parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('archive', type=Path)
parser.add_argument('--commit', required=True)
parser.add_argument('--sha256', required=True)
parser.add_argument('--root', type=Path, default=Path('/home/hatsusakura/apps/metallica-marcon'))
args = parser.parse_args()
if not re.fullmatch(r'[a-f0-9]{40}', args.commit):
    parser.error('Expected full source commit')
if hashlib.sha256(args.archive.read_bytes()).hexdigest() != args.sha256:
    raise SystemExit('Archive checksum mismatch')
os.umask(0o077)
release = args.root.parent / 'metallica-marcon-releases' / args.commit
source = release / 'source'
release.mkdir(parents=True, exist_ok=True)
# Do not overwrite an existing prepared/deployed source context.
source.mkdir()
with tarfile.open(args.archive) as archive:
    archive.extractall(source, filter='data')
services = ['metallicamarcon', 'metallicamarcon-worker', 'metallicamarcon-scheduler']
# Captured privately, never print a complete inspect/config/env result.
containers = json.loads(subprocess.check_output(['docker', 'inspect', *services]))
previous = {item['Name'].lstrip('/'): item['Image'] for item in containers}
vite = [value for value in containers[0]['Config']['Env'] if value.startswith('VITE_')]
(source / '.env.production').write_text('\n'.join(key + '=' + json.dumps(value) for key, value in (entry.split('=', 1) for entry in vite)) + '\n')
image = 'metallica-marcon-jarvis:' + args.commit[:12]
# Preserve original Compose, infrastructure and private runtime configuration.
# Override only application image/build and the explicitly approved session lifetime.
override = {'services': {service: {'image': image, 'build': {'context': str(source)}, 'environment': {'SESSION_LIFETIME': '720'}} for service in services}}
(release / 'override.yml').write_text(json.dumps(override, indent=2) + '\n')
with (release / 'build.log').open('wb') as log:
    result = subprocess.run(['docker', 'build', '-t', image, str(source)], stdout=log, stderr=subprocess.STDOUT)
if result.returncode:
    raise SystemExit('Build failed; see private release build.log. Active services unchanged.')
after = json.loads(subprocess.check_output(['docker', 'inspect', *services]))
if previous != {item['Name'].lstrip('/'): item['Image'] for item in after}:
    raise SystemExit('Active image changed concurrently; preparation requires review')
manifest = {'source_commit': args.commit, 'source_sha256': args.sha256, 'image': image,
            'environment': 'jarvis', 'status': 'prepared-not-deployed', 'previous_images': previous,
            'session_lifetime_minutes': 720, 'public_vite_keys_count': len(vite)}
(release / 'prepared.json').write_text(json.dumps(manifest, indent=2) + '\n')
print(json.dumps({'source_commit': args.commit, 'image': image, 'status': 'prepared-not-deployed', 'release': str(release)}))
