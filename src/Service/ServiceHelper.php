<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Persistence\ObjectManager;

abstract class ServiceHelper
{

    // PARAMETERS
    protected const USER_ROLES_AVALIABLE = ['ROLE_USER', 'ROLE_ADMIN'];
    protected const ROLE_USER  = self::USER_ROLES_AVALIABLE[0];
    protected const ROLE_ADMIN = self::USER_ROLES_AVALIABLE[1];

    // DEPENDENCIES
    protected ObjectManager $manager;

    // UTILITIES
    protected bool $status;
    protected ArrayCollection $functArgs;
    protected ArrayCollection $functResult;
    protected ArrayCollection $errMessages;

    // ERRORS
    protected const ERR_DB_ACCESS           = "Une erreur interne s'est produite.";
    protected const ERR_USER_NOT_LOGGED_IN  = "Vous n'êtes pas authentifié.";
    protected const ERR_INACTIVE_USER       = "L'utilisateur a été désactivé.";
    protected const ERR_USER_IN_NOT_ADMIN   = "Seul un administrateur peut effectuer cette opération.";

    public function __construct(ManagerRegistry $manager) {
        $this->manager = $manager->getManager();

        $this->initHelper();
    }

    protected function initHelper(): void
    {
        $this->status       = false;
        $this->functArgs    = new ArrayCollection();
        $this->functResult  = new ArrayCollection();
        $this->errMessages  = new ArrayCollection();
    }

    // ============================================================================================
    // CHECKING JOBS
    // ============================================================================================
    /**
     * Returns false if the user is null or inactive, otherwise returns true
     */
    protected function checkUser(?User $user)
    {
        if (null === $user) {
            $this->errMessages->add(self::ERR_USER_NOT_LOGGED_IN);
            return false;
        }

        if (false === $this->userIsActive($user)) {
            $this->errMessages->add(self::ERR_INACTIVE_USER);
            return false;
        }

        return true;
    }

    /**
     * Returns true if the user is active, otherwise returns false
     */
    protected function userIsActive(User $user): bool
    {
        if (false === $user->isActive()) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if the user has the administrator role, otherwise returns false
     */
    protected function userIsAdmin(User $user): bool
    {
        return in_array(self::ROLE_ADMIN, $user->getRoles());
    }

    /**
     * Checks if the user is valid, then if it has the administrator role and returns true, 
     * otherwise returns false.
     */
    protected function checkUserIsValidAndAdmin(?User $user): bool
    {
        if (false === $this->checkUser($user)) {
            return false;
        }

        if (false === $this->userIsAdmin($user)) {
            $this->errMessages->add(self::ERR_USER_IN_NOT_ADMIN);
            return false;
        }

        return true;
    }

    /**
     * Returns true if the two users passed in parameter are the same entity. Otherwise returns false.
     */
    protected function actingUserIsAuthenticatedUser(User $actingUser, User $authenticatedUser): bool
    {
        if ($actingUser->getId() !== $authenticatedUser->getId()) {
            return false;
        }

        return true;
    }

    // ============================================================================================
    // OUT
    // ============================================================================================
    public function getStatus()
    {
        return $this->status;
    }

//    public function getArguments()
//    {
//        return $this->functArgs;
//    }

//    public function getResult()
//    {
//        return $this->functResult;
//    }

    public function getErrorsMessages()
    {
        return $this->errMessages;
    }

}