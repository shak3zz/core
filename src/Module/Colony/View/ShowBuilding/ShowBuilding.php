<?php

declare(strict_types=1);

namespace Stu\Module\Colony\View\ShowBuilding;

use Building;
use Stu\Control\GameControllerInterface;
use Stu\Control\ViewControllerInterface;
use Stu\Module\Colony\Lib\ColonyLoaderInterface;

final class ShowBuilding implements ViewControllerInterface
{
    public const VIEW_IDENTIFIER = 'SHOW_BUILDING';

    private $colonyLoader;

    private $showBuildingRequest;

    public function __construct(
        ColonyLoaderInterface $colonyLoader,
        ShowBuildingRequestInterface $showBuildingRequest
    ) {
        $this->colonyLoader = $colonyLoader;
        $this->showBuildingRequest = $showBuildingRequest;
    }

    public function handle(GameControllerInterface $game): void
    {
        $userId = $game->getUser()->getId();

        $colony = $this->colonyLoader->byIdAndUser(
            $this->showBuildingRequest->getColonyId(),
            $userId
        );

        $building = new Building($this->showBuildingRequest->getBuildingId());

        $game->setTemplateVar('buildingdata', $building);
        $game->setPageTitle($building->getName());
        $game->setTemplateFile('html/ajaxwindow.xhtml');
        $game->setAjaxMacro('html/colonymacros.xhtml/buildinginfo');
        $game->setTemplateVar('COLONY', $colony);
    }
}