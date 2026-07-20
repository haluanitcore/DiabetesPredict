import fs from 'fs';

const htmlContent = fs.readFileSync('resources/views/stitch_raw/home.html', 'utf8');

const configMatch = htmlContent.match(/tailwind\.config\s*=\s*(\{[\s\S]*?\})\s*</);
if (!configMatch) {
    console.error("Config not found");
    process.exit(1);
}

// Very hacky JSON parse by replacing the js object notation with strict JSON
// Actually, it's already mostly valid JSON inside the script.
let configStr = configMatch[1];
// It has a trailing comma issue: `}, }, }`
configStr = configStr.replace(/,(\s*\})/g, '$1');

let config;
try {
    // using eval is safer here because it's a JS object, not strict JSON
    config = eval('(' + configStr + ')');
} catch (e) {
    console.error("Error parsing config", e);
    process.exit(1);
}

const theme = config.theme.extend;
let cssVars = "";

if (theme.colors) {
    for (const [key, value] of Object.entries(theme.colors)) {
        cssVars += `    --color-${key}: ${value};\n`;
    }
}

if (theme.spacing) {
    for (const [key, value] of Object.entries(theme.spacing)) {
        cssVars += `    --spacing-${key}: ${value};\n`;
    }
}

if (theme.borderRadius) {
    for (const [key, value] of Object.entries(theme.borderRadius)) {
        if (key === 'DEFAULT') {
            cssVars += `    --radius: ${value};\n`;
        } else {
            cssVars += `    --radius-${key}: ${value};\n`;
        }
    }
}

if (theme.fontFamily) {
    for (const [key, value] of Object.entries(theme.fontFamily)) {
        cssVars += `    --font-${key}: '${value[0]}', sans-serif;\n`;
    }
}

if (theme.fontSize) {
    for (const [key, value] of Object.entries(theme.fontSize)) {
        cssVars += `    --text-${key}: ${value[0]};\n`;
        if (value[1].lineHeight) {
            cssVars += `    --text-${key}--line-height: ${value[1].lineHeight};\n`;
        }
        if (value[1].fontWeight) {
            cssVars += `    --text-${key}--font-weight: ${value[1].fontWeight};\n`;
        }
        if (value[1].letterSpacing) {
            cssVars += `    --text-${key}--letter-spacing: ${value[1].letterSpacing};\n`;
        }
    }
}

const newCss = `
@theme {
${cssVars}
}
`;

let appCss = fs.readFileSync('resources/css/app.css', 'utf8');
// remove existing @theme block entirely, except for the import
appCss = appCss.replace(/@theme\s*\{[\s\S]*?\}/g, '');
appCss = appCss + '\n' + newCss;

fs.writeFileSync('resources/css/app.css', appCss);
console.log("Updated app.css with Stitch theme tokens");
