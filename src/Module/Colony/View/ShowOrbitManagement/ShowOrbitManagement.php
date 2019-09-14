<?php

declare(strict_types=1);

namespace Stu\Module\Colony\View\ShowOrbitManagement;

use Ship;
use Stu\Module\Colony\Lib\ColonyLibFactoryInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Control\ViewControllerInterface;
use Stu\Module\Colony\Lib\ColonyLoaderInterface;
use Stu\Module\Colony\View\ShowColony\ShowColony;

final class ShowOrbitManagement implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_ORBITAL_SHIPS';

    private $colonyLoader;

    private $showOrbitManagementRequest;

    private $colonyLibFactory;

    public function __construct(
        ColonyLoaderInterface $colonyLoader,
        ShowOrbitManagementRequestInterface $showOrbitManagementRequest,
        ColonyLibFactoryInterface $colonyLibFactory
    ) {
        $this->colonyLoader = $colonyLoader;
        $this->showOrbitManagementRequest = $showOrbitManagementRequest;
        $this->colonyLibFactory = $colonyLibFactory;
    }

    public function handle(GameControllerInterface $game): void
    {
        $userId = $game->getUser()->getId();

        $colony = $this->colonyLoader->byIdAndUser(
            $this->showOrbitManagementRequest->getColonyId(),
            $userId
        );

        $shipList = Ship::getObjectsBy(
            sprintf(
                "WHERE systems_id=%s AND sx=%s AND sy=%s AND (user_id=%s OR cloak=0) ORDER BY is_destroyed ASC, fleets_id DESC,id ASC",
                $colony->getSystemsId(),
                $colony->getSX(),
                $colony->getSY(),
                $userId
            )
        );

        $groupedList = [];

        foreach ($shipList as $ship) {
            $fleetId = $ship->getFleetId();

            $fleet = $groupedList[$fleetId] ?? null;
            if ($fleet === null) {
                $groupedList[$fleetId] = [];
            }

            $groupedList[$fleetId][] = $this->colonyLibFactory->createOrbitShipItem($ship, $userId);
        }

        $list = [];

        foreach ($groupedList as $fleetId => $shipList) {
            $list[] = $this->colonyLibFactory->createOrbitFleetItem(
                $fleetId,
                $shipList,
                $userId
            );
        }

        $game->appendNavigationPart(
            'colony.php',
            _('Kolonien')
        );
        $game->appendNavigationPart(
            sprintf('?%s=1&id=%s',
                ShowColony::VIEW_IDENTIFIER,
                $colony->getId()
            ),
            $colony->getNameWithoutMarkup()
        );
        $game->appendNavigationPart(
            sprintf('?%s=1&id=%d',
                static::VIEW_IDENTIFIER,
                $colony->getId()),
            _('Orbitalmanagement')
        );
        $game->setPagetitle(sprintf('%s Orbit', $colony->getNameWithoutMarkup()));
        $game->setTemplateFile('html/orbitalmanagement.xhtml');

        $game->setTemplateVar('COLONY', $colony);
        $game->setTemplateVar('ORBIT_SHIP_LIST', $list);
    }
}
