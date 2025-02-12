<?php
declare(strict_types=1);

namespace customiesdevs\customies;

use AllowDynamicProperties;
use customiesdevs\customies\block\CustomiesBlockFactory;
use customiesdevs\customies\item\CustomiesItemFactory;
use minicore\CustomPlayer;
use pocketmine\event\Listener;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\network\mcpe\protocol\ItemRegistryPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\types\BlockPaletteEntry;
use pocketmine\network\mcpe\protocol\types\Experiments;
use pocketmine\network\mcpe\protocol\types\ItemTypeEntry;
use function array_merge;
use function count;

#[AllowDynamicProperties]
final class CustomiesListener implements Listener {

	/** @var ItemTypeEntry[] */
	private array $cachedItemTable = [];
	/** @var BlockPaletteEntry[] */
	private array $cachedBlockPalette = [];
	private Experiments $experiments;

	public function __construct() {
		$this->experiments = new Experiments([
			// "data_driven_items" is required for custom blocks to render in-game. With this disabled, they will be
			// shown as the UPDATE texture block.
			"data_driven_items" => true,
		], true);
	}

	public function onDataPacketSend(DataPacketSendEvent $event): void {
		foreach($event->getPackets() as $packet){
			if($packet instanceof ItemRegistryPacket) {
				// ItemComponentPacket needs to be sent after the BiomeDefinitionListPacket.
				if($this->cachedItemTable === []) {
					// Wait for the data to be needed before it is actually cached. Allows for all blocks and items to be
					// registered before they are cached for the rest of the runtime.
					$this->cachedItemTable = CustomiesItemFactory::getInstance()->getItemTableEntries();
				}
				foreach($event->getTargets() as $session){
                    $player = $session->getPlayer();
                    if ($player instanceof CustomPlayer && $player->isTextureResolutionSet()) {
                        //ask for custom component packet
                        $itemComponents = $player->resyncTextureResolution();
                    } else $itemComponents = $this->cachedItemTable;

                    (function() use ($itemComponents) : void{
                        $this->entries = array_merge($this->entries, $itemComponents);
                    })->call($packet);
				}
			} elseif($packet instanceof StartGamePacket) {
				if(count($this->cachedItemTable) === 0) {
					// Wait for the data to be needed before it is actually cached. Allows for all blocks and items to be
					// registered before they are cached for the rest of the runtime.
					$this->cachedBlockPalette = CustomiesBlockFactory::getInstance()->getBlockPaletteEntries();
				}
				$packet->levelSettings->experiments = $this->experiments;
				$packet->blockPalette = $this->cachedBlockPalette;
			} elseif($packet instanceof ResourcePackStackPacket) {
				$packet->experiments = $this->experiments;
			}
		}
	}
}
