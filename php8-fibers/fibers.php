<?php


function createFolderClip(string $folder, string $clipName, string $ffmpeg): void
{
    $clip    = $clipName.".mp4";
    $command = sprintf("%s -threads 1 -framerate 1 -pattern_type glob -i '%s/*.jpg' -c:v libx264 -pix_fmt yuv420p %s", $ffmpeg, $folder, $clip);
    exec($command, $output, $ret);
    if ($ret !== 0) {
        throw new RuntimeException('Failed to create clip.');
    }
    echo 'Successfully created clip from ' . $folder . ' => ' . $clip . PHP_EOL;

}
