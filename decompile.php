<?php
$compiledFile = 'storage/framework/views/bc1c558bf6684a692832f0f55ae62788.php';
$content = file_get_contents($compiledFile);

// Remove the compiled headers/comments like /**PATH ... */
$content = preg_replace('/<\?php\s+\/\*\*PATH.*?\*\/\s+\?>/s', '', $content);

// Decompile echo e(...) to {{ ... }}
$content = preg_replace('/<\?php\s+echo\s+e\((.*?)\);\s+\?>/s', '{{ $1 }}', $content);

// Decompile echo ... to {!! ... !!}
$content = preg_replace('/<\?php\s+echo\s+(.*?);\s+\?>/s', '{!! $1 !!}', $content);

// Ensure we don't accidentally leave <?php echo e(...) if there was no semicolon
$content = preg_replace('/<\?php\s+echo\s+e\((.*?)\)\s+\?>/s', '{{ $1 }}', $content);

// Decompile loops and ifs
$content = preg_replace('/<\?php\s+foreach\((.*?)\):\s+\?>/s', '@foreach($1)', $content);
$content = str_replace("<?php endforeach; \$__env->popLoop(); \$loop = \$__env->getLastLoop(); ?>", '@endforeach', $content);

$content = preg_replace('/<\?php\s+if\((.*?)\):\s+\?>/s', '@if($1)', $content);
$content = preg_replace('/<\?php\s+elseif\((.*?)\):\s+\?>/s', '@elseif($1)', $content);
$content = str_replace('<?php else: ?>', '@else', $content);
$content = str_replace('<?php endif; ?>', '@endif', $content);

$content = preg_replace('/<\?php\s+for\((.*?)\):\s+\?>/s', '@for($1)', $content);
$content = str_replace('<?php endfor; ?>', '@endfor', $content);

$content = preg_replace('/<\?php\s+while\((.*?)\):\s+\?>/s', '@while($1)', $content);
$content = str_replace('<?php endwhile; ?>', '@endwhile', $content);

// Includes and sections
$content = preg_replace('/<\?php\s+echo\s+\$__env->make\((.*?)\s*,\s*\\\\Illuminate\\\\Support\\\\Arr::except\(get_defined_vars\(\),\s*\[\'__data\',\s*\'__path\'\]\)\)->render\(\);\s+\?>/s', '@include($1)', $content);
$content = str_replace('<?php $__env->stopSection(); ?>', '@endsection', $content);
$content = preg_replace('/<\?php\s+\$__env->startSection\((.*?)\);\s+\?>/s', '@section($1)', $content);

// Loop arrays (the complex ones)
$content = preg_replace('/<\?php\s+\$__currentLoopData\s+=\s+(.*?);\s+\$__env->addLoop\(\$__currentLoopData\);\s+foreach\(\$__currentLoopData\s+as\s+(.*?)\):\s+\$__env->incrementLoopIndices\(\);\s+\$loop\s+=\s+\$__env->getLastLoop\(\);\s+\?>/s', '@foreach($1 as $2)', $content);

file_put_contents('resources/views/monthly_reports/show.blade.php', $content);
echo "Restored fully.";
