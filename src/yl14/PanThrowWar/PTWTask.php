<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\scheduler\Task;

class PTWTask extends Task {

    private $plugin;
    private $sessionid;

    public function __construct(PanThrowWar $plugin, int $sessionid) {
        $this->plugin = $plugin;
        $this->sessionid = $sessionid;
    }

    public function onRun(int $currentTick) {
        $Session = $this->plugin->getRoomById($sessionid);
    }
}