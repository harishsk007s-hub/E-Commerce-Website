const fs = require('fs');
const path = require('path');

const excludeDirs = ['node_modules', 'dist', 'legacy', '.git', '.zencoder', '.zenflow', 'public'];
const includeExtensions = ['.ts', '.tsx', '.js', '.jsx', '.css', '.html', '.php', '.sql', '.json'];
const outputFile = 'codebase.txt';

function getAllFiles(dirPath, arrayOfFiles) {
  const files = fs.readdirSync(dirPath);

  arrayOfFiles = arrayOfFiles || [];

  files.forEach(function(file) {
    const fullPath = path.join(dirPath, file);
    if (fs.statSync(fullPath).isDirectory()) {
      if (!excludeDirs.includes(file)) {
        getAllFiles(fullPath, arrayOfFiles);
      }
    } else {
      const ext = path.extname(file).toLowerCase();
      if (includeExtensions.includes(ext) && file !== outputFile && file !== 'package-lock.json') {
        arrayOfFiles.push(fullPath);
      }
    }
  });

  return arrayOfFiles;
}

const allFiles = getAllFiles('.');
let combinedContent = '';

allFiles.forEach(file => {
  const relativePath = path.relative('.', file);
  const content = fs.readFileSync(file, 'utf8');
  combinedContent += `\n\n--- FILE: ${relativePath} ---\n\n`;
  combinedContent += content;
});

fs.writeFileSync(outputFile, combinedContent);
console.log(`Successfully created ${outputFile} with ${allFiles.length} files.`);
