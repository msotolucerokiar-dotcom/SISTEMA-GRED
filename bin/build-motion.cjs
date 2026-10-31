const { copyFileSync, mkdirSync } = require('node:fs');
const { join } = require('node:path');

const output = join(__dirname, '..', 'public', 'assets');
mkdirSync(output, { recursive: true });

for (const [source, destination] of [
  ['gsap.min.js', 'gsap.min.js'],
  ['ScrollTrigger.min.js', 'ScrollTrigger.min.js'],
]) {
  const packageFile = require.resolve(`gsap/dist/${source}`);
  copyFileSync(packageFile, join(output, destination));
}

console.log('GSAP browser assets copied to public/assets.');
