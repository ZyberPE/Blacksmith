<?php

namespace Blacksmith;

use pocketmine\plugin\PluginBase;
use pocketmine\player\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\world\sound\XpCollectSound;
use pocketmine\utils\Config;
use pocketmine\form\Form;
use pocketmine\form\CustomForm;

class Main extends PluginBase {

    private Config $config;

    public function onEnable(): void {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML, $this->getConfig()->getAll());
    }

    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if(!$sender instanceof Player){
            $sender->sendMessage("This command can only be used in-game!");
            return true;
        }

        if(strtolower($command->getName()) === "blacksmith"){
            $this->openMainMenu($sender);
            return true;
        }

        return false;
    }

    private function openMainMenu(Player $player): void {
        $form = new CustomForm(function(Player $player, $data){
            if($data === null) return;

            switch($data[0]){
                case "Repair Item":
                    $this->openRepairMenu($player);
                    break;
                case "Rename Item":
                    $this->openRenameMenu($player);
                    break;
            }
        });

        $form->setTitle("§6Blacksmith");
        $form->addDropdown("Select an option", ["Repair Item", "Rename Item"]);
        $player->sendForm($form);
    }

    private function openRepairMenu(Player $player): void {
        $item = $player->getInventory()->getItemInHand();
        if($item->isNull()){
            $player->sendMessage("§cYou must hold an item to repair!");
            return;
        }

        $xpCost = $this->config->get("repair")["xp-cost"];
        $playerXp = $player->getCurrentTotalXp();

        $form = new CustomForm(function(Player $player, $data) use ($item, $xpCost){
            if($data === null) return;

            if($player->getCurrentTotalXp() < $xpCost){
                $player->sendMessage($this->config->get("repair")["not-enough-xp"]);
                return;
            }

            $player->subtractXp($xpCost);
            $item->setDamage(0); // Repair
            $player->getInventory()->setItemInHand($item);
            $player->sendMessage($this->config->get("repair")["success-message"]);
            $player->getWorld()->addSound($player->getPosition(), new XpCollectSound());
        });

        $form->setTitle("§6Repair Item");
        $form->addLabel("XP Cost: $xpCost\nYour XP: $playerXp");
        $form->addToggle("Submit Repair");
        $player->sendForm($form);
    }

    private function openRenameMenu(Player $player): void {
        $item = $player->getInventory()->getItemInHand();
        if($item->isNull()){
            $player->sendMessage("§cYou must hold an item to rename!");
            return;
        }

        $xpCost = $this->config->get("rename")["xp-cost"];
        $playerXp = $player->getCurrentTotalXp();

        $form = new CustomForm(function(Player $player, $data) use ($item, $xpCost){
            if($data === null) return;

            $newName = $data[0];

            if($player->getCurrentTotalXp() < $xpCost){
                $player->sendMessage($this->config->get("rename")["not-enough-xp"]);
                return;
            }

            $player->subtractXp($xpCost);
            $item->setCustomName($newName === "" ? $this->config->get("rename")["default-name"] : $newName);
            $player->getInventory()->setItemInHand($item);
            $player->sendMessage($this->config->get("rename")["success-message"]);
            $player->getWorld()->addSound($player->getPosition(), new XpCollectSound());
        });

        $form->setTitle("§6Rename Item");
        $form->addInput("Enter new item name", $this->config->get("rename")["default-name"]);
        $form->addLabel("XP Cost: $xpCost\nYour XP: $playerXp");
        $player->sendForm($form);
    }
}
