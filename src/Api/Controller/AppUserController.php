<?php

namespace App\Api\Controller;

use App\Api\Model\AppUserInput;
use App\Api\Model\AppUserOutput;
use App\Entity\AppUser;
use App\Repository\AppUserRepository;
use App\Service\AppUserManager;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\HttpFoundation\Request;

class AppUserController extends AbstractFOSRestController {
    public function __construct(
        private readonly AppUserRepository $appUserRepository,
        private readonly AppUserManager $appUserManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        $this->passwordHasher = $passwordHasher;
    }

    #[Rest\Get('/user', name: 'api_app_users_list')]
    #[Rest\View]
    #[IsGranted(new Expression('is_granted("ROLE_SUPER_ADMIN") or is_granted("ROLE_ROOM_MANAGER") or is_granted("ROLE_GROUP_MANAGER")'))]
    public function list(Request $request): array {
        $username = $request->query->get('username');
        $name = $request->query->get('name');
        $email = $request->query->get('email');
        $phone = $request->query->get('phone');

        $appUsers = array_map(
            fn (AppUser $entity) => AppUserOutput::fromEntity($entity),
            $this->appUserManager->findAppUsersByFilters($username, $name, $email, $phone)
        );

        return ['appUsers' => $appUsers];
    }



    #[Rest\Get('/user/{id}', name: 'api_app_users_detail', requirements: ['id' => '\d+'])]
    #[Rest\View]
    public function detail(int $id): AppUserOutput
    {
        $appUser = $this->appUserRepository->find($id);

        if (!$appUser) {
            throw $this->createNotFoundException('AppUser not found');
        }

        return AppUserOutput::fromEntity($appUser);
    }

    #[Rest\Post('/user', name: 'api_app_users_create', defaults: ['id' => null])]
    #[Rest\Put('/user/{id}', name: 'api_app_users_update', requirements: ['id' => '\d+'])]
    #[ParamConverter('appUserInput', converter: 'fos_rest.request_body')]
    #[Rest\View(statusCode: 201)]
    public function update(?int $id, AppUserInput $appUserInput, ConstraintViolationListInterface $errors): AppUserOutput
    {
        $appUser = $id !== null ? $this->findOrFail($id) : new AppUser();
        if ($errors->count() > 0) {
            throw new HttpException(400, message: \implode("\n", \array_map(
                fn (ConstraintViolationInterface $constraintViolation) => $constraintViolation->getMessage(),
                array(...$errors)
            )));
        }

        $appUser = $appUserInput->toEntity($appUser);
        $this->appUserManager->saveToDatabase($appUser);
        return AppUserOutput::fromEntity($appUser);
    }

    #[Rest\Delete('/user/{id}', name: 'api_app_users_delete', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 204)]
    public function destroy(int $id): void
    {
        $appUser = $this->findOrFail($id);
        $this->appUserManager->removeFromDatabase($appUser);
    }

    #[Rest\Post('/user/register')]
    #[ParamConverter('appUserInput', converter: 'fos_rest.request_body')]
    #[Rest\View(statusCode: 201)]
    public function register(AppUserInput $appUserInput, ConstraintViolationListInterface $errors): AppUserOutput
    {
        $appUser = new AppUser();
        if ($errors->count() > 0) {
            throw new HttpException(400, message: \implode("\n", \array_map(
                fn (ConstraintViolationInterface $constraintViolation) => $constraintViolation->getMessage(),
                array(...$errors)
            )));
        }

        $appUser = $appUserInput->toEntity($appUser);
        $appUser->setRoles(['ROLE_USER']);
        $hashedPassword = $this->passwordHasher->hashPassword($appUser, $appUserInput->getPlainPassword());
        $appUser->setPassword($hashedPassword);
        $this->appUserManager->saveToDatabase($appUser);
        return AppUserOutput::fromEntity($appUser);
    }

    private function findOrFail(int $id): AppUser
    {
        $appUser = $this->appUserRepository->find($id);
        if ($appUser === null) {
            throw $this->createNotFoundException();
        }

        return $appUser;
    }
}
