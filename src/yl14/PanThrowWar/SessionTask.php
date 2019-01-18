<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\scheduler\Task;

class SessionTask extends Task {

    private $plugin;
    
    public function __construct(PanThrowWar $plugin) {
        $this->plugin = $plugin;
        $this->init();
    }

    private function init() {
        
    }

    public function onRun(int $tick) {

    }
}