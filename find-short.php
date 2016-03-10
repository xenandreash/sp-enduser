<?php
/*
 * Note: This script will directly modify the .php files in the given directory.
 * It is assumed that the code is under version control, so you can easily review
 * the changes using `git diff` or similar.
 */
function usageError() {
    die("Usage: php portVarKeywords.php dir/\n");
}
if ($argc !== 2) {
    usageError();
}
$dir = $argv[1];
if (!is_dir($dir)) {
    echo "\"$dir\" is not a directory.\n";
    usageError();
}
$it = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($dir),
    RecursiveIteratorIterator::LEAVES_ONLY
);
foreach ($it as $file) {
    if (!preg_match('/\.php$/', $file)) {
        continue;
    }
    $code = file_get_contents($file);
    $tokens = token_get_all($code);
	var_dump($tokens);
	die();
    //$tokens = portVarKeywords($tokens);
    //$code = tokensToCode($tokens);
    //file_put_contents($file, $code);
}
function portVarKeywords(array $tokens) {
    foreach ($tokens as $i => &$token) {
        if ($token[0] === T_VAR) {
            $token[1] = 'public';
        }
    }
    return $tokens;
}
function tokensToCode(array $tokens) {
    $code = '';
    foreach ($tokens as $token) {
        if (is_array($token)) {
            $code .= $token[1];
        } else {
            $code .= $token;
        }
    }
    return $code;
}
