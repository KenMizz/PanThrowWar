<?php

declare(strict_types=1);

namespace yl14\PanThrowWar;

use pocketmine\event\Listener;
use pocketmine\event\{
    player\PlayerQuitEvent, player\PlayerDropItemEvent, player\PlayerItemHeldEvent, player\PlayerInteractEvent, entity\EntityArmorChangeEvent, entity\EntityDamageByEntityEvent, block\BlockPlaceEvent, block\BlockBreakEvent
};
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Effect;
use pocketmine\utils\TextFormat as TF;

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
            } else {
                $ev->setCancelled();
            }
        }
    }

    public function onPlayerInteract(PlayerInteractEvent $ev) {
        $player = $ev->getPlayer();
        if($this->plugin->getPlayerInGame($player)) {
            $ev->setCancelled();
        }
    }

    public function onEntityArmorChange(EntityArmorChangeEvent $ev) {
        $entity = $ev->getEntity();
        if($entity instanceof Player) {
            if($this->plugin->getPlayerInGame($entity)) {
                if($entity->getArmorInventory()->getHelmet() == Item::get(Item::MOB_HEAD, 4)) {
                    $ev->setCancelled();
                }
            }
        }
    }

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $ev) {
        $damager = $ev->getDamager();
        $entity = $ev->getEntity();
        if($damager instanceof Player) {
            if($this->plugin->getPlayerInGame($damager)) {
                $ev->setCancelled();
                if($damager->getArmorInventory()->getHelmet() == Item::get(Item::MOB_HEAD, 4)) {
                    if($entity instanceof Player) {
                        if($this->plugin->getPlayerInGame($entity)) {
                            $Session = $this->plugin->getRoomById($this->plugin->getPlayerInGame($damager));
                            if(!$Session->isSpectator($entity)) {
                                $damager->getArmorInventory()->setHelmet(Item::get(Item::AIR));
                                $damager->removeAllEffects();
                                $entity->getArmorInventory()->setHelmet(Item::get(Item::MOB_HEAD, 4));
                                $entity->removeAllEffects();
                                $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 300 * 300, 1, false));
                                $this->plugin->getServer()->broadcastMessage($this->plugin->prefix."{$damager->getName()}".TF::GREEN."把锅传给了".TF::WHITE.$entity->getName(), $Session->getPlayers());
                            }
                        }
                    }
                }
            }
        }
    }
}

