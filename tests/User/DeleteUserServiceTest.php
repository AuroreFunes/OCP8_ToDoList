<?php

namespace App\Test\User;

use App\Entity\User;
use App\Service\User\DeleteUserService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;


/**
 * Required Fixtures :
 *  - "Anonymous" User, is_active = false
 *  - "Admin" User, is_active = true
 *  - "User" User, is_active = true
 *  - At least two other user with at least one task
 */
class DeleteUserServiceTest extends KernelTestCase
{
    /** @var \Doctrine\ORM\EntityManager */
    private $entityManager;

    private DeleteUserService $service;

    // UTILITIES
    private string  $uniqid;
    private User    $anonymous;
    private User    $admin;
    private User    $user;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $container = self::getContainer();

        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->service = $container->get(DeleteUserService::class);

        $this->uniqid = uniqid();

        // Init Users
        $this->anonymous = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'Anonymous']);
        $this->admin = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'Admin']);
        $this->user = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'User']);
    }

    /**
     * Normal case : an admin deletes a user
     */
    public function testOkAdminDeleteUser()
    {
        // Init before run
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $userNb = count($users);

        // Find an User with at least one task
        /** @var User $userToDelete */
        for ($i = $userNb - 1; $i >= 3; $i--) {
            if (count($users[$i]->getTasks()) > 0) {
                $userToDelete = $users[$i];
                break;
            }
        }
        unset($users);

        $oldUsername = $userToDelete->getUserIdentifier();
        $userTaskNb = count($userToDelete->getTasks());
        $anonymousTaskNb = count($this->anonymous->getTasks());

        // run service
        $this->service->deleteUser($userToDelete, $this->admin);

        // check status
        $this->assertTrue($this->service->getStatus());

        // count errors
        $this->assertEmpty($this->service->getErrorsMessages());

        // check result
        $this->assertCount($userNb - 1, $this->entityManager->getRepository(User::class)->findAll());

        /** @var ?User $deletedUser */
        $deletedUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => $oldUsername]);
        $this->assertNull($deletedUser);
        
        // No changes have been made
        $this->assertCount($anonymousTaskNb + $userTaskNb, $this->anonymous->getTasks());
    }

    /**
     * Test KO : a user who is not an administrator tries to update a user
     */
    public function testDeleteUserKoUserIsNotAdmin()
    {
        // Init before run
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $userNb = count($users);

        // Find an User with at least one task
        /** @var User $userToDelete */
        for ($i = $userNb - 1; $i >= 3; $i--) {
            if (count($users[$i]->getTasks()) > 0) {
                $userToDelete = $users[$i];
                break;
            }
        }
        unset($users);

        $userTaskNb = count($userToDelete->getTasks());
        $anonymousTaskNb = count($this->anonymous->getTasks());

        // run service
        $this->service->deleteUser($userToDelete, $this->user);

        // check status
        $this->assertFalse($this->service->getStatus());

        // count errors
        $this->assertCount(1, $this->service->getErrorsMessages());

        // read errors
        $this->assertEquals("Seul un administrateur peut effectuer cette op??ration.", $this->service->getErrorsMessages()->get(0));

        // check result
        $this->assertCount($userNb, $this->entityManager->getRepository(User::class)->findAll());

        /** @var ?User $userNotDelete */
        $userNotDelete = $this->entityManager->getRepository(User::class)->find($userToDelete->getId());
        $this->assertNotNull($userNotDelete);
        
        // No changes have been made
        $this->assertCount($userTaskNb, $userNotDelete->getTasks());
        $this->assertCount($anonymousTaskNb, $this->anonymous->getTasks());
    }


    /**
     * Test KO : a disabled user tries to update a user
     */
    public function testDeleteUserKoUserIsInactive()
    {
        // Init before run
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $userNb = count($users);

        // Find an User with at least one task
        /** @var User $userToDelete */
        for ($i = $userNb - 1; $i >= 3; $i--) {
            if (count($users[$i]->getTasks()) > 0) {
                $userToDelete = $users[$i];
                break;
            }
        }
        unset($users);

        $userTaskNb = count($userToDelete->getTasks());
        $anonymousTaskNb = count($this->anonymous->getTasks());

        // run service
        $this->service->deleteUser($userToDelete, $this->anonymous);

        // check status
        $this->assertFalse($this->service->getStatus());

        // count errors
        $this->assertCount(1, $this->service->getErrorsMessages());

        // read errors
        $this->assertEquals("L'utilisateur a ??t?? d??sactiv??.", $this->service->getErrorsMessages()->get(0));

        // check result
        $this->assertCount($userNb, $this->entityManager->getRepository(User::class)->findAll());

        /** @var ?User $userNotDelete */
        $userNotDelete = $this->entityManager->getRepository(User::class)->find($userToDelete->getId());
        $this->assertNotNull($userNotDelete);
        
        // No changes have been made
        $this->assertCount($userTaskNb, $userNotDelete->getTasks());
        $this->assertCount($anonymousTaskNb, $this->anonymous->getTasks());
    }

    /**
     * Test KO : an unauthenticated user tries to delete a user
     */
    public function testDeleteUserKoUserIsUnauthenticated()
    {
        // Init before run
        $users = $this->entityManager->getRepository(User::class)->findAll();
        $userNb = count($users);

        // Find an User with at least one task
        /** @var User $userToDelete */
        for ($i = $userNb - 1; $i >= 3; $i--) {
            if (count($users[$i]->getTasks()) > 0) {
                $userToDelete = $users[$i];
                break;
            }
        }
        unset($users);

        $userTaskNb = count($userToDelete->getTasks());
        $anonymousTaskNb = count($this->anonymous->getTasks());

        // run service
        $this->service->deleteUser($userToDelete, null);

        // check status
        $this->assertFalse($this->service->getStatus());

        // count errors
        $this->assertCount(1, $this->service->getErrorsMessages());

        // read errors
        $this->assertEquals("Vous n'??tes pas authentifi??.", $this->service->getErrorsMessages()->get(0));

        // check result
        $this->assertCount($userNb, $this->entityManager->getRepository(User::class)->findAll());

        /** @var ?User $userNotDelete */
        $userNotDelete = $this->entityManager->getRepository(User::class)->find($userToDelete->getId());
        $this->assertNotNull($userNotDelete);
        
        // No changes have been made
        $this->assertCount($userTaskNb, $userNotDelete->getTasks());
        $this->assertCount($anonymousTaskNb, $this->anonymous->getTasks());
    }

    /**
     * Test KO : an unauthenticated user tries to delete a user
     */
    public function testDeleteUserKoUserNotFound()
    {
        // Init before run
        $userNb = count($this->entityManager->getRepository(User::class)->findAll());

        // run service
        $this->service->deleteUser(null, $this->admin);

        // check status
        $this->assertFalse($this->service->getStatus());

        // count errors
        $this->assertCount(1, $this->service->getErrorsMessages());

        // read errors
        $this->assertEquals("L'utilisateur n'a pas ??t?? trouv??.", $this->service->getErrorsMessages()->get(0));

        // check result
        $this->assertCount($userNb, $this->entityManager->getRepository(User::class)->findAll());
    }

    /**
     * Test KO : an admin tries to delete our own account
     */
    public function testDeleteUserKoOwnAccount()
    {
        // Init before run
        $userNb = count($this->entityManager->getRepository(User::class)->findAll());

        // run service
        $this->service->deleteUser($this->admin, $this->admin);

        // check status
        $this->assertFalse($this->service->getStatus());

        // count errors
        $this->assertCount(1, $this->service->getErrorsMessages());

        // read errors
        $this->assertEquals("Vous ne pouvez pas supprimer votre propre compte.", $this->service->getErrorsMessages()->get(0));

        // check result
        $this->assertCount($userNb, $this->entityManager->getRepository(User::class)->findAll());

        /** @var ?User $userNotDelete */
        $userNotDelete = $this->entityManager->getRepository(User::class)->find($this->admin->getId());
        $this->assertNotNull($userNotDelete);
    }

    /**
     * Test KO : an admin tries to delete our own account
     */
    public function testDeleteUserKoAnonymous()
    {
        // Init before run
        $userNb = count($this->entityManager->getRepository(User::class)->findAll());

        // run service
        $this->service->deleteUser($this->anonymous, $this->admin);

        // check status
        $this->assertFalse($this->service->getStatus());

        // count errors
        $this->assertCount(1, $this->service->getErrorsMessages());

        // read errors
        $this->assertEquals("L'utilisateur Anonymous ne peut pas ??tre supprim??.", $this->service->getErrorsMessages()->get(0));

        // check result
        $this->assertCount($userNb, $this->entityManager->getRepository(User::class)->findAll());

        /** @var ?User $userNotDelete */
        $userNotDelete = $this->entityManager->getRepository(User::class)->find($this->anonymous->getId());
        $this->assertNotNull($userNotDelete);
    }

}