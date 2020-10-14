<?php

declare(strict_types=1);

namespace besher\stafftools;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\Listener;
use pocketmine\plugin\PluginBase;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\CommandExecutor;
use pocketmine\command\ConsoleCommandSender;
//BLOCK EVENTS
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
//INVENTORY EVENTS
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\item\Item;
//ENTITY EVENTS
use pocketmine\event\entity\EntityInventoryChangeEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Entity;
//PLAYER EVENTS
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
//FORM
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\Form;
//EFFECTS
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
//ENTERYS
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\event\server\DataPacketReceiveEvent;
//CUSTOM
use onebone\economyapi\EconomyAPI;
use besher\stafftools\FirstTask;

class Main extends PluginBase implements Listener
{
	public $ScreenS = [];
	public $inventory = [];
	public $nametagg = [];
	public $staffmode = [];
	public $playerlist = [];
	public $vanish = [];

	public function onEnable()
	{
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function Backup(Player $player)
	{
		$contents = $player->getInventory()->getContents();
		$items = [];
		foreach ($contents as $slot => $item) {
			$items[$slot] = [$item->getId(), $item->getDamage(), $item->getCount()];
		}
		$this->inventory[$player->getName()] = $items;
		$player->getInventory()->clearAll();
	}

	public function Restore(Player $player)
	{
		$player->removeAllEffects();
		$player->setMaxHealth(20);
		$player->setHealth(20);
		$player->setFood(20);
		$cloud = $this->inventory[$player->getName()];
		$player->getInventory()->clearAll();
		foreach ($cloud as $slot => $item) {
			$player->getInventory()->setItem($slot, Item::get($item[0], $item[1], $item[2]));
		}
		unset($this->inventory[$player->getName()]);
		return true;
	}

	public function setTag(Player $player)
	{
		$name = $player->getName();
		$nameTag = $player->getNameTag();
		$this->nametagg[$name] = $nameTag;
		$player->setNameTag("§8§l[§r§6§3Frozen§r§8§l]§r $nameTag");
	}

	public function stafftag(Player $player)
	{
		$name = $player->getName();
		$nameTag = $player->getNameTag();
		$this->nametagg[$name] = $nameTag;
		$player->setNameTag("§8§l[§r§6StaffMode§r§8§l]§r $nameTag");
	}

	public function unsetstafftag(Player $player)
	{
		$name = $player->getName();
		$nameTag = $this->nametagg[$name];
		$player->setNameTag("$nameTag");
	}

	public function setstaffmode($name)
	{
		$this->staffmode[$name] = $name;
	}

	public function setvanish($name)
	{
		$this->vanish[$name] = $name;
	}

	public function isvanish($name)
	{
		return in_array($name, $this->vanish);
	}

	public function isstaffmode($name)
	{
		return in_array($name, $this->staffmode);
	}

	public function quitstaffmode($name)
	{
		if (!$this->isstaffmode($name)) {
			return;
		}
		unset($this->staffmode[$name]);
	}

	public function quitvanish($name)
	{
		if (!$this->isvanish($name)) {
			return;
		}
		unset($this->vanish[$name]);
	}

	public function unsetTag(Player $player)
	{
		$name = $player->getName();
		$nameTag = $this->nametagg[$name];
		$player->setNameTag("$nameTag");
	}

	public function setScreenS($name)
	{
		$this->ScreenS[$name] = $name;
	}

	public function isScreenS($name)
	{
		return in_array($name, $this->ScreenS);
	}

	public function quitScreenS($name)
	{
		if (!$this->isScreenS($name)) {
			return;
		}
		unset($this->ScreenS[$name]);
	}

	public function freeze(EntityDamageByEntityEvent $e)
	{
		$entity = $e->getEntity();
		$damager = $e->getDamager();
		if ($damager instanceof Player && $entity instanceof Player) {
			$freeze = $damager->getInventory()->getItemInHand();
			if ($freeze->getCustomName() == "§r§fFreeze") {
				if($damager->hasPermission("freeze.player")){
				if (!$this->isScreenS($entity->getName())) {
					$e->setCancelled(true);
					$this->setScreenS($entity->getName());
					$entity->sendMessage("You have been frozen");
					$entity->addTitle("§cYOU HAVE BEEN FROZEN", "By " . $damager->getName(), 1, 20, 1);
					$entity->setImmobile(true);
					$this->Backup($entity);
					$this->setTag($entity);
					$this->setScreenS($entity->getName());
				} else {
					$this->unsetTag($entity);
					$this->quitScreenS($entity->getName());
					$this->Restore($entity);
					$entity->setImmobile(false);
					$entity->sendMessage("You have been unfrozen");
					$entity->addTitle("You have been", "unfrozen!", 1, 20, 1);

					$e->setCancelled(true);
				}
			}
		}
		}
	}

	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args): bool
	{
		switch ($cmd->getName()) {
			case "sm":
				if (!$this->isstaffmode($sender->getName())) {
					$this->setstaffmode($sender->getName());
					$sender->sendMessage("StaffMode enabled!");
					$sender->setGamemode(1);
					$this->stafftag($sender);
					$this->Backup($sender);
					$freeze = Item::get(79, 0, 1);
					$freeze->setCustomName("§r§fFreeze");
					$freeze->setLore(["Hit a player to freeze them"]);
					$sender->getInventory()->setItem(0, $freeze);
					$teleport = Item::get(345, 0, 1);
					$teleport->setCustomName("§r§fTeleport to players");
					$teleport->setLore(["Click the item to open menu"]);
					$sender->getInventory()->setItem(4, $teleport);
					$vanish = Item::get(353, 0, 1);
					$vanish->setCustomName("§r§fVanish");
					$vanish->setLore(["Click the item to vanish"]);
					$sender->getInventory()->setItem(2, $vanish);
					$nick = Item::get(298, 0, 1);
					$nick->setCustomName("§r§fNick");
					$nick->setLore(["Click the item to open nick menu"]);
					$sender->getInventory()->setItem(8, $nick);
					$info = Item::get(339, 0, 1);
					$info->setCustomName("§r§fInfo");
					$info->setLore(["Hit a player to see his info"]);
					$sender->getInventory()->setItem(6, $info);
					$sender->addTitle("StaffMode", "Enabled", 1, 20, 1);
				} else {
					$this->quitstaffmode($sender->getName());
					$sender->setGamemode(0);
					$this->unsetstafftag($sender);
					$sender->getInventory()->clearAll();
					$this->Restore($sender);
					$sender->sendMessage("StaffMode Disabled!");
					$sender->addTitle("StaffMode", "Disabled", 1, 20, 1);
					break;
				}
		}
		return true;
	}
	
