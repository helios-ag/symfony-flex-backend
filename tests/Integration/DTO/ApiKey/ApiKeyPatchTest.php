<?php
declare(strict_types = 1);
/**
 * /tests/Integration/DTO/ApiKey/ApiKeyPatchTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\DTO\ApiKey;

use App\DTO\ApiKey\ApiKeyPatch;
use App\Entity\ApiKey;
use App\Entity\Role;
use App\Entity\UserGroup;
use App\Tests\Integration\DTO\DtoTestCase;

/**
 * Class ApiKeyPatchTest
 *
 * @package App\Tests\Integration\DTO\ApiKey
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class ApiKeyPatchTest extends DtoTestCase
{
    /**
     * @psalm-var class-string
     * @phpstan-var class-string<ApiKeyPatch>
     */
    protected string $dtoClass = ApiKeyPatch::class;

    /**
     * @testdox Test that `setUserGroups` method updates entity correctly
     */
    public function testThatUserGroupsAreExpected(): void
    {
        $userGroup1 = (new UserGroup())
            ->setName('Group 1')
            ->setRole(new Role('Role 1'));

        $userGroup2 = (new UserGroup())
            ->setName('Group 2')
            ->setRole(new Role('Role 2'));

        $user = (new ApiKey())
            ->setDescription('description')
            ->addUserGroup($userGroup1);

        $dto = (new ApiKeyPatch())
            ->load($user)
            ->setUserGroups([$userGroup2]);

        /** @var ApiKey $updatedApiKey */
        $updatedApiKey = $dto->update($user);

        self::assertCount(2, $updatedApiKey->getUserGroups());
    }
}
