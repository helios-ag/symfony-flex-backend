<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Form/Type/Console/UserTypeTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Form\Type\Console;

use App\DTO\User\User as UserDto;
use App\Entity\Role;
use App\Entity\UserGroup;
use App\Form\DataTransformer\UserGroupTransformer;
use App\Form\Type\Console\UserType;
use App\Resource\UserGroupResource;
use App\Service\Localization;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use function array_keys;

/**
 * Class UserTypeTest
 *
 * @package App\Tests\Integration\Form\Type\Console
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class UserTypeTest extends TypeTestCase
{
    /**
     * @testdox Test that form submit with valid input data works as expected
     */
    public function testSubmitValidData(): void
    {
        $resource = $this->getUserGroupResource();
        $localization = $this->getLocalization();

        // Create new role entity for testing
        $roleEntity = new Role('ROLE_ADMIN');

        // Create new user group entity
        $userGroupEntity = (new UserGroup())
            ->setRole($roleEntity);

        $resource
            ->expects(self::once())
            ->method('find')
            ->willReturn([$userGroupEntity]);

        $resource
            ->expects(self::once())
            ->method('findOne')
            ->with($userGroupEntity->getId())
            ->willReturn($userGroupEntity);

        $localization
            ->expects(self::once())
            ->method('getLanguages')
            ->willReturn(['en', 'fi']);

        $localization
            ->expects(self::once())
            ->method('getLocales')
            ->willReturn(['en', 'fi']);

        $localization
            ->expects(self::once())
            ->method('getFormattedTimezones')
            ->willReturn([
                [
                    'timezone' => 'Europe',
                    'identifier' => 'Europe/Helsinki',
                    'offset' => 'GMT+2:00',
                    'value' => 'Europe/Helsinki',
                ],
                [
                    'timezone' => 'Europe',
                    'identifier' => 'Europe/Stockholm',
                    'offset' => 'GMT+1:00',
                    'value' => 'Europe/Stockholm',
                ],
            ]);

        // Create form
        $form = $this->factory->create(UserType::class);

        // Create new DTO object
        $dto = (new UserDto())
            ->setUsername('username')
            ->setFirstName('John')
            ->setLastName('Doe')
            ->setEmail('john.doe@test.com')
            ->setPassword('password')
            ->setLanguage('fi')
            ->setLocale('fi')
            ->setTimezone('Europe/Stockholm')
            ->setUserGroups([$userGroupEntity]);

        // Specify used form data
        $formData = [
            'username' => 'username',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'email' => 'john.doe@test.com',
            'password' => [
                'password1' => 'password',
                'password2' => 'password',
            ],
            'language' => 'fi',
            'locale' => 'fi',
            'timezone' => 'Europe/Stockholm',
            'userGroups' => [$userGroupEntity->getId()],
        ];

        // submit the data to the form directly
        $form->submit($formData);

        // Test that data transformers have not been failed
        self::assertTrue($form->isSynchronized());

        // Test that form data matches with the DTO mapping
        self::assertSame($dto->getId(), $form->getData()->getId());
        self::assertSame($dto->getUsername(), $form->getData()->getUsername());
        self::assertSame($dto->getFirstName(), $form->getData()->getFirstName());
        self::assertSame($dto->getLastName(), $form->getData()->getLastName());
        self::assertSame($dto->getEmail(), $form->getData()->getEmail());
        self::assertSame($dto->getLanguage(), $form->getData()->getLanguage());
        self::assertSame($dto->getLocale(), $form->getData()->getLocale());
        self::assertSame($dto->getTimezone(), $form->getData()->getTimezone());
        self::assertSame($dto->getUserGroups(), $form->getData()->getUserGroups());

        // Check that form renders correctly
        $view = $form->createView();
        $children = $view->children;

        foreach (array_keys($formData) as $key) {
            self::assertArrayHasKey($key, $children);
        }
    }

    /**
     * @return array<PreloadedExtension>
     */
    protected function getExtensions(): array
    {
        parent::getExtensions();

        $resource = $this->getUserGroupResource();
        $localization = $this->getLocalization();

        // create a type instance with the mocked dependencies
        $type = new UserType($resource, new UserGroupTransformer($resource), $localization);

        return [
            // register the type instances with the PreloadedExtension
            new PreloadedExtension([$type], []),
        ];
    }

    /**
     * @phpstan-return  MockObject&UserGroupResource
     */
    private function getUserGroupResource(): MockObject
    {
        static $cache;

        if ($cache === null) {
            $cache = $this->createMock(UserGroupResource::class);
        }

        return $cache;
    }

    /**
     * @phpstan-return  MockObject&Localization
     */
    private function getLocalization(): MockObject
    {
        static $cache;

        if ($cache === null) {
            $cache = $this->createMock(Localization::class);
        }

        return $cache;
    }
}
