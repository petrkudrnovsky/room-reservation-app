<?php

namespace App\Api\Controller;

use App\Api\Mapper\AppUserInputMapper;
use App\Api\Model\AppUserInput;
use App\Api\Model\AppUserOutput;
use App\Api\Service\EntityLinksFactory;
use App\Entity\AppUser;
use App\Filter\AppUserFilterCriteria;
use App\Repository\AppUserRepository;
use App\Service\AppUserManagerInterface;
use App\Voter\UserVoter;
use Exception;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\HttpFoundation\Request;

class AppUserController extends AbstractFOSRestController {
    public function __construct(
        private readonly AppUserRepository $appUserRepository,
        private readonly AppUserManagerInterface $appUserManager,
        private readonly AppUserInputMapper $appUserInputMapper,
        private readonly EntityLinksFactory $linksFactory,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    #[Rest\Get('/user', name: 'api_app_users_list')]
    #[Rest\View]
    public function list(Request $request): array {
        $this->denyAccessUnlessGranted(UserVoter::VIEW_INDEX);

        $criteria = new AppUserFilterCriteria(
            username: $request->query->get('username'),
            name: $request->query->get('name'),
            email: $request->query->get('email'),
            phone: $request->query->get('phone'),
        );

        $appUsers = array_map(
            fn (AppUser $entity) => AppUserOutput::fromEntity($entity, $this->linksFactory->forAppUser($entity)),
            $this->appUserManager->findAppUsersByFilters($criteria)
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

        $this->denyAccessUnlessGranted(UserVoter::VIEW_DETAIL, $appUser);

        return AppUserOutput::fromEntity($appUser, $this->linksFactory->forAppUser($appUser));
    }

    /**
     * @throws Exception
     */
    #[Rest\Post('/user', name: 'api_app_users_create', defaults: ['id' => null])]
    #[Rest\Put('/user/{id}', name: 'api_app_users_update', requirements: ['id' => '\d+'])]
    #[ParamConverter('appUserInput', converter: 'fos_rest.request_body')]
    #[Rest\View(statusCode: 201)]
    public function update(?int $id, AppUserInput $appUserInput, ConstraintViolationListInterface $errors): AppUserOutput
    {
        $appUser = $id !== null ? $this->findOrFail($id) : new AppUser();
        if($id === null) {
            $this->denyAccessUnlessGranted(UserVoter::CREATE);
        } else {
            $this->denyAccessUnlessGranted(UserVoter::EDIT, $appUser);
        }

        if ($errors->count() > 0) {
            throw new HttpException(400, message: \implode("\n", \array_map(
                fn (ConstraintViolationInterface $constraintViolation) => $constraintViolation->getMessage(),
                array(...$errors)
            )));
        }

        $appUser = $this->appUserInputMapper->toEntity($appUserInput, $appUser);
        $hashedPassword = $this->passwordHasher->hashPassword($appUser, $appUserInput->getPlainPassword());
        $appUser->setPassword($hashedPassword);
        $this->appUserManager->saveToDatabase($appUser);
        return AppUserOutput::fromEntity($appUser, $this->linksFactory->forAppUser($appUser));
    }

    #[Rest\Delete('/user/{id}', name: 'api_app_users_delete', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 204)]
    public function destroy(int $id): void
    {
        $appUser = $this->findOrFail($id);
        $this->denyAccessUnlessGranted(UserVoter::DELETE, $appUser);
        $this->appUserManager->removeFromDatabase($appUser);
    }

    /**
     * @throws Exception
     */
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

        if ($appUserInput->password === null) {
            throw new HttpException(400, message: 'Password is required');
        }

        if($appUserInput->memberRooms !== null || $appUserInput->adminRooms !== null ||
            $appUserInput->memberGroups !== null || $appUserInput->adminGroups !== null ||
            $appUserInput->approvedReservations !== null || $appUserInput->reservations !== null) {
            throw new HttpException(400, message: 'You cannot set rooms, groups or reservations when registering');
        }

        $appUser = $this->appUserInputMapper->toEntity($appUserInput, $appUser);
        $appUser->setRoles(['ROLE_USER']);
        $hashedPassword = $this->passwordHasher->hashPassword($appUser, $appUserInput->getPlainPassword());
        $appUser->setPassword($hashedPassword);
        $this->appUserManager->saveToDatabase($appUser);
        return AppUserOutput::fromEntity($appUser, $this->linksFactory->forAppUser($appUser));
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
