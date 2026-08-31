// One-off script: run `npm i -D sharp` before using this, then `node scripts/generate-icons.mjs`.
// sharp isn't a project dependency — it's only needed when regenerating icons.
import sharp from 'sharp'
import { mkdirSync } from 'node:fs'

const srcSvg = 'public/icon-source.svg'
mkdirSync('public/icons', { recursive: true })

const sizes = [192, 512]
for (const size of sizes) {
  await sharp(srcSvg).resize(size, size).png().toFile(`public/icons/icon-${size}.png`)
}

// Maskable icon: same art but with extra padding so OS safe-zone crops don't clip it.
await sharp({
  create: { width: 512, height: 512, channels: 4, background: '#0a1628' },
})
  .composite([{ input: await sharp(srcSvg).resize(360, 360).toBuffer(), gravity: 'center' }])
  .png()
  .toFile('public/icons/icon-512-maskable.png')

await sharp(srcSvg).resize(180, 180).png().toFile('public/icons/apple-touch-icon.png')
await sharp(srcSvg).resize(32, 32).png().toFile('public/favicon-32.png')

console.log('Icons generated in public/icons/')
