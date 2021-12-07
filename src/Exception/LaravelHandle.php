<?php
namespace Iayoo\ApiResponse\Exception;
use Iayoo\OpenApi\Exception\AuthException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\RecordsNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Router;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use Illuminate\Support\Arr;

class LaravelHandle extends ExceptionHandler
{

    use \Iayoo\ApiResponse\Response\Laravel\ResponseTrait;
    protected $ignoreTrace = [

    ];

    protected $exceptionMap = [
        AuthException::class
    ];

    public function report(Throwable $e)
    {
        return parent::report($e); // TODO: Change the autogenerated stub
    }

    public function register()
    {
        parent::register(); // TODO: Change the autogenerated stub
    }

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
        } elseif ($e instanceof AuthException){
            $e = new AuthenticationException($e->getMessage());
        }

        return $e;
    }

    public function render($request, Throwable $e)
    {
        if (method_exists($e, 'render') && $response = $e->render($request)) {
            return Router::toResponse($request, $response);
        } elseif ($e instanceof Responsable) {
            return $e->toResponse($request);
        }

        $e = $this->prepareException($this->mapException($e));



        foreach ($this->renderCallbacks as $renderCallback) {
            foreach ($this->firstClosureParameterTypes($renderCallback) as $type) {
                if (is_a($e, $type)) {
                    $response = $renderCallback($e, $request);

                    if (! is_null($response)) {
                        return $response;
                    }
                }
            }
        }
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        } elseif ($e instanceof AuthenticationException) {
            // 鉴权拦截
            return $this->unauthenticated($request, $e);
        } elseif ($e instanceof ValidationException) {
            // 表单验证拦截
            return $this->ValidationExceptionError($e, $request);
        } elseif ($e instanceof NotFoundHttpException){
            // 新增页面不存在的拦截
            return $this->undefined($request,$e);
        }
        return $this->shouldReturnJson($request, $e)
            ? $this->prepareJsonResponse($request, $e)
            : $this->prepareResponse($request, $e);
    }

    protected function undefined($request, NotFoundHttpException $exception){
        if (!$this->shouldReturnJson($request, $exception)){
            return $this->prepareResponse($request, $exception);
        }
        return $this->error('undefined',40400);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        if (!$this->shouldReturnJson($request, $exception)){
            redirect()->guest($exception->redirectTo() ?? route('login'));
        }
        return $this->error($exception->getMessage(),40100);
    }

    /**
     * 验证起错误拦截响应
     * @param ValidationException $e
     * @param $request
     * @return JsonResponse
     */
    protected function ValidationExceptionError(ValidationException $e, $request){
        $error = $e->errors();
        $message = Arr::first(Arr::first($error));
        return $this->error($message,40100,$error);
    }

    public function prepareJsonResponse($request, Throwable $e)
    {
        if ($this->shouldntReport($e)){
            return $this->error($e->getMessage());
        }
        /** @var Logger $logger */
        $logger = app('log');
        $loggerClass = get_class($logger->getLogger());

        if ($loggerClass === "Iayoo\MysqlLogger\monolog\mysql\Logger"){
            $logId = $logger->getLogerId();
        }else{
            $logId = null;
        }

        $this->setErrorCode($logId?"50".str_pad((string)$logId,10,'0',STR_PAD_LEFT):50000);
        return $this->fail($e->getMessage(),[
            'line'     => $e->getLine(),
            'file'     => $e->getFile(),
            'exception'=> get_class($e),
            'data'     => $e->getTrace(),
        ]);
    }
}