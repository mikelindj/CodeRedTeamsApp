import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const packageDir = path.join(__dirname, 'teams-app-package');
const distDir = path.join(__dirname, 'dist');

// Create package directory
if (fs.existsSync(packageDir)) {
  fs.rmSync(packageDir, { recursive: true });
}
fs.mkdirSync(packageDir, { recursive: true });

// Copy manifest
fs.copyFileSync(
  path.join(__dirname, 'manifest.json'),
  path.join(packageDir, 'manifest.json')
);

// Copy built files from dist
if (fs.existsSync(distDir)) {
  const copyRecursiveSync = (src, dest) => {
    const exists = fs.existsSync(src);
    const stats = exists && fs.statSync(src);
    const isDirectory = exists && stats.isDirectory();
    if (isDirectory) {
      if (!fs.existsSync(dest)) {
        fs.mkdirSync(dest);
      }
      fs.readdirSync(src).forEach(childItemName => {
        copyRecursiveSync(
          path.join(src, childItemName),
          path.join(dest, childItemName)
        );
      });
    } else {
      fs.copyFileSync(src, dest);
    }
  };
  copyRecursiveSync(distDir, path.join(packageDir, 'dist'));
}

// Create placeholder icons if they don't exist
const iconOutline = path.join(packageDir, 'icon-outline.png');
const iconColor = path.join(packageDir, 'icon-color.png');

if (!fs.existsSync(iconOutline)) {
  // Create a simple placeholder (192x192 PNG)
  console.log('⚠️  Warning: icon-outline.png not found. Please add a 192x192 PNG icon.');
}

if (!fs.existsSync(iconColor)) {
  // Create a simple placeholder (192x192 PNG)
  console.log('⚠️  Warning: icon-color.png not found. Please add a 192x192 PNG icon.');
}

console.log('✅ Teams app package created in: teams-app-package/');
console.log('📦 Next steps:');
console.log('   1. Add icon-outline.png (192x192) to teams-app-package/');
console.log('   2. Add icon-color.png (192x192) to teams-app-package/');
console.log('   3. Update manifest.json with your deployed URL');
console.log('   4. Zip the teams-app-package folder');
console.log('   5. Upload to Teams Admin Center');

