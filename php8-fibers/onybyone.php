<?php

// Convert all JPEGs in the directories to 4 second clips

# Test if FFmpeg is installed
$ffmpeg = '/usr/bin/ffmpeg';
$test  = sprintf('%s -version', $ffmpeg);
exec($test, $output, $ret);
if ($ret !== 0){
    throw new RuntimeException('FFmpeg is not installed.');
}

$clipIndex      = 0;
$convertCommand = "%s -threads 1 -framerate 1 -pattern_type glob -i '%s/*.jpg' -c:v libx264 -pix_fmt yuv420p %s";

$folders = [
    __DIR__ . '/assets/clip1',
    __DIR__ . '/assets/clip2',
    __DIR__ . '/assets/clip3',
    __DIR__ . '/assets/clip4',
];

# Convert
$start = microtime(true);
foreach ($folders as $folder) {
    $clip    = "clip".++$clipIndex.".mp4"; // clip1.mp4, clip2.mp4, clip3.mp4, clip4.mp4
    $command = sprintf($convertCommand, $ffmpeg, $folder, $clip);

    exec($command, $output, $ret);

    if ($ret !== 0) {
        throw new RuntimeException('Failed to create clip.');
    }
    echo 'Successfully created clip from ' . $folder . ' => ' . $clip . PHP_EOL;
}

$end = microtime(true);

# Print out the time taken
echo "\n";
echo sizeof($folders) . ' folders processed in ' . round($end - $start, 1) . ' seconds' . PHP_EOL;
