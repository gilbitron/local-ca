<?php

namespace LocalCA;

use Symfony\Component\Process\Process;

class CommandLine
{
    /**
     * Run the given command as the non-root user.
     *
     * @param  string  $command
     * @param  callable $onError
     * @return string
     */
    public function run($command, callable $onError = null)
    {
        return $this->runCommand($command, $onError);
    }

    /**
     * Run the given command.
     *
     * @param  string  $command
     * @param  callable $onError
     * @return string
     */
    public function runAsUser($command, callable $onError = null)
    {
        return $this->runCommand('sudo -u "'.user().'" '.$command, $onError);
    }

    /**
     * Run the given command.
     *
     * @param  string  $command
     * @param  callable $onError
     * @return string
     */
    public function runCommand($command, callable $onError = null)
    {
        $onError = $onError ?: function () {};

        $process = Process::fromShellCommandline($command);

        $processOutput = '';
        $process->setTimeout(null)->run(function ($type, $line) use (&$processOutput) {
            $processOutput .= $line;
        });

        if ($process->getExitCode() > 0) {
            $onError($process->getExitCode(), $processOutput);
        }

        return $processOutput;
    }
}