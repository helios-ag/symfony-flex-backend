<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Request/ParamConverter/RestResourceConverterTest.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Request\ParamConverter;

use App\Entity\Role;
use App\Request\ParamConverter\RestResourceConverter;
use App\Resource\ResourceCollection;
use App\Resource\RoleResource;
use App\Security\RolesService;
use App\Utils\Tests\StringableArrayObject;
use Generator;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

/**
 * Class RestResourceConverterTest
 *
 * @package App\Tests\Integration\Request\ParamConverter
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
class RestResourceConverterTest extends KernelTestCase
{
    /**
     * @dataProvider dataProviderTestThatSupportMethodReturnsExpected
     *
     * @phpstan-param StringableArrayObject<array> $configuration
     * @psalm-param StringableArrayObject $configuration
     *
     * @testdox Test `supports` method returns `$expected` when using `$configuration` as ParamConverter input.
     */
    public function testThatSupportMethodReturnsExpected(bool $expected, StringableArrayObject $configuration): void
    {
        self::assertSame(
            $expected,
            $this->getConverter()->supports(new ParamConverter($configuration->getArrayCopy())),
        );
    }

    /**
     * @throws Throwable
     */
    public function testThatApplyMethodThrowsAnException(): void
    {
        $this->expectException(NotFoundHttpException::class);

        $request = new Request();
        $request->attributes->set('foo', 'bar');

        $paramConverter = new ParamConverter([
            'name' => 'foo',
            'class' => RoleResource::class,
        ]);

        $this->getConverter()->apply($request, $paramConverter);
    }

    /**
     * @dataProvider dataProviderTestThatApplyMethodReturnsExpected
     *
     * @throws Throwable
     *
     * @testdox Test that `apply` method works as expected when using `$role` as a request attribute.
     */
    public function testThatApplyMethodReturnsExpected(string $role): void
    {
        $request = new Request();
        $request->attributes->set('role', $role);

        $paramConverter = new ParamConverter([
            'name' => 'role',
            'class' => RoleResource::class,
        ]);

        self::assertTrue($this->getConverter()->apply($request, $paramConverter));
        self::assertInstanceOf(Role::class, $request->attributes->get('role'));
        self::assertSame('Description - ' . $role, $request->attributes->get('role')->getDescription());
    }

    /**
     * @psalm-return Generator<array{0: boolean, 1: StringableArrayObject}>
     * @phpstan-return Generator<array{0: boolean, 1: StringableArrayObject<mixed>}>
     */
    public function dataProviderTestThatSupportMethodReturnsExpected(): Generator
    {
        yield [
            false,
            new StringableArrayObject([
                'class' => 'FooBar',
            ]),
        ];

        yield [
            false,
            new StringableArrayObject([
                'class' => LoggerInterface::class,
            ]),
        ];

        yield [
            false,
            new StringableArrayObject([
                'class' => Role::class,
            ]),
        ];

        yield [
            true,
            new StringableArrayObject([
                'class' => RoleResource::class,
            ]),
        ];
    }

    /**
     * @return Generator<array{0: string}>
     */
    public function dataProviderTestThatApplyMethodReturnsExpected(): Generator
    {
        yield [RolesService::ROLE_LOGGED];
        yield [RolesService::ROLE_USER];
        yield [RolesService::ROLE_ADMIN];
        yield [RolesService::ROLE_ROOT];
        yield [RolesService::ROLE_API];
    }

    private function getConverter(): RestResourceConverter
    {
        return new RestResourceConverter(self::getContainer()->get(ResourceCollection::class));
    }
}
