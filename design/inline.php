<?php

// Turns every page in src/ into a single self-contained file in design/, it opens anywhere (a double click, the
// Claude app's preview, an email) without an assets folder:
//
//   <link rel="stylesheet" href="assets/mockups.css">   the compiled Tailwind CSS, with the app's fonts embedded
//   <script src="assets/shared.js"></script>            src/shared.js (optional)
//   src="assets/logo.png"                               public/images/logo.png, the Costs to Expect "C" (optional)
//
// Usage: php design/inline.php path/to/compiled.css

$root = dirname(__DIR__);

$css = trim((string) file_get_contents($argv[1] ?? ''));
if ($css === '') {
    fwrite(STDERR, "No compiled CSS to inline\n");
    exit(1);
}

// The theme loads its fonts from /fonts/*.woff2, embed them so the page doesn't need the app's public folder.
$css = preg_replace_callback(
    '~url\((["\']?)/fonts/([a-z0-9.-]+\.woff2)\1\)~i',
    static function (array $match) use ($root): string {
        $path = $root.'/public/fonts/'.$match[2];
        if (!is_file($path)) {
            fwrite(STDERR, "Missing font public/fonts/{$match[2]}\n");
            exit(1);
        }

        return 'url(data:font/woff2;base64,'.base64_encode((string) file_get_contents($path)).')';
    },
    $css
);

$link = '<link rel="stylesheet" href="assets/mockups.css">';
$script = '<script src="assets/shared.js"></script>';
$logo = 'src="assets/logo.png"';

$shared = is_file(__DIR__.'/src/shared.js') ? trim((string) file_get_contents(__DIR__.'/src/shared.js')) : '';
$logo_data = 'src="data:image/png;base64,'.base64_encode((string) file_get_contents($root.'/public/images/logo.png')).'"';

foreach (glob(__DIR__.'/src/*.html') as $source) {
    $html = (string) file_get_contents($source);

    if (!str_contains($html, $link)) {
        fwrite(STDERR, basename($source)." is missing the stylesheet link: $link\n");
        exit(1);
    }

    $html = str_replace($link, '<style>'.$css.'</style>', $html);
    $html = str_replace($script, '<script>'.$shared.'</script>', $html);
    $html = str_replace($logo, $logo_data, $html);

    file_put_contents(__DIR__.'/'.basename($source), $html);
    echo 'Built design/'.basename($source)."\n";
}
