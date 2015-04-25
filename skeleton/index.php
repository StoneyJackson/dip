<?php
echo "<ul>\n";
foreach (scandir(__DIR__) as $file) {
    if (is_dir(__DIR__.DIRECTORY_SEPARATOR.$file)
        and $file != '.'
        and $file != '..'
        and $file != '.dip')
    {
        echo "<li><a href=\"$file\">$file</a></li>\n";
    }
}
echo "</ul>\n";
