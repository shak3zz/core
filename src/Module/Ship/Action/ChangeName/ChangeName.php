<?php

declare(strict_types=1);

namespace Stu\Module\Ship\Action\ChangeName;

use JBBCode\Parser;
use request;
use Stu\Module\Control\ActionControllerInterface;
use Stu\Module\Control\GameControllerInterface;
use Stu\Module\Ship\Lib\ShipLoaderInterface;
use Stu\Module\Ship\View\ShowShip\ShowShip;

final class ChangeName implements ActionControllerInterface
{
    public const ACTION_IDENTIFIER = 'B_CHANGE_NAME';

    private $shipLoader;

    private $bbCodeParser;

    public function __construct(
        ShipLoaderInterface $shipLoader,
        Parser $bbCodeParser
    ) {
        $this->shipLoader = $shipLoader;
        $this->bbCodeParser = $bbCodeParser;
    }

    public function handle(GameControllerInterface $game): void
    {
        $game->setView(ShowShip::VIEW_IDENTIFIER);

        $userId = $game->getUser()->getId();

        $ship = $this->shipLoader->getByIdAndUser(
            request::indInt('id'),
            $userId
        );

        $value = tidyString(strip_tags(request::postString('shipname')));

        if (mb_strlen($this->bbCodeParser->parse($value)->getAsText()) < 3) {
            $game->addInformation(_('Der Schiffname ist zu kurz (Minimum 3 Zeichen)'));
            return;
        }

        $ship->setName($value);
        $ship->save();
        $game->addInformation("Der Schiffname wurde geändert");
    }

    public function performSessionCheck(): bool
    {
        return true;
    }
}
