<?php

namespace Xsirot\Freezed;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\world\Position;
use pocketmine\utils\TextFormat as TF;

class Main extends PluginBase implements Listener
{

    private $playerfreeze = [];
    private $prefix = TF::BOLD . TF::WHITE . "[" . TF::AQUA . "FREEZE" . TF::WHITE . "] " . TF::RESET;

    public function onEnable(): void
    {
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if (!$sender instanceof Player) {
            $sender->sendMessage("Use command in game!");
            return true;
        }
        if ($command->getName() == "freeze") {
            if ($sender->hasPermission("freezed.freezed")) {
                if (isset($args[0])) {
                    $player = $this->getServer()->getPlayerExact($args[0]);
                    if ($player) {
                        if($sender->getName() == $player->getName()){
                            $sender->sendMessage($this->prefix . TF::RED . "Can't use commands on myself");
                            return true;
                        }
                        if (!in_array($player->getName(), $this->playerfreeze)) {
                            array_push($this->playerfreeze, $player->getName());
                            $sender->sendMessage($this->prefix . TF::GREEN . "You have frozen " . TF::YELLOW . $args[0]);
                            $player->sendMessage($this->prefix . TF::GREEN . "You are frozen by ". TF::YELLOW . $sender->getName());
                        } else {
                            $sender->sendMessage($this->prefix . TF::YELLOW . $args[0] . TF::RED . " is already frozen!");
                        }
                    } else {
                        $sender->sendMessage($this->prefix . TF::YELLOW . $args[0] . TF::RED . " not online!");
                    }
                } else {
                    $sender->sendMessage($this->prefix . TF::RED . "/freeze <player>");
                }
            } else {
                $sender->sendMessage($this->prefix . TF::RED . "You don't have permission!");
            }
        }
        if ($command->getName() == "unfreeze") {
            if ($sender->hasPermission("freezed.unfreezed")) {
                if (isset($args[0])) {
                    $player = $this->getServer()->getPlayerExact($args[0]);
                    if ($player) {
                        if (!in_array($player->getName(), $this->playerfreeze)) {
                            $sender->sendMessage($this->prefix . TF::YELLOW . $args[0] . TF::RED . " has not been frozen!");
                        } else {
                            array_splice($this->playerfreeze, array_search($player->getName(), $this->playerfreeze), 1);
                            $sender->sendMessage($this->prefix . TF::GREEN . "Unfrozen " . TF::YELLOW . $args[0]);
                            $player->sendMessage($this->prefix . TF::GREEN . "Unfrozen by ". TF::YELLOW . $sender->getName());
                        }
                    } else {
                        $sender->sendMessage($this->prefix . TF::YELLOW . $args[0] . TF::RED . " not online!");
                    }
                } else {
                    $sender->sendMessage($this->prefix . TF::RED . "/unfreeze <player>");
                }
            } else {
                $sender->sendMessage($this->prefix . TF::RED . "You don't have permission!");
            }
        }
        return true;
    }

    public function checkFreeze(Player $player)
    {
        $pos = $player->getPosition();
        $world = $player->getWorld();
        if (!$player->isOnGround()) {
            $newY = $world->getHighestBlockAt($pos->getFloorX(), $pos->getFloorZ());
            $player->teleport(new Position($pos->getFloorX(), $newY + 1, $pos->getFloorZ(), $world));
        }

        if($player->isUnderwater()){
            $newY = $player->getWorld()->getHighestBlockAt($pos->getFloorX(), $pos->getFloorZ());
            $player->teleport(new Position($pos->getFloorX(), $newY + 1, $pos->getFloorZ(), $world));
        }
    }

    public function onMove(PlayerMoveEvent $event)
    {
        $playerName = $event->getPlayer()->getName();
        if (in_array($playerName, $this->playerfreeze)) {
            $event->cancel();
        }
    }

    public function onHit(EntityDamageByEntityEvent $event)
    {
        $damager = $event->getDamager();
        $player = $event->getEntity();
        if ($player instanceof Player) {
            $playerName = $player->getName();
            if (in_array($playerName, $this->playerfreeze)) {
                $event->cancel();
                if ($damager instanceof Player) {
                    $damager->sendMessage(TF::YELLOW . "You can't hit players while frozen!");
                }
            }
        }
    }

    public function onJoin(PlayerJoinEvent $event)
    {
        $playerName = $event->getPlayer()->getName();
        if (in_array($playerName, $this->playerfreeze)) {
            $this->checkFreeze($event->getPlayer());
        }
    }
}
