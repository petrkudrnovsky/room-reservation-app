<?php

namespace App\Form\Constraints;

use App\Api\Model\AppUserInput;
use App\Form\Model\AppUserTypeModel;
use App\Service\AppUserManagerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class UniqueUsernameValidator extends ConstraintValidator
{
    public function __construct(
        private readonly AppUserManagerInterface $appUserManager
    ) {}

    public function validate(mixed $value, Constraint $constraint)
    {
        /** @var $value AppUserTypeModel */
        /** @var $constraint UniqueUsername */
        if(!$constraint instanceof UniqueUsername) {
            throw new UnexpectedTypeException($constraint, UniqueUsername::class);
        }

        if ($value instanceof AppUserInput) {
            if(!$this->appUserManager->isUniqueUsername($value->username, $value->id)) {
                $this->context->buildViolation($constraint->message)
                    ->addViolation();
            }
            return;
        }

        if(!$this->appUserManager->isUniqueUsername($value->username, $value->userId)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }
}