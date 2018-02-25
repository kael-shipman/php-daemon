<?php
namespace KS;

interface ExecutableConfigInterface
{
    public function getLogLevel() : int;
    public function getLogIdentifier() : string;
    public function reload(): void;
    public function checkConfig(): void;
}

