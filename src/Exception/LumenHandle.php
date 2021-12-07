<?php


namespace Iayoo\ApiResponse\Exception;


use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Illuminate\Support\Arr;


class LumenHandle extends ExceptionHandler
{
    use \Iayoo\ApiResponse\Response\Laravel\ResponseTrait;

    protected $exceptionMap = [];

    protected function prepareException(Throwable $e)
    {
        if ($e instanceof ModelNotFoundException) {
            $e = new NotFoundHttpException($e->getMessage(), $e);
        } elseif ($e instanceof AuthorizationException) {
            $e = new AccessDeniedHttpException($e->getMessage(), $e);
        } elseif ($e instanceof TokenMismatchException) {
            $e = new HttpException(419, $e->getMessage(), $e);
        } elseif ($e instanceof SuspiciousOperationException) {
            $e = new NotFoundHttpException('Bad hostname provided.', $e);
        } elseif ($e instanceof RecordsNotFoundException) {
            $e = new NotFoundHttpException('Not found.', $e);
        }

        return $e;
    }

    protected function undefined(){
        return $this->error('undefined',40400);
    }

    public function prepareJsonResponse($request, Throwable $e)
    {
        $this->setErrorCode(50000);
        return $this->fail($e->getMessage(),[
            'line'     => $e->getLine(),
            'file'     => $e->getFile(),
            'exception'=> get_class($e),
            'previous' => $e->getPrevious(),
            'data'     => $e->getTraceAsString(),
        ]);
    }

    public function render($request, Throwable $e)
    {

        if (method_exists($e, 'render')) {
            return $e->render($request);
        } elseif ($e instanceof Responsable) {
            return $e->toResponse($request);
        }

        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        } elseif ($e instanceof ModelNotFoundException) {
            $e = new NotFoundHttpException($e->getMessage(), $e);
        } elseif ($e instanceof AuthorizationException) {
            $e = new HttpException(403, $e->getMessage());
        } elseif ($e instanceof ValidationException && $e->getResponse()) {
            return $e->getResponse();
        } elseif ($e instanceof NotFoundHttpException){
            // 新增页面不存在的拦截
            return $this->undefined();
        }

        return $request->expectsJson()
            ? $this->prepareJsonResponse($request, $e)
            : $this->prepareResponse($request, $e);
    }
}