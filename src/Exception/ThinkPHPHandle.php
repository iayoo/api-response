<?php
/**
 *
 */


namespace Iayoo\ApiResponse\Exception;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Iayoo\ApiResponse\Response\ThinkPHP\ResponseTrait;
use Throwable;

class ThinkPHPHandle extends Handle
{
    use ResponseTrait;

    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param  Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }



    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request   $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // 添加自定义异常处理机制
        $this->isJson = $request->isJson();
        if ($e instanceof HttpResponseException) {
            return $e->getResponse();
        } elseif ($e instanceof HttpException) {
            return $this->renderHttpException($e);
        }  elseif ($e instanceof ValidateException) {
            return $this->error($e->getMessage());
        } else {
            return $this->convertExceptionToResponse($e);
        }
    }
}