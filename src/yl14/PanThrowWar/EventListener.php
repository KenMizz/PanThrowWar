<?php

namespace yl14\PanThrowWar;

use pocketmine\event\{
    Listener, player\PlayerJoinEvent, player\PlayerQuitEvent, player\PlayerDeathEvent, player\PlayerDropItemEvent, entity\EntityArmorChangeEvent, block\BlockBreakEvent, block\BlockPlaceEvent
};

class EventListener implements Listener {

    private $plugin;

    public function __construct(PanThrowWar $plugin) {
        $this->plugin = $plugin;
    }

    
}