<?php

declare(strict_types=1);

namespace Stu\Component\Communication\Kn;

use JBBCode\Parser;
use Stu\Component\Game\GameEnum;
use Stu\Module\Tal\StatusBarColorEnum;
use Stu\Module\Tal\TalStatusBar;
use Stu\Orm\Entity\KnPostInterface;
use Stu\Orm\Entity\RpgPlotInterface;
use Stu\Orm\Entity\UserInterface;
use Stu\Orm\Repository\KnCommentRepositoryInterface;

final class KnItem implements KnItemInterface {

    private $bbcodeParser;

    private $knCommentRepository;

    private $post;

    private $currentUser;

    public function __construct(
        Parser $bbcodeParser,
        KnCommentRepositoryInterface $knCommentRepository,
        KnPostInterface $post,
        UserInterface $currentUser
    ) {
        $this->bbcodeParser = $bbcodeParser;
        $this->knCommentRepository = $knCommentRepository;
        $this->post = $post;
        $this->currentUser = $currentUser;
    }
    public function getId(): int
    {
        return $this->post->getId();
    }

    public function getUser(): ?UserInterface
    {
        return $this->post->getUser();
    }

    public function getUserId(): int
    {
        return $this->post->getUserId();
    }

    public function getTitle(): string
    {
        return $this->post->getTitle();
    }

    public function getText(): string
    {
        return $this->bbcodeParser->parse($this->post->getText())->getAsHTML();
    }

    public function getDate(): int
    {
        return $this->post->getDate();
    }

    public function getEditDate(): int
    {
        return $this->post->getEditDate();
    }

    public function isEditAble(): bool
    {
        return $this->getDate() > time() - 600 && $this->post->getUser() === $this->currentUser;
    }

    public function getPlotId(): ?int
    {
        return $this->post->getPlotId();
    }

    public function getRPGPlot(): ?RpgPlotInterface
    {
        return $this->post->getRpgPlot();
    }

    public function getCommentCount(): int
    {
        return $this->knCommentRepository->getAmountByPost((int)$this->getId());
    }

    public function displayUserLinks(): bool
    {
        $user = $this->post->getUser();
        return $user !== $this->currentUser && $user->getId() !== GameEnum::USER_NOONE;
    }

    public function getUserName(): string
    {
        return $this->post->getUserName();
    }

    public function isNewerThanMark(): bool
    {
        return $this->getId() > $this->currentUser->getKNMark();
    }

    public function userHasRated(): bool
    {
        return array_key_exists($this->currentUser->getId(), $this->post->getRatings());
    }

    public function getRating(): int
    {
        return array_sum($this->post->getRatings());
    }

    public function getRatingBar(): string
    {
        $ratingAmount = count($this->post->getRatings());

        if ($ratingAmount === 0) {
            return '';
        }

        return (new TalStatusBar())
            ->setColor(StatusBarColorEnum::STATUSBAR_YELLOW)
            ->setLabel(_('Bewertung'))
            ->setMaxValue($ratingAmount)
            ->setValue(array_sum($this->post->getRatings()))
            ->render();

    }
}