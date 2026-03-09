import { promises as fs } from 'node:fs';
import path from 'node:path';

const root = process.cwd();
const src = path.join(root, 'node_modules', 'monaco-editor', 'min');
const dest = path.join(root, 'public', 'monaco');

async function exists(p) {
  try { await fs.access(p); return true; } catch { return false; }
}

async function copyDir(from, to) {
  await fs.mkdir(to, { recursive: true });
  const entries = await fs.readdir(from, { withFileTypes: true });
  for (const e of entries) {
    const a = path.join(from, e.name);
    const b = path.join(to, e.name);
    if (e.isDirectory()) {
      await copyDir(a, b);
    } else if (e.isFile()) {
      await fs.copyFile(a, b);
    }
  }
}

if (!(await exists(src))) {
  // monaco-editor not installed yet
  process.exit(0);
}

await copyDir(src, dest);

