<?php

declare(strict_types=1);

use pocketmine\plugin\PluginBase;
use pocketmine\utils\{TextFormat as TF, Config};
use pocketmine\command\{Command, CommandSender};

class PanThrowWar extends PluginBase {

    const VERSION = '1.0.0';
    const LABEL = 'Beta';

    private static $instance;

    private $Sessions = [];
    private $InGame = [];

    public function onEnable() {
        $this->getLogger()->notice(TF::YELLOW."丢锅大战已启用，初始化插件中...");
        $this->initPlugin();
    }

    public function onLoad() {
        
    }
}