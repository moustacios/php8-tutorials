<?php

// Convert all JPEGs in the directories to 4 second clips

# Test if FFmpeg is installed
$ffmpeg = '/usr/bin/ffmpeg';
$test  = sprintf('%s -version', $ffmpeg);
exec($test, $output, $ret);
if ($ret !== 0){
    throw new RuntimeException('FFmpeg is not installed.');
}

$clipIndex = 0;

/**
 * @throws Throwable
 */
function createFolderClip(string $folder, string $clipName, string $ffmpeg): string
{
    $command = sprintf(
        "%s -threads 1 -framerate 1 -pattern_type glob -i '%s/*.jpg' -c:v libx264 -pix_fmt yuv420p %s",
        $ffmpeg,
        $folder,
        $clipName
    );

    // Shell process stream redirection
    $stdout = fopen('php://temporary', 'w+');
    $stderr = fopen('php://temporary', 'w+');
    $streams = [
        0 => ['pipe', 'r']
        , 1 => $stdout
        , 2 => $stderr
    ];

    $proc = proc_open($command, $streams, $pipes);
    if (!$proc){
        throw new \RuntimeException('Unable to launch external process');
    }

    do {
        Fiber::suspend(); // suspend the fiber until next fiber yield
        $status = proc_get_status($proc);
    } while ($status['running']);

    // Close proc and streams
    proc_close($proc);
    fclose($stdout);
    fclose($stderr);

    $success = $status['exitcode'] === 0;
    if ($success) {
        return $clipName;
    } else {
        throw new \RuntimeException('Unable to perform conversion');
    }

}

$folders = [
    __DIR__ . '/assets/clip1',
    __DIR__ . '/assets/clip2',
    __DIR__ . '/assets/clip3',
    __DIR__ . '/assets/clip4',
];

# Convert
$start     = microtime(true);
$fiberList = [];

// Launch fibers
foreach ($folders as $folder) {
    $clip  = "clip".++$clipIndex.".mp4"; // clip1.mp4, clip2.mp4, clip3.mp4, clip4.mp4
    $fiber = new Fiber(createFolderClip(...));
    $fiber->start($folder, $clip, $ffmpeg);
    $fiberList[] = $fiber;

    // echo 'Successfully created clip from ' . $folder . ' => ' . $clip . PHP_EOL;
}

// Loop until all fibers are terminated
while ($fiberList) {
    foreach ($fiberList as $idx => $fiber) {
        if ($fiber->isTerminated()){
            $clip = $fiber->getReturn();
            echo 'Successfully created clip => ' . $clip . PHP_EOL;
            unset($fiberList[$idx]);
        } else {
            $fiber->resume();
        }
    }
}

$end = microtime(true);

# Print out the time taken
echo "\n";
echo sizeof($folders) . ' folders processed in ' . round($end - $start, 1) . ' seconds' . PHP_EOL;
