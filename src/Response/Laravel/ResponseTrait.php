<?php


namespace Iayoo\ApiResponse\Response\Laravel;


use Illuminate\Http\JsonResponse;

/**
 * Trait ResponseTrait
 * 响应结构说明
 * {
 *     staut   // 描述 HTTP 响应结果：HTTP 状态响应码在 500-599 之间为”fail”，在 400-499 之间为”error”，其它均为”success”
 *     code    // 业务描述操作码，比如 200001 表示注册成功
 *     message // 响应描述
 *     data    // 实际的响应数据
 *     trace   // 异常时的调试信息
 * }
 * @package Iayoo\ApiResponse\Response\Laravel
 */
trait ResponseTrait
{
    /** @var int HTTP 状态码 */
    protected $httpStatusCode = 200;
    /** @var string success|fail|error */
    protected $statusCode = 'success';
    /** @var int 业务错误骂  */
    protected $errorCode = 0;

    /**
     * @param int $httpStatusCode
     */
    public function setHttpStatusCode($httpStatusCode)
    {
        $this->httpStatusCode = $httpStatusCode;
        return $this;
    }

    /**
     * @param string $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * @param int $errorCode
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;
        return $this;
    }

    /**
     * 成功响应
     * @param string|array $message 响应说明
     * @param array $data 响应数据
     * @return JsonResponse
     */
    public function success($message = 'success', $data = [])
    {
        if (is_array($message)) {
            $data    = $message;
            $message = 'success';
        }
        $this->setStatusCode('success');
        return $this->response($message,$data);
    }

    /**
     * 通常为代码异常等
     * 状态响应码在 500-599 之间为”fail”
     * @param $message
     * @param array $trace
     * @return JsonResponse
     */
    public function fail($message,$trace = []){
        $this->setStatusCode('fail');
        return $this->response($message,[],$trace);
    }

    /**
     * 用户操作等错误
     * @param $message
     * @param int $code 业务错误码
     *
     * @return JsonResponse
     */
    public function error($message, $code = 40000)
    {
        $this->setStatusCode('error');
        $this->setErrorCode($code);
        return $this->response($message,[]);
    }

    public function exception($request, $exception)
    {

        $status = method_exists($exception, 'getStatusCode') ? $exception->getStatusCode() : 40000;
        
        return new JsonResponse(
            [
                'message' => $exception->getMessage(),
                'status'  => $this->statusCode,
                'data'    => $this->convertExceptionToArray($exception),
            ],
            $this->httpStatusCode,
            $this->isHttpException($exception) ? $exception->getHeaders() : [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }

    protected function response($message,$data,$trace = [])
    {
        $status = $this->statusCode;
        $code = $this->errorCode;
        if ($status === 'fail'){
            $message = env('APP_DEBUG')?"系统异常:{$message}":"系统异常";
        }
        if ($status === 'success'){
            $trace = [];
        }
        return new JsonResponse(
            $status === 'success'
                ?compact('message','status','code','data')
                :compact('message','status','code','data','trace'),
            $this->httpStatusCode,
            [],
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        );
    }
}