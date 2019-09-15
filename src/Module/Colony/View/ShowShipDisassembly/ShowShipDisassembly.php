<?php

declare(strict_types=1);

namespace Stu\Module\Colony\View\ShowShipDisassembly;

use Ship;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Module\Colony\Lib\ColonyLoaderInterface;
use Stu\Module\Colony\View\ShowColony\ShowColony;
use Stu\Orm\Repository\PlanetFieldRepositoryInterface;
use Stu\Orm\Repository\ShipRumpBuildingFunctionRepositoryInterface;

final class ShowShipDisassembly implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_SHIP_DISASSEMBLY';

    private $colonyLoader;

    private $showShipDisassemblyRequest;

    private $shipRumpBuildingFunctionRepository;

    private $planetFieldRepository;

    public function __construct(
        ColonyLoaderInterface $colonyLoader,
        ShowShipDisassemblyRequestInterface $showShipDisassemblyRequest,
        ShipRumpBuildingFunctionRepositoryInterface $shipRumpBuildingFunctionRepository,
        PlanetFieldRepositoryInterface $planetFieldRepository
    ) {
        $this->colonyLoader = $colonyLoader;
        $this->showShipDisassemblyRequest = $showShipDisassemblyRequest;
        $this->shipRumpBuildingFunctionRepository = $shipRumpBuildingFunctionRepository;
        $this->planetFieldRepository = $planetFieldRepository;
    }

    public function handle(GameControllerInterface $game): void
    {
        $userId = $game->getUser()->getId();

        $colony = $this->colonyLoader->byIdAndUser(
            $this->showShipDisassemblyRequest->getColonyId(),
            $userId
        );

        $field = $this->planetFieldRepository->getByColonyAndFieldId(
            $colony->getId(),
            $this->showShipDisassemblyRequest->getFieldId(),
        );

        if ($field === null) {
            return;
        }

        if ($colony->hasShipyard()) {

            $repairableShips = [];
            foreach ($colony->getOrbitShipList($userId) as $fleet) {
                /** @var Ship $ship */
                foreach ($fleet['ships'] as $ship_id => $ship) {
                    if ($ship->getUserId() != $userId) {
                        continue;
                    }
                    foreach ($this->shipRumpBuildingFunctionRepository->getByShipRump((int) $ship->getRumpId()) as $rump_rel) {
                        if (array_key_exists($rump_rel->getBuildingFunction(), $field->getBuilding()->getFunctions())) {
                            $repairableShips[$ship->getId()] = $ship;
                            break;
                        }
                    }
                }
            }

            $game->appendNavigationPart(
                'colony.php',
                _('Kolonien')
            );
            $game->appendNavigationPart(
                sprintf('?%s=1&id=%d',
                    ShowColony::VIEW_IDENTIFIER,
                    $colony->getId()
                ),
                $colony->getName()
            );
            $game->appendNavigationPart(
                sprintf(
                    '?id=%s&%d=1&fid=%d',
                    $colony->getId(),
                    static::VIEW_IDENTIFIER,
                    $field->getFieldId()
                ),
                _('Schiffsdemontage')
            );
            $game->setPagetitle(_('Schiffsdemontage'));
            $game->setTemplateFile('html/colony_ship_disassembly.xhtml');

            $game->setTemplateVar('SHIP_LIST', $repairableShips);
            $game->setTemplateVar('COLONY', $colony);
            $game->setTemplateVar('FIELD', $field);
        }
    }
}