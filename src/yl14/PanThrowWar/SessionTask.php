<?php

namespace yl14\PanThrowWar;

use pocketmine\scheduler\Task;

class SessionTask extends Task {

    private $plugin;

    public function __construct(PanThrowWar $plugin) {$this->plugin = $plugin;}

    
}