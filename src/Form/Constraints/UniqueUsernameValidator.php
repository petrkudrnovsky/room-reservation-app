<?php

namespace App\Form\Constraints;

use App\Form\Model\AppUserTypeModel;
use App\Service\AppUserManager;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueUsernameValidator extends ConstraintValidator
{
    public function __construct(
        private readonly AppUserManager $appUserManager
    ) {}

    public function validate(mixed $value, Constraint $constraint)
    {
        /** @var $value AppUserTypeModel */
        /** @var $constraint UniqueUsername */
        if(!$constraint instanceof UniqueUsername) {
            throw new UnexpectedTypeException($constraint, UniqueUsername::class);
        }

        if(!$this->appUserManager->isUniqueUsername($value->username)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}