<?php
declare(strict_types = 1);
/**
 * /tests/Integration/Rest/Traits/Methods/FindMethodTestClass.php
 *
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */

namespace App\Tests\Integration\Rest\Traits\Methods\src;

use App\Rest\Controller;
use App\Rest\Interfaces\ResponseHandlerInterface;
use App\Rest\Interfaces\RestResourceInterface;
use App\Rest\Traits\Methods\FindMethod;

/**
 * Class FindMethodTestClass - just a dummy class so that we can actually test that trait.
 *
 * @package App\Tests\Integration\Rest\Traits\Methods\src
 * @author TLe, Tarmo Leppänen <tarmo.leppanen@pinja.com>
 */
abstract class FindMethodTestClass extends Controller
{
    use FindMethod;

    public function __construct(
        protected RestResourceInterface $resource,
        ResponseHandlerInterface $responseHandler,
    ) {
        $this->responseHandler = $responseHandler;
    }
}
