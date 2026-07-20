import fs from 'fs';
import path from 'path';

const rawDir = 'resources/views/stitch_raw';
const outDir = 'resources/views';

const filesToProcess = [
    { src: 'home.html', dest: 'home.blade.php' },
    { src: 'login.html', dest: 'auth/login.blade.php' },
    { src: 'register.html', dest: 'auth/register.blade.php' },
    { src: 'form.html', dest: 'analysis/form.blade.php' },
    { src: 'history.html', dest: 'analysis/history.blade.php' },
];

filesToProcess.forEach(file => {
    const srcPath = path.join(rawDir, file.src);
    const destPath = path.join(outDir, file.dest);
    
    // Create directory if it doesn't exist
    const dir = path.dirname(destPath);
    if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir, { recursive: true });
    }

    let content = fs.readFileSync(srcPath, 'utf8');
    
    // Extract everything between <body ...> and </body>
    const bodyMatch = content.match(/<body[^>]*>([\s\S]*?)<\/body>/i);
    
    if (bodyMatch && bodyMatch[1]) {
        let innerHtml = bodyMatch[1].trim();
        
        let bladeContent = `@extends('layouts.app')\n\n@section('content')\n${innerHtml}\n@endsection\n`;
        
        fs.writeFileSync(destPath, bladeContent, 'utf8');
        console.log(`Processed ${file.src} -> ${file.dest}`);
    } else {
        console.error(`Could not find body tag in ${file.src}`);
    }
});
