const fs = require('fs');
const path = require('path');

const codeHtmlDir = path.join(__dirname, 'code_html');
const viewsDir = path.join(__dirname, 'resources', 'views');

const mappings = [
    { src: 'diapredict_home_education/code.html', dest: 'home.blade.php' },
    { src: 'diapredict_login/code.html', dest: 'auth/login.blade.php' },
    { src: 'diapredict_registration/code.html', dest: 'auth/register.blade.php' },
    { src: 'diapredict_analysis_form/code.html', dest: 'analysis/form.blade.php' },
    { src: 'diapredict_analysis_history/code.html', dest: 'analysis/history.blade.php' }
];

function extractMainContent(html) {
    let mainStart = html.indexOf('<main');
    if (mainStart === -1) {
        // Fallback if no <main> tag, try to find content between nav and footer
        const navEnd = html.indexOf('</nav>');
        const footerStart = html.indexOf('<footer');
        if (navEnd !== -1 && footerStart !== -1) {
            return html.substring(navEnd + 6, footerStart).trim();
        }
        return '';
    }

    let mainEnd = html.lastIndexOf('</main>');
    if (mainEnd !== -1) {
        return html.substring(mainStart, mainEnd + 7);
    }
    return '';
}

mappings.forEach(mapping => {
    const srcPath = path.join(codeHtmlDir, mapping.src);
    const destPath = path.join(viewsDir, mapping.dest);

    if (!fs.existsSync(srcPath)) {
        console.warn(`Warning: Source file not found: ${srcPath}`);
        return;
    }

    const htmlContent = fs.readFileSync(srcPath, 'utf8');
    let mainContent = extractMainContent(htmlContent);

    if (!mainContent) {
        console.warn(`Warning: Could not extract content from ${srcPath}`);
        return;
    }

    // Wrap with layout
    const bladeContent = `@extends('layouts.app')

@section('content')
${mainContent}
@endsection
`;

    // Ensure directory exists
    const destDir = path.dirname(destPath);
    if (!fs.existsSync(destDir)) {
        fs.mkdirSync(destDir, { recursive: true });
    }

    fs.writeFileSync(destPath, bladeContent, 'utf8');
    console.log(`Created ${mapping.dest}`);
});
