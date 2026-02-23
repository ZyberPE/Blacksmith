<?php

namespace Blacksmith;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\form\MenuForm;
use pocketmine\form\CustomForm;

class Main extends PluginBase {

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->getLogger()->info("Blacksmith plugin enabled!");
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if($command->getName() === "blacksmith" && $sender instanceof Player){
            $this->openMainMenu($sender);
            return true;
        }
        return false;
    }

    // Main menu GUI
    public function openMainMenu(Player $player): void {
        $form = new MenuForm(
            "§lBlacksmith",
            "Select an option. Each costs §e".$this->getConfig()->get("xp_cost")." XP",
            ["Repair Item", "Rename Item"],
            function(Player $player, ?int $data){
                if($data === null) return;
                if($data === 0){
                    $this->openRepairConfirm($player);
                } elseif($data === 1){
                    $this->openRenameForm($player);
                }
            }
        );
        $player->sendForm($form);
    }

    // Repair confirmation GUI
    public function openRepairConfirm(Player $player): void {
        $xpCost = $this->getConfig()->getInt("xp_cost");
        $xpManager = $player->getXpManager();
        $currentXp = $xpManager->getCurrentXp();

        $item = $player->getInventory()->getItemInHand();
        if($item->isNull()){
            $player->sendMessage($this->getConfig()->get("messages")["no_item"]);
            return;
        }

        $form = new MenuForm(
            "§lRepair Item",
            "XP Required: §e$xpCost\nYour XP: §a$currentXp",
            ["Submit Repair", "Cancel"],
            function(Player $player, ?int $data) use ($item, $xpCost, $xpManager){
                if($data === null || $data === 1) return; // Cancel
                if($xpManager->getCurrentXp() < $xpCost){
                    $player->sendMessage($this->getConfig()->get("messages")["not_enough_xp"]);
                    return;
                }
                $xpManager->addXp(-$xpCost);
                $item->setDamage(0);
                $player->getInventory()->setItemInHand($item);
                $player->sendMessage($this->getConfig()->get("messages")["repaired"]);
            }
        );
        $player->sendForm($form);
    }

    // Rename form with XP info
    public function openRenameForm(Player $player): void {
        $xpCost = $this->getConfig()->getInt("xp_cost");
        $xpManager = $player->getXpManager();
        $currentXp = $xpManager->getCurrentXp();

        $item = $player->getInventory()->getItemInHand();
        if($item->isNull()){
            $player->sendMessage($this->getConfig()->get("messages")["no_item"]);
            return;
        }

        $form = new CustomForm(
            "§lRename Item",
            [
                "xp_info" => [
                    "type" => "label",
                    "text" => "XP Required: §e$xpCost\nYour XP: §a$currentXp"
                ],
                "new_name" => [
                    "type" => "input",
                    "text" => "Enter new name",
                    "placeholder" => $item->getCustomName() ?: $item->getName()
                ]
            ],
            function(Player $player, ?array $data) use ($item, $xpCost, $xpManager){
                if($data === null) return;

                $newName = trim($data["new_name"]);
                if($newName === ""){
                    $player->sendMessage("§cInvalid name!");
                    return;
                }

                if($xpManager->getCurrentXp() < $xpCost){
                    $player->sendMessage($this->getConfig()->get("messages")["not_enough_xp"]);
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
