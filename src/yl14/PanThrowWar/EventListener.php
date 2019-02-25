<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\event\Listener;
use pocketmine\event\{
    player\PlayerQuitEvent, player\PlayerDropItemEvent, player\PlayerItemHeldEvent, entity\EntityArmorChangeEvent, entity\EntityDamageByEntityEvent, block\BlockPlaceEvent, block\BlockBreakEvent
};
use pocketmine\item\Item;

class EventListener implements Listener {

    private $plugin;
    private $onQuit = [];

    public function __construct(PanThrowWar $plugin) {
        $this->plugin = $plugin;
    }

    public function onPlayerQuit(PlayerQuitEvent $ev) {
        $player = $ev->getPlayer();
        if($this->plugin->getPlayerInGame($player)) {
            $this->plugin->leaveRoom([$player], $this->plugin->getPlayerInGame($player), 1);
            unset($this->onQuit[$player->getName()]);
        }
    }

    public function onPlayerDropItem(PlayerDropItemEvent $ev) {
        $player = $ev->getPlayer();
        if($this->plugin->getPlayerInGame($player)) {
            $ev->setCancelled();
        }
    }

    public function onBlockPlace(BlockPlaceEvent $ev) {
        $player = $ev->getPlayer();
        if($this->plugin->getPlayerInGame($player)) {
            $ev->setCancelled();
        }
    }

    public function onBlockBreak(BlockBreakEvent $ev) {
        $player = $ev->getPlayer();
        if($this->plugin->getPlayerInGame($player)) {
            $ev->setCancelled();
        }
    }

    public function onPlayerItemHeld(PlayerItemHeldEvent $ev) {
        $Item = $ev->getItem();
        $player = $ev->getPlayer();
        if($this->plugin->getPlayerInGame($player)) {
            if($Item->getCustomName() == "离开房间") {
                if(!isset($this->onQuit[$player->getName()])) {
                    $ev->setCancelled();
                    $this->onQuit[$player->getName()] = $player->getName();
                    $player->sendMessage("再切换一次即可退出房间");
                } else {
                    unset($this->onQuit[$player->getName()]);
                    $this->plugin->leaveRoom([$player], $this->plugin->getPlayerInGame($player), 1);
                }
            }
        }
    }
}

