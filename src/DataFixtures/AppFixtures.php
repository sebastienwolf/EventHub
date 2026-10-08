<?php

namespace App\DataFixtures;

use App\Story\AppStory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

/**
 * Entry point of "bin/console doctrine:fixtures:load": delegates to the Foundry story.
 */
class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        AppStory::load();
    }
}
