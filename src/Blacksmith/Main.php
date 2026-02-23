<?php

namespace Blacksmith;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\form\MenuForm;
use pocketmine\form\CustomForm;
use pocketmine\world\Position;

class Main extends PluginBase {

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->getLogger()->info("Blacksmith plugin enabled!");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if($command->getName() === "blacksmith" && $sender instanceof Player){
            $this->openShop($sender);
            return true;
        }
        return false;
    }

    public function openShop(Player $player): void {
        $form = new MenuForm(
            "§lBlacksmith",
            "Select an option. Each costs §e" . $this->getConfig()->get("xp_cost") . " XP",
            [
                "Repair Item",
                "Rename Item"
            ],
            function(Player $player, ?int $data){
                if($data === null) return;
                if($data === 0){
                    $this->repairItem($player);
                } elseif($data === 1){
                    $this->renameItem($player);
                }
            }
        );
        $player->sendForm($form);
    }

    public function repairItem(Player $player): void {
        $xpCost = $this->getConfig()->getInt("xp_cost");
        $xpManager = $player->getXpManager();
        $item = $player->getInventory()->getItemInHand();

        if($item->isNull()){
            $player->sendMessage($this->getConfig()->get("messages")["no_item"]);
            return;
        }

        if($xpManager->getCurrentXp() < $xpCost){
            $player->sendMessage($this->getConfig()->get("messages")["not_enough_xp"]);
            return;
        }

        $xpManager->addXp(-$xpCost);
        $item->setDamage(0);
        $player->getInventory()->setItemInHand($item);
        $player->sendMessage($this->getConfig()->get("messages")["repaired"]);
    }

    public function renameItem(Player $player): void {
        $xpCost = $this->getConfig()->getInt("xp_cost");
        $xpManager = $player->getXpManager();
        $item = $player->getInventory()->getItemInHand();

        if($item->isNull()){
            $player->sendMessage($this->getConfig()->get("messages")["no_item"]);
            return;
        }

        if($xpManager->getCurrentXp() < $xpCost){
            $player->sendMessage($this->getConfig()->get("messages")["not_enough_xp"]);
            return;
        }

        $form = new CustomForm(
            "Rename Item",
            [
                "name" => [
                    "type" => "input",
                    "text" => "Enter new name",
                    "placeholder" => $item->getCustomName() ?: $item->getName()
                ]
            ],
            function(Player $player, ?array $data) use ($item, $xpCost, $xpManager){
                if($data === null) return;
                $newName = trim($data["name"]);
                if($newName === ""){
                    $player->sendMessage("§cInvalid name!");
                    return;
                }
                $xpManager->addXp(-$xpCost);
                $item->setCustomName($newName);
                $player->getInventory()->setItemInHand($item);
                $player->sendMessage($this->getConfig()->get("messages")["renamed"]);
            }
        );
        $player->sendForm($form);
    }
}