	public function info(EntityDamageByEntityEvent $e)
{
	$entity = $e->getEntity();
	$damager = $e->getDamager();
	if ($damager instanceof Player && $entity instanceof Player) {
		$info = $damager->getInventory()->getItemInHand();
		if ($info->getCustomName() == "§r§fInfo") {
			if ($damager->hasPermission("infoabout.player")) {
				$e->setCancelled(true);
				$playerip = $entity->getAddress();
				$dammess = $damager->getPlayer();
				$health = $entity->getHealth();
				$x = $entity->getX();
				$y = $entity->getY();
				$z = $entity->getZ();
				$uuid = $entity->getUniqueId()->toString();
				$money = EconomyAPI::getInstance()->mymoney($entity);
				$dammess->sendMessage("Player's name: " . $entity->getName());
				$dammess->sendMessage("Player's ip: " . $playerip);
				$dammess->sendMessage("Player's health: $health");
				$dammess->sendMessage("Player's location: X: " . round($x) . " " . "Y: " . round($y) . " " . "Z: " . round($z));
				$dammess->sendMessage("Player's money: $money");
				$dammess->sendMessage("Player's UUID: $uuid");
			}
		}
	}
}

	public function dropItem(PlayerDropItemEvent $event)
	{
		$player = $event->getPlayer();
		$item = $event->getItem();
		if ($this->isScreenS($player->getName())) {
			$event->setCancelled(true);
		}
	}

	public function onHunger(PlayerExhaustEvent $event)
	{
		$player = $event->getPlayer();

		if ($this->isScreenS($player->getName())) {
			$event->setCancelled(true);
		}
	}

	public function placeblock(BlockPlaceEvent $event)
	{
		$player = $event->getPlayer();
		if ($this->isScreenS($player->getName())) {
			$event->setCancelled(true);
		}
	}

	public function onInventoryy(InventoryOpenEvent $event)
	{
		$player = $event->getPlayer();
		if ($this->isScreenS($player->getName())) {
			$inv = $event->getInventory();
			$inv->close($event->getPlayer());
			$event->setCancelled(true);
		}
	}

