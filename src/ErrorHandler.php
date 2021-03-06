<?php
/**
 * @link http://www.atomframework.net/
 * @copyright Copyright (c) 2017 Safarov Alisher
 * @license https://github.com/atomwares/atom-errorhandler/blob/master/LICENSE (MIT License)
 */

namespace Atom\Middleware;

use Atom\Http\Server\CallableMiddleware;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Error;
use Exception;

/**
 * Class ErrorHandler
 *
 * @package Atom
 */
class ErrorHandler implements MiddlewareInterface
{
    /**
     * @var MiddlewareInterface|null
     */
    protected $exceptionHandler;
    /**
     * @var MiddlewareInterface|null
     */
    protected $errorHandler;
    /**
     * @var \Throwable
     */
    protected $error;

    /**
     * ErrorHandler constructor.
     *
     * @param MiddlewareInterface|callable|null $exceptionHandler
     * @param MiddlewareInterface|callable|null $errorHandler
     */
    public function __construct($exceptionHandler = null, $errorHandler = null)
    {
        $this->setExceptionHandler($exceptionHandler);
        $this->setErrorHandler($errorHandler);
    }

    /**
     * @param MiddlewareInterface|callable|null $handler
     */
    public function setExceptionHandler($handler)
    {
        $this->exceptionHandler = static::filterHandler($handler);
    }

    /**
     * @param MiddlewareInterface|callable|null $handler
     */
    public function setErrorHandler($handler)
    {
        $this->errorHandler = static::filterHandler($handler);
    }

    /**
     * @return \Throwable|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     * @throws Error
     * @throws Exception
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $handler->handle($request);
        } catch (Exception $e) {
            if ($this->exceptionHandler === null) {
                throw $e;
            }

            $response = $this->exceptionHandler->process(
                $request->withAttribute('error', $this->error = $e),
                $handler
            );
        } catch (Error $e) {
            if ($this->errorHandler === null) {
                throw $e;
            }

            $response = $this->errorHandler->process(
                $request->withAttribute('error', $this->error = $e),
                $handler
            );
        }

        return $response;
    }

    /**
     * @param MiddlewareInterface|callable $handler
     *
     * @return MiddlewareInterface
     */
    protected static function filterHandler($handler)
    {
        if (is_callable($handler)) {
            $handler = new CallableMiddleware($handler);
        }

        if ($handler !== null && ! $handler instanceof MiddlewareInterface) {
            throw new InvalidArgumentException(sprintf(
                'Invalid middleware provided; must be an instance of %s, received %s',
                MiddlewareInterface::class,
                (is_object($handler) ? get_class($handler) : gettype($handler))
            ));
        }

        return $handler;
    }
}
