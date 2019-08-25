<?php

declare(strict_types=1);

namespace Stu\Module\Maindesk\View\Overview;

use AllianceTopic;
use ColonyShipQueue;
use ContactlistData;
use HistoryEntry;
use KNPosting;
use Stu\Control\GameControllerInterface;
use Stu\Control\ViewControllerInterface;
use User;
use UserProfileVisitors;

final class Overview implements ViewControllerInterface
{

    public function __construct()
    {
    }

    public function handle(GameControllerInterface $game): void
    {
        $user = $game->getUser();
        $userId = $user->getId();

        $game->appendNavigationPart(
            'maindesk.php',
            _('Maindesk')
        );
        $game->setPageTitle(_('/ Maindesk'));
        $game->setTemplateFile('html/maindesk.xhtml');

        $game->setTemplateVar(
            'DISPLAY_FIRST_COLONY_DIALOGUE',
            (int)$user->getActive() === 1
        );
        $game->setTemplateVar(
            'NEW_KN_POSTINGS',
            KNPosting::getBy("WHERE id>" . $user->getKnMark() . " LIMIT 3")
        );
        $game->setTemplateVar(
            'NEW_KN_POSTING_COUNT',
            KNPosting::countInstances('id>' . $user->getKnMark())
        );
        $game->setTemplateVar(
            'RECENT_PROFILE_VISITORS',
            UserProfileVisitors::getRecentList($userId)
        );
        $game->setTemplateVar(
            'RANDOM_ONLINE_USER',
            User::getListBy(sprintf(
                'WHERE id != %d AND (show_online_status=1 OR id IN (SELECT user_id FROM stu_contactlist WHERE mode = %d AND recipient = %d)) AND lastaction > %d ORDER BY RAND() LIMIT 15',
                $userId,
                ContactlistData::CONTACT_FRIEND,
                $userId,
                (time() - USER_ONLINE_PERIOD)
            ))
        );
        $game->setTemplateVar(
            'SHIP_BUILD_PROGRESS',
            ColonyShipQueue::getByUserId($userId)
        );
        $game->setTemplateVar(
            'RECENT_ALLIANCE_BOARD_TOPICS',
            AllianceTopic::getLatestTopics($user->getAllianceId())
        );
        $game->setTemplateVar('RECENT_HISTORY', HistoryEntry::getListBy('ORDER BY id DESC LIMIT 10'));
    }
}
