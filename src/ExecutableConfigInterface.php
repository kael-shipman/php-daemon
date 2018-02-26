<?php
namespace KS;

interface ExecutableConfigInterface
{
    public function getLogIdentifier() : string;
    public function getLogLevel() : int;
}

