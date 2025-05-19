<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class IsVerifiedVoter implements VoterInterface
{
    public function vote(TokenInterface $token, mixed $subject, array $attributes): int
    {
        $user = $token->getUser();

        // If the user is not logged in, deny access
        if (!$user instanceof User) {
            return self::ACCESS_DENIED;
        }

        // Check if we're testing for IS_VERIFIED
        if (in_array('IS_VERIFIED', $attributes) && !$user->isVerified()) {
            return self::ACCESS_DENIED;
        }

        return self::ACCESS_GRANTED;
    }
} 