	public function onAttack(EntityDamageByEntityEvent $event): void
	{
		$damager = $event->getDamager();
		$entity = $event->getEntity();
		if ($damager instanceof Player) {
			if ($this->isScreenS($entity->getName())) {
				$event->setCancelled(true);
			}
		}
	}

	public function onChat(PlayerChatEvent $event)
	{
		$player = $event->getPlayer();
		$message = $event->getMessage();
		if ($this->isScreenS($player->getName())) {
			$event->setCancelled(true);
		}
	}

	public function onDamage(EntityDamageEvent $event)
	{
		$player = $event->getEntity();
		if ($this->isScreenS($player->getName())) {
			$event->setCancelled(true);
		}
	}

	public function onBreak(BlockBreakEvent $event)
	{
		$player = $event->getPlayer();
		if ($this->isScreenS($player->getName())) {
			$event->setCancelled(true);
		}
	}


	public static $vanish1 = [
		"§r§fVanish" => "§r§fVanish",
	];

	public function vanishe(PlayerInteractEvent $event)
	{
		$player = $event->getPlayer();
		$item = $event->getItem();

		if ($item->getCustomName() == self::$vanish1['§r§fVanish']) {
			if ($player->hasPermission("vanish.staffmode")) {
				foreach ($this->getServer()->getOnlinePlayers() as $onlinePlayer) {
					if ($this->isVanish($player->getName())) {
						$this->quitvanish($player->getName());
						$onlinePlayer->showPlayer($player);
						$player->sendMessage("unvanished");
					} else {
						$this->setvanish($player->getName());
						$onlinePlayer->hidePlayer($player);
						$player->sendPopup("Vanished");
					}
				}
			}
		}
	}
	public function nicklol(Player $player){

    $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");

    $form = $api->createCustomForm(function (Player $player, array $data = null){

      if($data === null){

        return true;

      }

      if($data[0] == "reset"){

        $this->ResetNick($player); 

      }

      $player->setDisplayName($data[0]);

      $player->setNameTag($data[0]);

      $player->sendMessage("§aYour nickname was changed to §f" . $data[0]);

    });

    $form->setTitle("§l§aCHANGE NICK");

    

    $form->addInput("Enter your New Name!", "New Nick..");

    

    $form->sendToPlayer($player);

    }

	public function Teleport($sender){

		$form = new CustomForm(function (Player $sender, $data){

			if($data !== null){



				$this->getServer()->getCommandMap()->dispatch($sender, "tp $data[0]");



			}

		});

		$form->setTitle("§l§aTELEPORT");

		$form->addInput("Player Name");



		$form->sendToPlayer($sender);



	}

	public static $nick = [
		"§r§fNick" => "§r§fNick",
	];

	public function nick123(PlayerInteractEvent $event)
	{
		$player = $event->getPlayer();
		$item = $event->getItem();

		if ($item->getCustomName() == self::$nick['§r§fNick']) {
			if($player->hasPermission("nick.set")){
				$this->Nick333($player);
			}
		}
	}

	public function Nick333($player){

    $api = $this->getServer()->getPluginManager()->getPlugin("FormAPI");

    $form = $api->createSimpleForm(function (Player $player, int $data = null){

      $result = $data;

      if($result === null){

        return true;

      }

      switch($result){

          case 0:

          

            $this->nicklol($player);

          

          break;

          

          case 1:

          

            $this->ResetNick($player);

          

          break;

          

          case 2:

          

          break;

         

      }

    });

    $form->setTitle("§a§lCHANGE NICK");

    $form->addButton("§eChange Nick\n§7§oTap To Change Nick", 0, "textures/items/name_tag");

    $form->addButton("§cReset Nick\n§7§oTap To Reset Your Nick", 0, "textures/ui/refresh_light");

    $form->addButton("§l§cBACK\n§7§oTap To Back",0,"textures/blocks/barrier");

    $form->sendToPlayer($player);

    return true;

  }

	private function ResetNick(Player $player){

  	 $player->setDisplayName($player->getName());

  	 $player->setNameTag($player->getName());

     $player->sendMessage("§aSucces! §fYour NickName Has Been Changed");

    }

	public static $teleporter = [
		"§r§fTeleport to players" => "§r§fTeleport to players",
	];

	public function teleporter123(PlayerInteractEvent $event)
	{
		$player = $event->getPlayer();
		$item = $event->getItem();

		if ($item->getCustomName() == self::$teleporter['§r§fTeleport to players']) {
			if($player->hasPermission("teleport.players")){
				$this->Teleport($player);
			}
			}
		}
	}