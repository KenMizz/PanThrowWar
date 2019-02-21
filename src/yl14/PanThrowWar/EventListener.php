<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\event\Listener;

class EventListener implements Listener {

    private $plugin;

    public function __construct(PanThrowWar $plugin) {
        $this->plugin = $plugin;
    }

    
}

