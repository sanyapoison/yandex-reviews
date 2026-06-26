<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class YandexParserService
{
    public function parse(string $url): array
    {
        $scriptPath = config('parser.script_path', base_path('scripts/parser.js'));

        $process = new Process(['node', $scriptPath, $url]);
        $process->setTimeout(30);
        $process->run();

        if (!$process->isSuccessful()) {
            Log::error("Parser failed: " . $process->getErrorOutput());
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();

        $data = json_decode($output, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            Log::error("Parser returned invalid JSON", ['output' => $output]);
            throw new \RuntimeException("Parser returned invalid JSON");
        }

        return $data;
    }
}
