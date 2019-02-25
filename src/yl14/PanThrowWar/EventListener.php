<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\event\Listener;
use pocketmine\event\{
    player\PlayerQuitEvent, player\PlayerDropItemEvent, player\PlayerItemHeldEvent, entity\EntityArmorChangeEvent, entity\EntityDamageByEntityEvent, block\BlockPlaceEvent, block\BlockBreakEvent
};

class EventListener implements Listener {

    private $plugin;

    public function __construct(PanThrowWar $plugin) {
        $this->plugin = $plugin;
    }

    
}

