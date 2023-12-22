<?php

namespace App\Api\Controller;

use App\Api\Model\AppUserInput;
use App\Api\Model\AppUserOutput;
use App\Entity\AppUser;
use App\Repository\AppUserRepository;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class AppUserController extends AbstractFOSRestController {
    public function __construct(
        private readonly AppUserRepository $appUserRepository
    ) {
    }

    #[Rest\Get('/user', name: 'api_app_users_list')]
    #[Rest\View]
    public function list(): array
    {
        $appUsers = array_map(
            fn (AppUser $entity) => AppUserOutput::fromEntity($entity),
            $this->appUserRepository->findAll()
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
        $this->appUserRepository->save($appUser, true);
        return AppUserOutput::fromEntity($appUser);
    }

    #[Rest\Delete('/user/{id}', name: 'api_app_users_destroy', requirements: ['id' => '\d+'])]
    #[Rest\View(statusCode: 204)]
    public function destroy(int $id): void
    {
        $appUser = $this->findOrFail($id);
        $this->appUserRepository->remove($appUser, true);
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
