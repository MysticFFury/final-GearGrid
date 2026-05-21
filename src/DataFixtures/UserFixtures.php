<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setName('John Ralf');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setEmail('johnralf@gmail.com');
        $hashPassword = $this->passwordHasher->hashPassword($admin, '12345');
        $admin->setPassword($hashPassword);
        $manager->persist($admin);

        // Create Staff User
        $staff = new User();
        $staff->setName('staff');
        $staff->setRoles(['ROLE_STAFF']); // or ROLE_USER if you prefer
        $staff->setEmail('staff@gmail.com');
        $staffPassword = $this->passwordHasher->hashPassword($staff, 'staff123');
        $staff->setPassword($staffPassword);
        $manager->persist($staff);

        
        $manager->flush();
        // php bin/console doctrine:fixtures:load
        //symfony console doctrine:fixtures:load --append

    }
}
