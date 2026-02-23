<?php

namespace Blacksmith;

use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\form\Form;
use pocketmine\item\Item;

class Main extends PluginBase {

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $this->getLogger()->info("Blacksmith enabled!");
    }

    public function onCommand(Player $player, string $commandLabel, array $args): bool {
        if(strtolower($commandLabel) === "blacksmith"){
            $this->openMainMenu($player);
            return true;
        }
        return false;
    }

    private function openMainMenu(Player $player): void {
        $form = new \pocketmine\form\CustomForm(function(Player $player, array $data = null){
            if($data === null) return;
            $choice = $data[0] ?? -1;
            if($choice === 0){
                $this->openRepairMenu($player);
            } elseif($choice === 1){
                $this->openRenameMenu($player);
            }
        });

        $form->setTitle("§6Blacksmith");
        $form->addDropdown("Select an option", ["Repair Item", "Rename Item"]);
        $player->sendForm($form);
    }

    private function openRepairMenu(Player $player): void {
        $xpCost = $this->getConfig()->get("xp_cost", 2);
        $playerXp = $player->getXpManager()->getXpLevel(); // API5 way
        $item = $player->getInventory()->getItemInHand();

        if($item->isNull()){
            $player->sendMessage($this->getConfig()->get("messages.no_item"));
            return;
        }

        $form = new \pocketmine\form\ModalForm(function(Player $player, ?bool $data){
            if($data){
                if($player->getXpManager()->getXpLevel() >= $this->getConfig()->get("xp_cost")){
                    $item = $player->getInventory()->getItemInHand();
                    $item->setDamage(0);
                    $player->getInventory()->setItemInHand($item);
                    $player->getXpManager()->subtractXpLevel($this->getConfig()->get("xp_cost"));
                    $player->sendMessage($this->getConfig()->get("messages.repaired"));
                } else {
                    $player->sendMessage($this->getConfig()->get("messages.not_enough_xp"));
                }
            }
        });

        $form->setTitle("§6Repair Item");
        $form->setContent("Repairing this item costs $xpCost XP.\nYou have: $playerXp XP");
        $form->setButton1("Repair");
        $form->setButton2("Cancel");
        $player->sendForm($form);
    }

    private function openRenameMenu(Player $player): void {
        $xpCost = $this->getConfig()->get("xp_cost", 2);
        $playerXp = $player->getXpManager()->getXpLevel();
        $item = $player->getInventory()->getItemInHand();

        if($item->isNull()){
            $player->sendMessage($this->getConfig()->get("messages.no_item"));
            return;
        }

        $form = new \pocketmine\form\CustomForm(function(Player $player, array $data = null){
            if($data === null) return;
            $newName = $data[0];
            if(empty($newName)) return;

            if($player->getXpManager()->getXpLevel() >= $this->getConfig()->get("xp_cost")){
                $item = $player->getInventory()->getItemInHand();
                $item->setCustomName($newName);
                $player->getInventory()->setItemInHand($item);
                $player->getXpManager()->subtractXpLevel($this->getConfig()->get("xp_cost"));
                $player->sendMessage($this->getConfig()->get("messages.renamed"));
            } else {
                $player->sendMessage($this->getConfig()->get("messages.not_enough_xp"));
            }
        });

        $form->setTitle("§6Rename Item");
        $form->addInput("Type new name", $item->getName());
        $player->sendForm($form);
    }
}
