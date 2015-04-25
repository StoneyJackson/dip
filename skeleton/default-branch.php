<?php
$local_mirror = implode(DIRECTORY_SEPARATOR, array(
    __DIR__,
    '.dip',
    'local.git'
));
exec('git --git-dir="'.$local_mirror.'" branch 2>&1', $out, $exit);
if ($exit !== 0) {
    error_log($out);
    exit(1);
}
foreach ($out as $line) {
    if (preg_match('/^\s*\*\s*(\S+)/', $line, $matches)) {
        $branch = $matches[1];
        header("Location: $branch");
        exit(0);
    }
}
